<?php
namespace Advent;

use PDO;
use RuntimeException;

final class App
{
    private array $env;
    private PDO $db;

    public function __construct(private string $root)
    {
        $this->env = $this->loadEnv();
        $dbPath = $this->env('DB_PATH', 'var/advent.sqlite');
        if (!str_starts_with($dbPath, '/')) $dbPath = $this->root . '/' . $dbPath;
        if (!is_dir(dirname($dbPath))) mkdir(dirname($dbPath), 0775, true);
        $this->db = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
        $this->initDb();
    }

    public function db(): PDO { return $this->db; }

    public function env(string $key, ?string $default = null): string
    {
        $environmentValue = getenv($key);
        if ($environmentValue !== false) {
            return (string)$environmentValue;
        }

        return (string)($this->env[$key] ?? $default ?? '');
    }

    public function locations(): array
    {
        $configured = trim($this->env('LOCATION'));
        if ($configured === '') {
            return $this->defaultLocations();
        }

        $locations = [];
        foreach (explode(',', $configured) as $name) {
            $name = trim($name);
            $slug = self::locationSlug($name);
            if ($name !== '' && $slug !== '' && !isset($locations[$slug])) {
                $locations[$slug] = $name;
            }
        }

        return $locations ?: $this->defaultLocations();
    }

    public function weekdayShort(int $day, ?int $year = null): string
    {
        if ($day < 1 || $day > 23) {
            throw new RuntimeException('Ungültiger Tag.');
        }

        $date = new \DateTimeImmutable(sprintf('%04d-12-%02d', $year ?? (int)date('Y'), $day));
        return [
            1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do',
            5 => 'Fr', 6 => 'Sa', 7 => 'So',
        ][(int)$date->format('N')];
    }

    private function defaultLocations(): array
    {
        return [
            'nebringen' => 'Nebringen',
            'oeschelbronn' => 'Öschelbronn',
            'tailfingen' => 'Tailfingen',
        ];
    }

    private static function locationSlug(string $name): string
    {
        $name = strtr($name, ['Ä'=>'Ae', 'Ö'=>'Oe', 'Ü'=>'Ue', 'ä'=>'ae', 'ö'=>'oe', 'ü'=>'ue', 'ß'=>'ss']);
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?? '';
        return trim($name, '-');
    }

    public function locationName(string $slug): ?string { return $this->locations()[$slug] ?? null; }

    public function isLocked(string $location): bool
    {
        $s = $this->db->prepare('SELECT registration_locked FROM location_settings WHERE location = ?');
        $s->execute([$location]);
        return (int)$s->fetchColumn() === 1;
    }

    public function setLocked(string $location, bool $locked): void
    {
        $s = $this->db->prepare('INSERT INTO location_settings (location, registration_locked) VALUES (?, ?)
            ON CONFLICT(location) DO UPDATE SET registration_locked = excluded.registration_locked');
        $s->execute([$location, $locked ? 1 : 0]);
    }

    public function registration(string $location, int $day): ?array
    {
        $s = $this->db->prepare('SELECT * FROM registrations WHERE location = ? AND day = ?');
        $s->execute([$location, $day]);
        return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function registrations(string $location): array
    {
        $s = $this->db->prepare('SELECT * FROM registrations WHERE location = ? ORDER BY day');
        $s->execute([$location]);
        $rows = [];
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) $rows[(int)$r['day']] = $r;
        return $rows;
    }

    public function save(string $location, int $day, array $data): void
    {
        $sql = 'INSERT INTO registrations (location, day, name, address, street, house_number, phone, email, publication_consent, created_at, updated_at)
                VALUES (:location,:day,:name,:address,:street,:house_number,:phone,:email,:consent,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
                ON CONFLICT(location,day) DO UPDATE SET name=excluded.name, address=excluded.address, street=excluded.street, house_number=excluded.house_number,
                phone=excluded.phone, email=excluded.email, publication_consent=excluded.publication_consent, updated_at=CURRENT_TIMESTAMP';
        $s = $this->db->prepare($sql);
        $s->execute([
            ':location'=>$location, ':day'=>$day, ':name'=>$data['name'], ':address'=>$data['address'],
            ':street'=>$data['address'], ':house_number'=>'', ':phone'=>$data['phone'], ':email'=>$data['email'] ?: null,
            ':consent'=>!empty($data['publication_consent']) ? 1 : 0,
        ]);
    }

    public function dayAvailable(string $location, int $day, ?int $exceptDay = null): bool
    {
        if ($day < 1 || $day > 23) {
            return false;
        }
        if ($exceptDay !== null && $day === $exceptDay) {
            return true;
        }

        return $this->registration($location, $day) === null;
    }

    public function saveAdmin(string $location, int $currentDay, int $targetDay, array $data): void
    {
        if (!$this->dayAvailable($location, $targetDay, $currentDay)) {
            throw new RuntimeException('day_taken');
        }

        $this->db->beginTransaction();
        try {
            if ($currentDay !== $targetDay) {
                $delete = $this->db->prepare('DELETE FROM registrations WHERE location = ? AND day = ?');
                $delete->execute([$location, $currentDay]);
            }
            $this->save($location, $targetDay, $data);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function delete(string $location, int $day): void
    {
        $s=$this->db->prepare('DELETE FROM registrations WHERE location=? AND day=?'); $s->execute([$location,$day]);
    }

    public function deleteAll(string $location): void
    {
        $s=$this->db->prepare('DELETE FROM registrations WHERE location=?'); $s->execute([$location]);
    }

    public function adminSignature(string $location): string
    {
        $secret = $this->env('APP_SECRET');
        if (strlen($secret) < 32) throw new RuntimeException('APP_SECRET fehlt oder ist zu kurz (mindestens 32 Zeichen).');
        return hash_hmac('sha256', 'admin|' . $location, $secret);
    }

    public function validAdminSignature(string $location, ?string $sig): bool
    {
        if (!$sig) return false;
        try { return hash_equals($this->adminSignature($location), $sig); } catch (RuntimeException) { return false; }
    }

    public function csrf(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
    }

    public function checkCsrf(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return isset($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
    }

    public function baseUrl(): string { return rtrim($this->env('APP_URL'), '/'); }

    private function loadEnv(): array
    {
        $file = $this->root . '/.env';
        if (!is_file($file)) {
            $file = dirname($this->root) . '/.env';
        }
        $out=[];
        if (!is_file($file)) return $out;
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line=trim($line); if ($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue;
            [$k,$v]=array_map('trim',explode('=',$line,2)); $out[$k]=trim($v,"\"'");
        }
        return $out;
    }

    private function initDb(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS registrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            location TEXT NOT NULL,
            day INTEGER NOT NULL CHECK(day BETWEEN 1 AND 23),
            name TEXT NOT NULL,
            address TEXT NOT NULL DEFAULT \'\',
            street TEXT NOT NULL,
            house_number TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT NULL,
            publication_consent INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE(location, day)
        )');
        $this->db->exec('CREATE TABLE IF NOT EXISTS location_settings (
            location TEXT PRIMARY KEY,
            registration_locked INTEGER NOT NULL DEFAULT 0 CHECK(registration_locked IN (0, 1))
        )');
        $columns = $this->db->query('PRAGMA table_info(registrations)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('address', $columns, true)) {
            $this->db->exec("ALTER TABLE registrations ADD COLUMN address TEXT NOT NULL DEFAULT ''");
            $this->db->exec("UPDATE registrations SET address = trim(street || ' ' || house_number) WHERE address = ''");
        }
    }
}

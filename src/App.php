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
        return (string)($this->env[$key] ?? getenv($key) ?: $default ?? '');
    }

    public function locations(): array
    {
        return [
            'nebringen' => 'Nebringen',
            'oeschelbronn' => 'Öschelbronn',
            'tailfingen' => 'Tailfingen',
        ];
    }

    public function locationName(string $slug): ?string { return $this->locations()[$slug] ?? null; }

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
        $sql = 'INSERT INTO registrations (location, day, name, street, house_number, phone, email, publication_consent, created_at, updated_at)
                VALUES (:location,:day,:name,:street,:house_number,:phone,:email,:consent,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
                ON CONFLICT(location,day) DO UPDATE SET name=excluded.name, street=excluded.street, house_number=excluded.house_number,
                phone=excluded.phone, email=excluded.email, publication_consent=excluded.publication_consent, updated_at=CURRENT_TIMESTAMP';
        $s = $this->db->prepare($sql);
        $s->execute([
            ':location'=>$location, ':day'=>$day, ':name'=>$data['name'], ':street'=>$data['street'],
            ':house_number'=>$data['house_number'], ':phone'=>$data['phone'], ':email'=>$data['email'] ?: null,
            ':consent'=>!empty($data['publication_consent']) ? 1 : 0,
        ]);
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
        $file = $this->root . '/.env'; $out=[];
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
            street TEXT NOT NULL,
            house_number TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT NULL,
            publication_consent INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE(location, day)
        )');
    }
}

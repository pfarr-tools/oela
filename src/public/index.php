<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/Validation.php';

if (PHP_SAPI === 'cli-server') {
    $requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if ($requestedPath !== '/' && is_file(__DIR__ . $requestedPath)) {
        return false;
    }
}

use Advent\App;
use function Advent\validateRegistration;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$app = new App(dirname(__DIR__));
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$parts = $path === '' ? [] : explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function h(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url, true, 303); exit; }
function abortPage(int $code, string $message): never { http_response_code($code); echo '<!doctype html><meta charset="utf-8"><title>Fehler</title><p>'.h($message).'</p>'; exit; }
function render(string $template, array $vars=[]): never {
    extract($vars); ob_start(); require dirname(__DIR__).'/templates/'.$template.'.php'; $content=ob_get_clean();
    require dirname(__DIR__).'/templates/layout.php'; exit;
}
// Startseite: Auswahl der drei Orte
if (!$parts) render('home', ['app'=>$app, 'title'=>'Lebendiger Adventskalender']);

// Öffentlicher HTML-Snippet für TYPO3
if (($parts[0]??'') === 'embed' && count($parts)===2) {
    $loc=$parts[1]; $name=$app->locationName($loc); if(!$name) abortPage(404,'Ort nicht gefunden.');
    header('Content-Type: text/html; charset=utf-8');
    header('Access-Control-Allow-Origin: '.$app->env('EMBED_ALLOWED_ORIGIN','*'));
    header('Cache-Control: no-cache, max-age=0, must-revalidate');
    $regs=$app->registrations($loc);
    echo '<div class="lebendiger-advent-termine" data-ort="'.h($loc).'">';
    $shown=0;
    foreach($regs as $day=>$r) if((int)$r['publication_consent']===1){
        echo '<div class="lebendiger-advent-termin"><strong>'.sprintf('%02d.12.', $day).'</strong> '.h($r['name']).', '.h($r['address']).'</div>';
        $shown++;
    }
    if(!$shown) echo '<p>Noch keine Termine veröffentlicht.</p>';
    echo '</div>'; exit;
}

$loc=$parts[0]??''; $locationName=$app->locationName($loc); if(!$locationName) abortPage(404,'Ort nicht gefunden.');

// Admin
if (($parts[1]??'') === 'admin') {
    $sig=(string)($_GET['sig']??$_POST['sig']??''); if(!$app->validAdminSignature($loc,$sig)) abortPage(403,'Ungültiger Verwaltungslink.');
    $action=$parts[2]??'';
    if($action==='export' && $method==='GET'){
        $regs=$app->registrations($loc); $sheet=(new Spreadsheet())->getActiveSheet(); $sheet->setTitle($locationName);
        $sheet->fromArray(['Datum','Wochentag','Name','Ortsangabe','Telefon','E-Mail','Veröffentlichung'], null, 'A1');
        $row=2; foreach($regs as $day=>$r){ $sheet->fromArray([sprintf('%02d.12.',$day),$app->weekdayShort((int)$day),$r['name'],$r['address'],$r['phone'],$r['email']??'',(int)$r['publication_consent']===1?'Ja':'Nein'],null,'A'.$row++); }
        foreach(range('A','G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $filename = sprintf('%d Adventskalender %s.xlsx', (int)date('Y'), $locationName);
        $quotedFilename = addcslashes($filename, "\\\"");
        header('Content-Disposition: attachment; filename="'.$quotedFilename.'"; filename*=UTF-8\'\''.rawurlencode($filename));
        header('Cache-Control: max-age=0');
        (new Xlsx($sheet->getParent()))->save('php://output'); exit;
    }
    if($action==='delete-all' && $method==='POST'){
        if(!$app->checkCsrf($_POST['_csrf']??null)) abortPage(419,'Sitzung abgelaufen.'); $app->deleteAll($loc); redirect('/'.$loc.'/admin?sig='.rawurlencode($sig));
    }
    if($action==='edit' && isset($parts[3])){
        $day=(int)$parts[3]; if($day<1||$day>23) abortPage(404,'Ungültiger Tag.'); $existing=$app->registration($loc,$day);
        $availableDays = array_values(array_filter(range(1, 23), fn(int $candidate): bool => $app->dayAvailable($loc, $candidate, $day)));
        if($method==='POST'){
            if(!$app->checkCsrf($_POST['_csrf']??null)) abortPage(419,'Sitzung abgelaufen.');
            $targetDay=(int)($_POST['day']??$day);
            [$data,$errors]=validateRegistration($_POST);
            if ($targetDay < 1 || $targetDay > 23) {
                $errors['day']='Bitte wählen Sie einen gültigen Tag.';
            } elseif (!$app->dayAvailable($loc, $targetDay, $day)) {
                $errors['day']='Dieser Tag ist inzwischen bereits vergeben.';
            }
            if(!$errors){
                try {
                    $app->saveAdmin($loc,$day,$targetDay,$data);
                    redirect('/'.$loc.'/admin?sig='.rawurlencode($sig));
                } catch (Throwable $exception) {
                    if ($exception->getMessage() === 'day_taken' || str_contains($exception->getMessage(), 'UNIQUE')) {
                        $errors['day']='Dieser Tag ist inzwischen bereits vergeben.';
                    } else {
                        throw $exception;
                    }
                }
            }
            $availableDays = array_values(array_filter(range(1, 23), fn(int $candidate): bool => $app->dayAvailable($loc, $candidate, $day)));
            render('form',['app'=>$app,'title'=>'Termin bearbeiten','location'=>$loc,'locationName'=>$locationName,'day'=>$targetDay,'data'=>$data,'errors'=>$errors,'admin'=>true,'sig'=>$sig,'availableDays'=>$availableDays]);
        }
        $data=$existing?:['name'=>'','address'=>'','phone'=>'','email'=>'','publication_consent'=>1];
        render('form',['app'=>$app,'title'=>'Termin bearbeiten','location'=>$loc,'locationName'=>$locationName,'day'=>$day,'data'=>$data,'errors'=>[],'admin'=>true,'sig'=>$sig,'availableDays'=>$availableDays]);
    }
    if($action==='delete' && isset($parts[3]) && $method==='POST'){
        if(!$app->checkCsrf($_POST['_csrf']??null)) abortPage(419,'Sitzung abgelaufen.'); $day=(int)$parts[3]; if($day>=1&&$day<=23)$app->delete($loc,$day); redirect('/'.$loc.'/admin?sig='.rawurlencode($sig));
    }
    render('admin',['app'=>$app,'title'=>'Verwaltung – '.$locationName,'location'=>$loc,'locationName'=>$locationName,'regs'=>$app->registrations($loc),'sig'=>$sig]);
}

// Öffentlicher Kalender
if(count($parts)===1) render('calendar',['app'=>$app,'title'=>'Lebendiger Adventskalender – '.$locationName,'location'=>$loc,'locationName'=>$locationName,'regs'=>$app->registrations($loc)]);

// Öffentliche Anmeldung
if(count($parts)===2 && ctype_digit($parts[1])){
    $day=(int)$parts[1]; if($day<1||$day>23) abortPage(404,'Ungültiger Tag.');
    if($app->registration($loc,$day)) abortPage(409,'Dieser Termin ist inzwischen bereits vergeben.');
    $data=['name'=>'','address'=>'','phone'=>'','email'=>'','publication_consent'=>0]; $errors=[];
    if($method==='POST'){
        if(!$app->checkCsrf($_POST['_csrf']??null)) abortPage(419,'Sitzung abgelaufen.'); [$data,$errors]=validateRegistration($_POST);
        if(!$errors){
            try{$app->save($loc,$day,$data);}catch(Throwable $e){ if(str_contains($e->getMessage(),'UNIQUE')) abortPage(409,'Dieser Termin wurde gerade von jemand anderem vergeben.'); throw $e; }
            render('success',['app'=>$app,'title'=>'Vielen Dank','location'=>$loc,'locationName'=>$locationName,'day'=>$day]);
        }
    }
    render('form',['app'=>$app,'title'=>sprintf('%02d. Dezember – %s',$day,$locationName),'location'=>$loc,'locationName'=>$locationName,'day'=>$day,'data'=>$data,'errors'=>$errors,'admin'=>false,'sig'=>'']);
}
abortPage(404,'Seite nicht gefunden.');

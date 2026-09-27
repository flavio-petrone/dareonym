<?php

declare(strict_types=1);
const ROOT = __DIR__ . '/..';
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/storage/logs/php.log');
date_default_timezone_set('Europe/Rome');

function env(string $key, mixed $default = null): mixed
{
    $v = getenv($key);
    return $v === false ? $default : $v;
}
function config(): array
{
    static $config;
    if ($config !== null) {
        return $config;
    }
    $config = is_file(ROOT . '/config/local.php') ? require ROOT . '/config/local.php' : [];
    foreach (['DB_DRIVER' => 'driver','DB_HOST' => 'host','DB_PORT' => 'port','DB_NAME' => 'database','DB_USER' => 'username','DB_PASSWORD' => 'password','APP_KEY' => 'key'] as $env => $field) {
        if (getenv($env) !== false) {
            $config[$field] = getenv($env);
        }
    }
    return $config;
}
function db(): PDO
{
    static $db;
    return $db ??= connect_database(config());
}
function connect_database(array $c): PDO
{
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        $dsn = 'sqlite:' . ($c['database'] ?? ROOT . '/storage/local.sqlite');
    } else {
        foreach (['host','port','database'] as $k) {
            if (!isset($c[$k]) || preg_match('/[;\x00-\x20]/', (string)$c[$k])) {
                throw new RuntimeException('Invalid database configuration');
            }
        }
        if (!ctype_digit((string)$c['port']) || (int)$c['port'] < 1 || (int)$c['port'] > 65535) {
            throw new RuntimeException('Invalid port');
        }
        $dsn = 'mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'].';charset=utf8mb4';
    }
    $db = new PDO($dsn, $c['username'] ?? null, $c['password'] ?? null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES => false]);
    if (($c['driver'] ?? '') === 'sqlite') {
        $db->exec('PRAGMA foreign_keys=ON');
        $db->exec('PRAGMA busy_timeout=5000');
    }
    return $db;
}
function sql(string $query, array $params = []): PDOStatement
{
    $s = db()->prepare($query);
    $s->execute($params);
    return $s;
}
function one(string $query, array $params = []): ?array
{
    return sql($query, $params)->fetch() ?: null;
}
function all(string $query, array $params = []): array
{
    return sql($query, $params)->fetchAll();
}
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function url(string $page = 'dashboard', array $params = []): string
{
    return 'index.php?'.http_build_query(['page' => $page] + $params);
}
function go(string $page = 'dashboard', array $params = []): never
{
    header('Location: '.url($page, $params), true, 303);
    exit;
}
function abort_request(int $code, string $message): never
{
    http_response_code($code);
    throw new HttpError($message, $code);
}
class HttpError extends RuntimeException
{
}
function flash(string $message, string $kind = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message,'kind' => $kind];
}
function csrf(): string
{
    return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">';
}
function field(string $name, int $max = 255, bool $required = true): string
{
    $v = $_POST[$name] ?? '';
    if (!is_string($v) || strlen($v) > $max || ($required && trim($v) === '')) {
        abort_request(422, 'Controlla il campo «'.str_replace('_', ' ', $name).'».');
    }
    return str_contains($name, 'password') ? $v : trim($v);
}
function id_field(string $name): int
{
    $v = $_POST[$name] ?? null;
    if (!is_scalar($v) || !ctype_digit((string)$v) || (int)$v < 1) {
        abort_request(422, 'Selezione non valida.');
    }return (int)$v;
}
function password_hash_new(string $password): string
{
    if (strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
        abort_request(422, 'Scegli una password di almeno 12 caratteri e massimo 72 byte.');
    }
    return password_hash($password, PASSWORD_DEFAULT);
}
function email_field(): string
{
    $v = strtolower(field('email', 190));
    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
        abort_request(422, 'Inserisci un indirizzo email valido.');
    }return $v;
}
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }return $user = isset($_SESSION['uid']) ? one('SELECT id,name,email,role,active,auth_version FROM d2_users WHERE id=? AND active=1 AND auth_version=?', [$_SESSION['uid'],$_SESSION['auth_version'] ?? 0]) : null;
}
function require_user(): array
{
    $u = current_user();
    if (!$u) {
        go('login');
    }return $u;
}
function require_admin(): array
{
    $u = require_user();
    if ($u['role'] !== 'admin') {
        abort_request(403, 'Questa sezione è riservata agli amministratori.');
    }return $u;
}
function manage_group(int $id): array
{
    $u = require_user();
    $g = one('SELECT * FROM d2_groups WHERE id=?', [$id]);
    if (!$g || ($u['role'] !== 'admin' && ($u['role'] !== 'creator' || (int)$g['creator_id'] !== (int)$u['id']))) {
        abort_request(404, 'Gruppo non disponibile.');
    }return $g;
}
function visible_group(int $id): array
{
    $u = require_user();
    if ($u['role'] !== 'user') {
        return manage_group($id);
    }
    $g = one('SELECT g.* FROM d2_groups g JOIN d2_memberships m ON m.group_id=g.id WHERE g.id=? AND m.user_id=?', [$id,$u['id']]);
    if (!$g) {
        abort_request(404, 'Gruppo non disponibile.');
    }return $g;
}
function challenge(int $id): array
{
    $c = one('SELECT c.*,g.name group_name,g.creator_id FROM d2_challenges c JOIN d2_groups g ON g.id=c.group_id WHERE c.id=?', [$id]);
    if (!$c) {
        abort_request(404, 'Sfida non disponibile.');
    }visible_group((int)$c['group_id']);
    return $c;
}
function is_manager(): bool
{
    return current_user() && current_user()['role'] !== 'user';
}
function audit(string $event, string $entity): void
{
    sql('INSERT INTO d2_events (actor_id,event,entity,created_at) VALUES (?,?,?,?)', [current_user()['id'] ?? null,$event,$entity,date('Y-m-d H:i:s')]);
}
function nice_date(?string $date): string
{
    return $date ? date('d/m/Y', strtotime($date)) : 'Senza scadenza';
}
function status_label(string $status): string
{
    return ['pending' => 'Da valutare','approved' => 'Approvata','rejected' => 'Da riprovare','open' => 'Disponibile','closed' => 'Conclusa'][$status] ?? $status;
}
function badge(string $status): string
{
    return '<span class="badge '.e($status).'">'.e(status_label($status)).'</span>';
}
function group_scope(string $alias = 'g'): array
{
    $u = require_user();
    if ($u['role'] === 'admin') {
        return ['1=1',[]];
    }
    if ($u['role'] === 'creator') {
        return ["$alias.creator_id=?",[$u['id']]];
    }
    return ["EXISTS (SELECT 1 FROM d2_memberships m WHERE m.group_id=$alias.id AND m.user_id=?)",[$u['id']]];
}
function upload_proof(array $file, bool $replace = false): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
        abort_request(422, 'Scegli un’immagine JPG o PNG, fino a 3 MB.');
    }
    if (filesize($file['tmp_name']) > 3 * 1024 * 1024) {
        abort_request(422, 'L’immagine supera il limite di 3 MB.');
    }
    $info = @getimagesize($file['tmp_name']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!$info || !in_array($mime, ['image/jpeg','image/png'], true) || $info['mime'] !== $mime || $info[0] * $info[1] > 12000000) {
        abort_request(422, 'Formato non valido: usa JPG o PNG fino a 12 megapixel.');
    }
    $count = one('SELECT COUNT(*) n FROM d2_submissions WHERE user_id=?', [require_user()['id']]);
    if ((int)$count['n'] >= 100 && !$replace) {
        abort_request(422, 'Hai raggiunto il limite di 100 prove. Contatta l’amministratore.');
    }
    // Decode/re-encode to discard metadata and appended content. GD is a requirement.
    $image = $mime === 'image/png' ? @imagecreatefrompng($file['tmp_name']) : @imagecreatefromjpeg($file['tmp_name']);
    if (!$image) {
        abort_request(422, 'L’immagine non può essere letta.');
    }
    $name = bin2hex(random_bytes(24)).'.jpg';
    $path = ROOT.'/storage/uploads/'.$name;
    $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
    if (!imagejpeg($canvas, $path, 85)) {
        throw new RuntimeException('Image write failed');
    }
    if (filesize($path) > 3 * 1024 * 1024) {
        unlink($path);
        abort_request(422, 'La foto elaborata è troppo grande. Riducine la risoluzione e riprova.');
    }
    chmod($path, 0600);
    imagedestroy($image);
    imagedestroy($canvas);
    return $name;
}
function delete_proof(?string $name): void
{
    if ($name && preg_match('/^[a-f0-9]{48}\.jpg$/D', $name)) {
        @unlink(ROOT.'/storage/uploads/'.$name);
    }
}

if (PHP_SAPI !== 'cli') {
    ob_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('dareonym2');
    session_set_cookie_params(['httponly' => true,'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),'samesite' => 'Lax','path' => '/']);
    session_start();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    set_exception_handler(function (Throwable $error): void {
        if (db_transaction_active()) {
            db()->rollBack();
        }
        if (ob_get_level()) {
            ob_clean();
        }
        $code = $error instanceof HttpError ? $error->getCode() : 500;
        http_response_code($code);
        $ref = bin2hex(random_bytes(4));
        if (!($error instanceof HttpError)) {
            error_log('Dareonym '.$ref.' '.get_class($error).' '.basename($error->getFile()).':'.$error->getLine());
        }
        $message = $error instanceof HttpError ? $error->getMessage() : 'Non siamo riusciti a completare l’operazione. Riferimento: '.$ref;
        echo '<!doctype html><html lang="it"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/app.css"><title>Operazione interrotta · Dareonym</title><body class="auth-body"><main class="auth-card"><a class="brand" href="index.php">D<span>•</span> Dareonym</a><h1>'.($code === 500 ? 'Qualcosa non ha funzionato' : 'Un momento').'</h1><p>'.e($message).'</p><p>Correggi la richiesta o torna alla pagina precedente. Se il problema continua, comunica il riferimento all’amministratore.</p><a class="button" href="index.php">Torna a Dareonym</a></main></body></html>';
    });
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf']))) {
        abort_request(403, 'La sessione del modulo è scaduta. Ricarica la pagina e riprova.');
    }
}
function db_transaction_active(): bool
{
    try {
        return !empty(config()) && db()->inTransaction();
    } catch (Throwable) {
        return false;
    }
}

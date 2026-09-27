<?php

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path === '/') {
    $path = '/index.php';
}
$real = realpath(__DIR__.$path);
if (!$real || !str_starts_with($real, __DIR__.DIRECTORY_SEPARATOR) || !is_file($real)) {
    http_response_code(404);
    exit('Non trovato');
}
$rel = substr($real, strlen(__DIR__) + 1);
if (preg_match('~(^|/)(\.|app/|config/|storage/|database/|scripts/|tests/|docs/)~', $rel) || !in_array(pathinfo($real, PATHINFO_EXTENSION), ['php','css','js','svg','png','jpg'], true) || $rel === 'router.php') {
    http_response_code(403);
    exit('Accesso negato');
}
if (str_ends_with($real, '.php')) {
    require $real;
    return true;
}return false;

<?php

if (PHP_VERSION_ID < 80200) {
    http_response_code(503);
    exit('Dareonym 2 richiede PHP 8.2 o successivo. Seleziona una versione compatibile nel pannello del tuo hosting.');
}
$requiredExtensions = ['pdo_mysql', 'fileinfo', 'gd', 'mbstring'];
$missingExtensions = array_filter($requiredExtensions, function ($name) {
    return !extension_loaded($name);
});
if ($missingExtensions) {
    http_response_code(503);
    exit('Abilita queste estensioni PHP nel tuo hosting: '.htmlspecialchars(implode(', ', $missingExtensions), ENT_QUOTES, 'UTF-8'));
}
require __DIR__.'/app/core.php';
if (!config()) {
    require __DIR__.'/app/setup.php';
    setup_page();
}
if (!isset(config()['key']) || strlen(config()['key']) < 32) {
    abort_request(503, 'La configurazione privata è incompleta.');
}
require __DIR__.'/app/actions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_action();
}
$page = $_GET['page'] ?? 'dashboard';
if (!is_string($page)) {
    abort_request(404, 'Pagina non disponibile.');
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['page']) && !current_user()) {
    header('Location: scopri.php', true, 303);
    exit;
}
if ($page === 'login' && current_user()) {
    go();
}
if ($page !== 'login') {
    require_user();
}
require __DIR__.'/app/views.php';
render_page($page);

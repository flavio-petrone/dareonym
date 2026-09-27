<?php

if (PHP_VERSION_ID < 80200) {
    http_response_code(503);
    exit('Richiesto PHP 8.2 o successivo.');
}
require __DIR__.'/app/core.php';
$u = require_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    abort_request(404, 'Immagine non disponibile.');
}
$s = one('SELECT s.*,c.group_id FROM d2_submissions s JOIN d2_challenges c ON c.id=s.challenge_id WHERE s.id=?', [$id]);
if (!$s) {
    abort_request(404, 'Immagine non disponibile.');
}
if ($u['role'] === 'user') {
    if ((int)$s['user_id'] !== (int)$u['id']) {
        abort_request(404, 'Immagine non disponibile.');
    }
} else {
    manage_group((int)$s['group_id']);
}
if (!preg_match('/^[a-f0-9]{48}\.jpg$/D', $s['proof'])) {
    abort_request(404, 'Immagine non disponibile.');
}
$path = ROOT.'/storage/uploads/'.$s['proof'];
if (!is_file($path) || is_link($path)) {
    abort_request(404, 'Immagine non disponibile.');
}
header('Content-Type: image/jpeg');
header('Content-Length: '.filesize($path));
header("Content-Security-Policy: default-src 'none'; sandbox");
readfile($path);

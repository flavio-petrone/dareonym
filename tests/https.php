<?php
require __DIR__.'/../app/core.php';
$cases = [
    [[], false, false],
    [['HTTPS'=>'on'], false, true],
    [['HTTPS'=>'1'], false, true],
    [['HTTPS'=>'off'], false, false],
    [['HTTP_X_FORWARDED_PROTO'=>'https'], false, false],
    [['HTTP_X_FORWARDED_PROTO'=>'https'], true, true],
    [['HTTPS'=>'off','HTTP_X_FORWARDED_PROTO'=>'https'], true, true],
    [['HTTP_X_FORWARDED_PROTO'=>'http'], true, false],
    [['HTTP_X_FORWARDED_PROTO'=>'https,http'], true, false],
    [['HTTP_X_FORWARDED_PROTO'=>'http,https'], true, false],
    [['HTTP_X_FORWARDED_SSL'=>'on'], true, false],
    [[], true, false],
];
foreach ($cases as $i => [$server, $trust, $expected]) {
    if (request_is_https($server, $trust) !== $expected) {
        throw new RuntimeException('HTTPS case failed: '.$i);
    }
}
echo count($cases)." HTTPS checks passed.\n";

$redirects = [
    [['HTTP_HOST'=>'example.test','REQUEST_URI'=>'/dareonym2/index.php?page=login'], 'https://example.test/dareonym2/index.php?page=login'],
    [['HTTP_HOST'=>'example.test:80','REQUEST_URI'=>'/'], 'https://example.test/'],
    [['HTTP_HOST'=>'example.test:8443','REQUEST_URI'=>'/'], 'https://example.test:8443/'],
    [['HTTP_HOST'=>"evil.test\r\nX-Test: yes"], null],
    [['HTTP_HOST'=>'good.test@evil.test'], null],
    [['HTTP_HOST'=>'example.test','REQUEST_URI'=>"/\r\nX-Test: yes"], null],
    [['HTTP_HOST'=>'example.test','REQUEST_URI'=>'https://evil.test'], null],
    [[], null],
];
foreach ($redirects as $i => [$server, $expected]) {
    if (https_url($server) !== $expected) {
        throw new RuntimeException('HTTPS redirect case failed: '.$i);
    }
}
echo count($redirects)." HTTPS redirect checks passed.\n";

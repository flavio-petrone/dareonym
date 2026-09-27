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

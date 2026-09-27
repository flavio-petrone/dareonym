<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
if (is_file($root.'/config/local.php') || is_file($root.'/config/install.php')) {
    fwrite(STDERR, "Esiste già una configurazione o un codice di installazione. Nessun file modificato.\n");
    exit(1);
}
$token = bin2hex(random_bytes(24));
file_put_contents($root.'/config/install.php', "<?php\nreturn ".var_export($token, true).";\n");
chmod($root.'/config/install.php', 0600);
echo "Codice privato per l’installazione: ".$token."\nCarica config/install.php sul sito e usa il codice nel modulo iniziale. Non pubblicare questo file su GitHub.\n";

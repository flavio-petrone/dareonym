<?php
require_once __DIR__.'/schema.php';
function setup_page(): never
{
    if (config()) {
        abort_request(403, 'L’installazione è già configurata. Non è possibile ricreare l’amministratore.');
    }
    $secret = is_file(ROOT.'/config/install.php') ? require ROOT.'/config/install.php' : null;
    $ready = is_string($secret) && strlen($secret) >= 24 && $secret !== 'SOSTITUISCI_CON_UN_CODICE_CASUALE';
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (PHP_SAPI !== 'cli-server' && !request_is_https()) {
            abort_request(400, 'Apri il sito tramite HTTPS prima di installare Dareonym.');
        }
        if (!$ready || !hash_equals($secret, field('install_code', 200))) {
            abort_request(403, 'Codice di installazione non valido.');
        }
        $email = email_field();
        $name = field('name', 80);
        $hash = password_hash_new(field('password', 72));
        $c = ['driver' => 'mysql','host' => field('db_host', 100),'port' => field('db_port', 5),'database' => field('db_name', 100),'username' => field('db_user', 100, false),'password' => field('db_password', 200, false),'key' => bin2hex(random_bytes(32))];
        // A lock prevents two simultaneous first-admin installations.
        $lock = fopen(ROOT.'/storage/install.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            abort_request(409, 'Un’installazione è già in corso.');
        }
        if (is_file(ROOT.'/config/local.php')) {
            abort_request(409, 'Installazione già completata.');
        }
        try {
            if (!is_writable(ROOT.'/config') || !is_writable(ROOT.'/storage/uploads')) {
                throw new RuntimeException('Directories not writable');
            }
            $pdo = connect_database($c);
            $existing = $pdo->query("SHOW TABLES LIKE 'd2\\_%'")->fetchAll();
            if ($existing) {
                abort_request(409, 'Esistono già tabelle Dareonym 2. Non sono state sovrascritte. Controlla il database scelto.');
            }
            install_schema($pdo);
            $pdo->prepare('INSERT INTO d2_users (name,email,password,role,created_at) VALUES (?,?,?,?,?)')->execute([$name,$email,$hash,'admin',date('Y-m-d H:i:s')]);
            $content = "<?php\n// Private configuration. Never commit this file.\nreturn ".var_export($c, true).";\n";
            $temp = ROOT.'/config/.local-'.bin2hex(random_bytes(8)).'.php';
            if (file_put_contents($temp, $content, LOCK_EX) === false) {
                throw new RuntimeException('Configuration write failed');
            }chmod($temp, 0600);
            if (!rename($temp, ROOT.'/config/local.php')) {
                unlink($temp);
                throw new RuntimeException('Configuration publish failed');
            }
            @unlink(ROOT.'/config/install.php');
            flash('Dareonym è pronto. Accedi con l’amministratore che hai appena creato.');
            go('login');
        } catch (HttpError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('Setup: '.get_class($e).' line '.$e->getLine());
            $error = 'Installazione non completata. Controlla i parametri, i permessi di scrittura e la disponibilità di InnoDB. Se sono state create tabelle d2_, chiedi assistenza prima di riprovare: non cancellare le tabelle del vecchio sito.';
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
    ?><!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Benvenuto · Dareonym</title><link rel="stylesheet" href="assets/app.css"></head><body class="auth-body"><main class="setup-card"><a class="brand" href="index.php">D<span>•</span> Dareonym</a><p class="eyebrow">IL PRIMO PASSO</p><h1>Uno spazio per<br>fare progressi, insieme.</h1><p>Collega il database e crea il tuo amministratore. Nessuna tabella del vecchio progetto verrà sostituita.</p>
    <?php if ($error):?><div class="notice error"><?=e($error)?></div><?php endif;?>
    <?php if (!$ready):?><div class="notice">L’installazione è protetta. Copia <code>config/install.example.php</code> in <code>config/install.php</code> e sostituisci il segnaposto con un codice casuale di almeno 24 caratteri. Poi ricarica questa pagina. Il codice va custodito privatamente.</div>
    <?php else:?>
    <form method="post" class="stack"><?=csrf()?>
    <label>Codice di installazione<input name="install_code" type="password" required autocomplete="off"></label>
    <h2>Il database</h2><div class="form-grid"><label>Host<input name="db_host" value="localhost" required></label><label>Porta<input name="db_port" value="3306" required inputmode="numeric"></label><label>Nome del database<input name="db_name" placeholder="my_nomeaccount" required></label><label>Utente database<input name="db_user" autocomplete="off"></label></div><label>Password database<input name="db_password" type="password" autocomplete="off"><small>Usa i parametri indicati dal tuo hosting. Questo campo può essere vuoto se previsto dal servizio.</small></label>
    <h2>Il tuo account amministratore</h2><label>Nome pubblico<input name="name" maxlength="80" required autocomplete="name"></label><label>Email<input name="email" type="email" required autocomplete="email"></label><label>Password<input name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password"><small>Almeno 12 caratteri. Non riutilizzare la password del vecchio sito.</small></label><button class="button" type="submit">Crea il tuo spazio <span>→</span></button></form>
    <?php endif;?><p class="fine">Prima di iniziare: PHP 8.2+, PDO MySQL, GD e Fileinfo. Directory config e storage scrivibili. Connessione HTTPS per l’installazione online.</p></main></body></html><?php exit;
}

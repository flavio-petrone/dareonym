<?php

// Local-only sample dataset. Run on a disposable copy; never upload its database/config.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__).'/app/core.php';
require dirname(__DIR__).'/app/schema.php';
if (config()) {
    fwrite(STDERR, "Configurazione esistente: nessun dato modificato. Usa una copia vuota.\n");
    exit(1);
}
$c = ['driver' => 'sqlite','database' => realpath(ROOT.'/storage').'/demo.sqlite','key' => bin2hex(random_bytes(32)),'demo' => true];
$testConfig = getenv('DAREONYM_DEMO_MYSQL_CONFIG');
if ($testConfig !== false) {
    $c = json_decode(file_get_contents($testConfig), true, 512, JSON_THROW_ON_ERROR);
    if (($c['driver'] ?? '') !== 'mysql' || !str_starts_with($c['database'] ?? '', 'dareonym_test_')) {
        throw new RuntimeException('Not a test database');
    }
    $c['key'] = bin2hex(random_bytes(32));
    $c['demo'] = true;
}
if (is_file($c['database'])) {
    fwrite(STDERR, "Database demo già presente.\n");
    exit(1);
}
$pdo = connect_database($c);
install_schema($pdo);
$password = 'Local-demo-2026!';
$hash = password_hash($password, PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');
$users = [['Alex','admin@example.test','admin'],['Sofia','creator@example.test','creator'],['Luca','user@example.test','user'],['Giulia','giulia@example.test','user'],['Marco','marco@example.test','user'],['Elena','othercreator@example.test','creator'],['Anna','other@example.test','user']];
foreach ($users as [$name,$email,$role]) {
    $pdo->prepare('INSERT INTO d2_users (name,email,password,role,created_at) VALUES (?,?,?,?,?)')->execute([$name,$email,$hash,$role,$now]);
}
foreach ([['Piccoli passi','Benessere, abitudini e piccoli gesti che fanno stare bene.',2],['Idee in movimento','Uno spazio per imparare qualcosa e costruire insieme.',2],['Fuori dagli schemi','Un altro gruppo, un altro percorso.',6]] as [$name,$description,$creator]) {
    $pdo->prepare('INSERT INTO d2_groups (name,description,creator_id,created_at) VALUES (?,?,?,?)')->execute([$name,$description,$creator,$now]);
}
foreach ([[1,3],[1,4],[1,5],[2,3],[2,4],[3,7]] as $pair) {
    $pdo->prepare('INSERT INTO d2_memberships (group_id,user_id) VALUES (?,?)')->execute($pair);
}
$challenges = [
[1,'Venti minuti, un po’ di aria nuova','Regalati una passeggiata di almeno 20 minuti. Lascia il telefono in tasca e nota qualcosa che di solito ti sfugge. Invia una foto di un dettaglio del percorso e racconta come ti sei sentito.',50],
[2,'Impara qualcosa. Poi condividilo.','Dedica mezz’ora a una cosa che non sai ancora fare: un disegno, una ricetta o una nuova tecnica. Mostra il risultato con una foto e racconta una cosa che hai imparato.',80],
[1,'Un piccolo gesto, un grande impatto','Scegli un gesto utile per il tuo ambiente: sistema uno spazio condiviso o prenditi cura di una pianta. Fotografa il risultato senza includere persone riconoscibili.',60],
[3,'Una pausa, tutta per te','Metti in pausa le notifiche per venti minuti e dedica quel tempo a una lettura. Mostra il tuo angolo tranquillo e racconta qualcosa che ti è rimasto.',30]
];
foreach ($challenges as [$gid,$title,$description,$points]) {
    $pdo->prepare('INSERT INTO d2_challenges (group_id,title,description,points,due_date,created_at) VALUES (?,?,?,?,?,?)')->execute([$gid,$title,$description,$points,date('Y-m-d', strtotime('+14 days')),$now]);
}
// Synthetic, clearly labelled proof images for the local demo only.
foreach ([[1,4,'approved',50,'Un piccolo traguardo, ben raccontato.'],[3,5,'pending',0,'']] as [$cid,$uid,$status,$points,$feedback]) {
    $proof = bin2hex(random_bytes(24)).'.jpg';
    $image = imagecreatetruecolor(640, 400);
    $bg = imagecolorallocate($image, 231, 226, 246);
    imagefill($image, 0, 0, $bg);
    $ink = imagecolorallocate($image, 104, 84, 217);
    imagefilledellipse($image, 320, 160, 130, 130, $ink);
    imagestring($image, 5, 244, 270, 'ESEMPIO DEMO', $ink);
    imagestring($image, 3, 180, 300, 'Immagine sintetica - nessun dato reale', $ink);
    imagejpeg($image, ROOT.'/storage/uploads/'.$proof, 85);
    imagedestroy($image);
    $pdo->prepare('INSERT INTO d2_submissions (challenge_id,user_id,note,proof,status,feedback,awarded_points,reviewed_by,submitted_at,reviewed_at) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([$cid,$uid,'Prova dimostrativa: mostra il flusso di valutazione, non un risultato reale.',$proof,$status,$feedback,$points,$status === 'approved' ? 2 : null,$now,$status === 'approved' ? $now : null]);
}
file_put_contents(ROOT.'/config/local.php', "<?php\nreturn ".var_export($c, true).";\n");
chmod(ROOT.'/config/local.php', 0600);
echo "Demo locale creata. Account: admin@example.test / creator@example.test / user@example.test\nPassword solo demo: $password\nAvvio: php -S 127.0.0.1:8000 router.php\n";

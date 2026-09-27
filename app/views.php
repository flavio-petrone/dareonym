<?php
function icon(string $name): string
{
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m1-16a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/>',
        'check' => '<path d="m5 12 4 4L19 6"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/>',
        'trophy' => '<path d="M8 3h8v5c0 5-8 5-8 0zm4 9v6m-4 3h8m-9-2h10M8 5H4v3c0 3 3 4 5 3m7-6h4v3c0 3-3 4-5 3"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'logout' => '<path d="M9 3H4v18h5m4-14 5 5-5 5M8 12h13"/>',
        'spark' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>'
    ];
    return '<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? $paths['target']).'</svg>';
}
function action_input(string $action): string
{
    return csrf().'<input type="hidden" name="action" value="'.e($action).'">';
}
function empty_state(string $title, string $description, string $link = '', string $label = ''): void
{
    echo '<div class="empty"><span class="empty-icon">'.icon('spark').'</span><h3>'.e($title).'</h3><p>'.e($description).'</p>'.($link ? '<a class="button secondary" href="'.e($link).'">'.e($label).'</a>' : '').'</div>';
}
function flash_view(): void
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="notice '.e($f['kind']).'" role="status">'.e($f['message']).'</div>';
    }
}
function page_heading(string $title, string $subtitle, string $button = '', string $href = ''): void
{
    echo '<div class="page-heading"><div><h1>'.e($title).'</h1><p>'.e($subtitle).'</p></div>'.($button ? '<a class="button" href="'.e($href).'">'.icon('plus').e($button).'</a>' : '').'</div>';
}
function render_page(string $page): void
{
    $titles = ['dashboard' => 'Panoramica','challenges' => 'Sfide','challenge' => 'Dettaglio sfida','new-challenge' => 'Nuova sfida','groups' => 'Gruppi','group' => 'Il gruppo','people' => 'Persone','reviews' => 'Da valutare','history' => 'Le mie prove','leaderboard' => 'Classifica','profile' => 'Il tuo profilo','login' => 'Accedi'];
    if (!isset($titles[$page])) {
        abort_request(404, 'Pagina non trovata.');
    }
    if ($page === 'people') {
        require_admin();
    }
    if (in_array($page, ['reviews','new-challenge'], true) && !is_manager()) {
        abort_request(403, 'Questa sezione è riservata ai creator.');
    }
    $u = $page === 'login' ? null : require_user();
    ?><!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#172238"><title><?=e($titles[$page])?> · Dareonym</title><link rel="icon" href="assets/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="assets/app.css"><script src="assets/app.js" defer></script></head>
    <body class="<?=$page === 'login' ? 'login-page' : 'app-page'?>">
    <?php if ($page === 'login'): login_view();
    else:?>
    <a href="#main" class="skip-link">Vai al contenuto</a>
    <aside class="sidebar" id="sidebar"><a class="brand" href="index.php"><span class="brand-symbol">d<span>•</span></span>Dareonym<span class="brand-version">2</span></a><p class="nav-label">IL TUO SPAZIO</p><nav aria-label="Navigazione principale">
    <?php $nav = ['dashboard' => ['home','Panoramica'],'challenges' => ['target','Sfide'],'groups' => ['users','Gruppi']];
        if ($u['role'] === 'user') {
            $nav['history'] = ['check','Le mie prove'];
        } else {
            $nav['reviews'] = ['check','Da valutare'];
        }$nav['leaderboard'] = ['trophy','Classifica'];
        if ($u['role'] === 'admin') {
            $nav['people'] = ['users','Persone'];
        }
        foreach ($nav as $key => [$ico,$label]):$active = $page === $key || ($key === 'challenges' && in_array($page, ['challenge','new-challenge'])) || ($key === 'groups' && $page === 'group');?><a href="<?=e(url($key))?>" class="nav-item <?=$active ? 'active' : ''?>" <?=$active ? 'aria-current="page"' : ''?>><?=icon($ico)?><span><?=e($label)?></span><?=$active ? '<span class="nav-dot"></span>' : ''?></a><?php endforeach;?>
    </nav><div class="sidebar-note"><?=icon('spark')?><strong>Piccoli passi.<br>Risultati condivisi.</strong><p>La prossima sfida è un’occasione per crescere.</p></div><a class="sidebar-profile" href="<?=e(url('profile'))?>"><span class="avatar"><?=e(mb_strtoupper(mb_substr($u['name'], 0, 1)))?></span><span><strong><?=e($u['name'])?></strong><small><?=e(['admin' => 'Amministratore','creator' => 'Creator','user' => 'Partecipante'][$u['role']])?></small></span></a><form method="post" class="logout-form"><?=action_input('logout')?><button class="text-button" type="submit"><?=icon('logout')?> Esci</button></form></aside>
    <div class="workspace"><header class="topbar"><button id="menu-toggle" class="icon-button" aria-label="Apri navigazione" aria-controls="sidebar" aria-expanded="false"><?=icon('menu')?></button><span>Dareonym <span class="breadcrumb">/ <?=e($titles[$page])?></span></span><div class="topbar-right"><span class="today"><?=e(date('d.m.Y'))?></span><span class="live-dot"></span><span><?=!empty(config()['demo']) ? 'Demo locale · dati di esempio' : 'Il tuo prossimo passo'?></span></div></header><main id="main" tabindex="-1">
    <?php if (!empty(config()['demo'])) { echo '<p class="demo-banner">Demo locale · nomi, prove e punteggi dimostrativi</p>'; } flash_view();
        match($page) {
            'dashboard' => dashboard_view(),'challenges' => challenges_view(),'challenge' => challenge_view(),'new-challenge' => new_challenge_view(),'groups' => groups_view(),'group' => group_view(),'people' => people_view(),'reviews' => reviews_view(),'history' => history_view(),'leaderboard' => leaderboard_view(),'profile' => profile_view(),default => null
        };?>
    <footer class="footer"><span>Dareonym · Crescere, insieme.</span><span>Fatto di persone, non solo di punti.</span></footer></main></div>
    <?php endif;?></body></html><?php
}
function login_view(): void
{?>
    <div class="login-story"><a class="brand" href="index.php"><span class="brand-symbol">d<span>•</span></span>Dareonym</a><div class="story-content"><p class="eyebrow">UNA SFIDA ALLA VOLTA</p><h1>Le buone intenzioni <br>diventano<br><em>progressi reali.</em></h1><p>Sfide condivise, piccoli traguardi e un gruppo con cui andare più lontano.</p><div class="orbit-art" aria-hidden="true"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><span class="art-center"><?=icon('target')?></span><span class="art-note art-note-one"><?=icon('check')?> Un passo in più</span><span class="art-note art-note-two"><?=icon('spark')?> Insieme conta di più</span></div></div><p class="story-bottom">Il tuo impegno. La forza del gruppo.</p></div>
    <main class="login-panel"><div class="login-form"><p class="eyebrow">BENTORNATO</p><h2>Riparti da qui.</h2><p>Accedi al tuo spazio e scopri la prossima sfida.</p><?php if (!empty(config()['demo'])) { echo '<p class="demo-banner">Demo locale · nomi, prove e punteggi dimostrativi</p>'; } flash_view();?><form method="post" class="stack"><?=action_input('login')?><label>Email<input name="email" type="email" required maxlength="190" autocomplete="username" placeholder="nome@esempio.it"></label><label>Password<input name="password" type="password" required maxlength="72" autocomplete="current-password" placeholder="La tua password"></label><button class="button wide" type="submit">Entra in Dareonym <?=icon('arrow')?></button></form><p class="login-help">Non hai ancora un account o hai dimenticato la password?<br>Contatta l’amministratore del tuo gruppo.</p><div class="login-footnote"><?=icon('users')?><span>Uno spazio per mettersi in gioco,<br>con persone che fanno il tifo per te.</span></div></div></main>
<?php }
function dashboard_view(): void
{
    $u = require_user();
    [$scope,$params] = group_scope();
    $groups = one("SELECT COUNT(*) n FROM d2_groups g WHERE $scope", $params)['n'];
    $open = one("SELECT COUNT(*) n FROM d2_challenges c JOIN d2_groups g ON g.id=c.group_id WHERE $scope AND c.archived=0 AND (c.due_date IS NULL OR c.due_date>=?)", array_merge($params, [date('Y-m-d')]))['n'];
    $extra = $u['role'] === 'user' ? ' AND s.user_id=?' : '';
    $sp = $u['role'] === 'user' ? array_merge($params, [$u['id']]) : $params;
    $stats = one("SELECT COALESCE(SUM(CASE WHEN s.status='approved' THEN s.awarded_points ELSE 0 END),0) points,COALESCE(SUM(CASE WHEN s.status='pending' THEN 1 ELSE 0 END),0) pending FROM d2_submissions s JOIN d2_challenges c ON c.id=s.challenge_id JOIN d2_groups g ON g.id=c.group_id WHERE $scope $extra", $sp);
    page_heading('Ciao, '.explode(' ', $u['name'])[0].' 👋', 'Ogni piccolo passo merita di essere visto.');
    ?><section class="hero"><div><span class="hero-tag"><?=icon('spark')?> IL PROGRESSO SI COSTRUISCE</span><h2><?=$u['role'] === 'user' ? 'La prossima sfida<br>comincia da te.' : 'Dai spazio<br>ai prossimi traguardi.'?></h2><p><?=$u['role'] === 'user' ? 'Scegli una sfida, mettiti in gioco e condividi il risultato con il tuo gruppo.' : 'Crea sfide che contano e accompagna il tuo gruppo, un risultato alla volta.'?></p><a class="button light" href="<?=e(url($u['role'] === 'user' ? 'challenges' : 'new-challenge'))?>"><?=$u['role'] === 'user' ? 'Esplora le sfide' : 'Crea una sfida'?> <?=icon('arrow')?></a></div><div class="hero-art" aria-hidden="true"><div class="hero-ring outer"></div><div class="hero-ring inner"></div><div class="hero-star"><?=icon('spark')?></div><span class="hero-caption">DARE. DO. GROW.</span></div></section>
    <div class="stats-grid"><?php foreach ([['target','Sfide aperte',$open,'Un’occasione per iniziare'],['users','I tuoi gruppi',$groups,'Il valore di farlo insieme'],['check','Prove in attesa',$stats['pending'],'Ogni impegno conta'],['trophy',$u['role'] === 'user' ? 'I tuoi punti' : 'Punti conquistati',$stats['points'],'Dalle prove approvate']] as [$ico,$label,$value,$note]):?><article class="stat-card"><div class="stat-top"><span><?=e($label)?></span><span class="stat-icon"><?=icon($ico)?></span></div><strong><?=e($value)?></strong><small><?=e($note)?></small></article><?php endforeach;?></div>
    <div class="section-title"><div><p class="eyebrow">FAI IL PROSSIMO PASSO</p><h2>Sfide da scoprire</h2></div><a class="inline-link" href="<?=e(url('challenges'))?>">Vedi tutte <?=icon('arrow')?></a></div>
    <?php $cards = all("SELECT c.*,g.name group_name FROM d2_challenges c JOIN d2_groups g ON g.id=c.group_id WHERE $scope AND c.archived=0 AND (c.due_date IS NULL OR c.due_date>=?) ORDER BY CASE WHEN c.due_date IS NULL THEN 1 ELSE 0 END,c.due_date,c.id DESC LIMIT 3", array_merge($params, [date('Y-m-d')]));
    challenge_cards($cards);
    $recent = all("SELECT s.status,s.submitted_at,u.name,c.title,s.awarded_points FROM d2_submissions s JOIN d2_users u ON u.id=s.user_id JOIN d2_challenges c ON c.id=s.challenge_id JOIN d2_groups g ON g.id=c.group_id WHERE $scope $extra ORDER BY s.submitted_at DESC LIMIT 5", $sp);?>
    <section class="panel activity-panel"><div class="section-title compact"><h2>Gli ultimi passi</h2><span class="muted">Attività recente</span></div><?php if (!$recent):?><p class="muted">Le prime prove inviate appariranno qui. Ogni storia comincia con un primo passo.</p><?php else:foreach ($recent as $r):?><div class="activity-row"><span class="activity-icon"><?=icon($r['status'] === 'approved' ? 'trophy' : 'check')?></span><div><strong><?=e($r['name'])?></strong><p><?=e($r['title'])?></p></div><?=badge($r['status'])?><small><?=nice_date($r['submitted_at'])?></small></div><?php endforeach;endif;?></section>
<?php }
function challenge_cards(array $cards): void
{
    if (!$cards) {
        empty_state('La prima sfida deve ancora arrivare', is_manager() ? 'Crea una sfida per dare al gruppo un obiettivo concreto.' : 'Appena il creator pubblicherà una sfida nel tuo gruppo, la troverai qui.', is_manager() ? url('new-challenge') : '', 'Crea una sfida');
        return;
    }
    ?><div class="challenge-grid"><?php foreach ($cards as $i => $c):$closed = $c['archived'] || ($c['due_date'] && $c['due_date'] < date('Y-m-d'));?><article class="challenge-card"><div class="card-art art-<?=$i % 3?>"><span class="card-art-symbol"><?=icon(['target','spark','users'][$i % 3])?></span><span class="points-pill"><?=icon('trophy')?> <?=e($c['points'])?> pt</span></div><div class="card-content"><div class="card-meta"><span><?=e($c['group_name'])?></span><?=badge($closed ? 'closed' : 'open')?></div><h3><a href="<?=e(url('challenge', ['id' => $c['id']]))?>"><?=e($c['title'])?></a></h3><p class="card-description"><?=e(mb_strimwidth($c['description'], 0, 135, '…'))?></p><div class="card-bottom"><span><?=icon('clock')?> <?=nice_date($c['due_date'])?></span><a class="circle-link" href="<?=e(url('challenge', ['id' => $c['id']]))?>" aria-label="<?=e('Apri '.$c['title'])?>"><?=icon('arrow')?></a></div></div></article><?php endforeach;?></div><?php
}
function challenges_view(): void
{
    page_heading('Le sfide', 'Obiettivi concreti. Piccoli passi. Risultati da condividere.', is_manager() ? 'Nuova sfida' : '', url('new-challenge'));
    [$scope,$params] = group_scope();
    $filter = is_string($_GET['filter'] ?? null) ? $_GET['filter'] : 'open';
    $filter = in_array($filter, ['open','all','closed'], true) ? $filter : 'open';
    $condition = match($filter) {
        'open' => ' AND c.archived=0 AND (c.due_date IS NULL OR c.due_date>=?)','closed' => ' AND (c.archived=1 OR c.due_date<?)',default => ''
    };
    if ($filter !== 'all') {
        $params[] = date('Y-m-d');
    }
    ?><div class="tabs"><?php foreach (['open' => 'Disponibili','all' => 'Tutte','closed' => 'Concluse'] as $key => $label):?><a class="<?=$filter === $key ? 'selected' : ''?>" href="<?=e(url('challenges', ['filter' => $key]))?>"><?=e($label)?></a><?php endforeach;?></div><?php
    challenge_cards(all("SELECT c.*,g.name group_name FROM d2_challenges c JOIN d2_groups g ON g.id=c.group_id WHERE $scope $condition ORDER BY c.id DESC", $params));
}
function new_challenge_view(): void
{
    page_heading('Una nuova sfida', 'Dai al gruppo un obiettivo chiaro e una ragione per provarci.');
    [$scope,$params] = group_scope();
    $groups = all("SELECT g.* FROM d2_groups g WHERE $scope ORDER BY g.name", $params);
    if (!$groups) {
        empty_state('Prima serve un gruppo', 'L’amministratore può creare un gruppo e assegnarlo a un creator.', url('groups'), 'Vai ai gruppi');
        return;
    }
    ?><div class="form-layout"><section class="panel"><form method="post" class="stack"><?=action_input('challenge_create')?><label>Titolo della sfida<input name="title" maxlength="140" placeholder="Es. 20 minuti all’aria aperta" required></label><label>Il gruppo<select name="group_id" required><?php foreach ($groups as $g):?><option value="<?=$g['id']?>" <?=(int)($_GET['group'] ?? 0) === (int)$g['id'] ? 'selected' : ''?>><?=e($g['name'])?></option><?php endforeach;?></select></label><label>Che cosa bisogna fare?<textarea name="description" rows="6" maxlength="5000" placeholder="Spiega l’obiettivo e quale foto inviare come prova. Evita di richiedere volti o dati personali non necessari." required></textarea></label><div class="form-grid"><label>Punti<input name="points" type="number" min="1" max="1000" value="50" required></label><label>Scadenza (facoltativa)<input name="due_date" type="date" min="<?=date('Y-m-d')?>"></label></div><button class="button" type="submit">Pubblica la sfida <?=icon('arrow')?></button></form></section><aside class="tip-card"><span class="tip-icon"><?=icon('spark')?></span><h3>Una buona sfida<br>parte dalla chiarezza.</h3><p>Scegli qualcosa di concreto, accessibile e verificabile. Specifica come mostrare il risultato.</p><p>I punti verranno assegnati soltanto dopo l’approvazione della prova.</p></aside></div><?php
}
function challenge_view(): void
{
    $c = challenge((int)($_GET['id'] ?? 0));
    $u = require_user();
    $closed = $c['archived'] || ($c['due_date'] && $c['due_date'] < date('Y-m-d'));
    ?><a class="back-link" href="<?=e(url('challenges'))?>">← Tutte le sfide</a><?php page_heading($c['title'], $c['group_name']);?>
    <div class="detail-layout"><section class="panel"><div class="detail-meta"><?=badge($closed ? 'closed' : 'open')?><span class="points-text"><?=icon('trophy')?> <?=e($c['points'])?> punti</span><span><?=icon('clock')?> <?=nice_date($c['due_date'])?></span></div><h2>La tua missione</h2><div class="prose"><?=nl2br(e($c['description']))?></div><div class="notice neutral">Una foto e una breve descrizione raccontano il tuo risultato. Il creator approva la prova prima che i punti entrino in classifica.</div>
    <?php if (is_manager()):?><form method="post" class="inline-form"><?=action_input('challenge_toggle')?><input type="hidden" name="id" value="<?=$c['id']?>"><button class="button secondary" type="submit"><?=$c['archived'] ? 'Riapri la sfida' : 'Chiudi le nuove partecipazioni'?></button></form><?php endif;?></section>
    <aside class="panel"><?php if ($u['role'] === 'user'):
        $s = one('SELECT * FROM d2_submissions WHERE challenge_id=? AND user_id=?', [$c['id'],$u['id']]);
        if ($s):?><div class="section-title compact"><h2>Il tuo percorso</h2><?=badge($s['status'])?></div><img class="proof-image" src="image.php?id=<?=$s['id']?>" alt="La prova che hai inviato"><p><?=e($s['note'])?></p><?php if ($s['feedback']):?><div class="feedback"><strong>Il riscontro del creator</strong><p><?=nl2br(e($s['feedback']))?></p></div><?php endif;?><?php if ($s['status'] === 'approved'):?><p class="points-earned">+<?=e($s['awarded_points'])?> punti conquistati</p><?php endif;endif;
        if ((!$s || $s['status'] === 'rejected') && !$closed):?><h2><?=$s ? 'Prova di nuovo' : 'Mostra il tuo risultato'?></h2><form method="post" enctype="multipart/form-data" class="stack"><?=action_input('submit')?><input type="hidden" name="challenge_id" value="<?=$c['id']?>"><label>La tua foto<input type="file" name="proof" accept="image/jpeg,image/png" required><small>JPG o PNG, massimo 3 MB e 12 megapixel. Evita dati personali o volti senza consenso.</small></label><label>Racconta com’è andata<textarea name="note" rows="4" maxlength="2000" required placeholder="Che cosa hai fatto? Qual è stato il tuo piccolo traguardo?"></textarea></label><button class="button wide" type="submit">Invia la prova <?=icon('arrow')?></button></form><?php elseif (!$s):?><h2>La sfida è conclusa</h2><p>Non è più possibile inviare nuove prove. Trova un altro obiettivo nella pagina Sfide.</p><?php endif;
    else:$count = one('SELECT COUNT(*) n FROM d2_submissions WHERE challenge_id=?', [$c['id']])['n'];?><p class="eyebrow">IL GRUPPO SI METTE IN GIOCO</p><span class="big-number"><?=e($count)?></span><h2>prove ricevute</h2><p>Valuta i risultati e lascia un riscontro che aiuti a crescere.</p><a class="button secondary" href="<?=e(url('reviews'))?>">Vai alle valutazioni <?=icon('arrow')?></a><?php endif;?></aside></div><?php
}
function groups_view(): void
{
    page_heading('I gruppi', 'Le persone giuste rendono ogni passo più importante.');
    [$scope,$params] = group_scope();
    $groups = all("SELECT g.*,u.name creator_name,(SELECT COUNT(*) FROM d2_memberships m WHERE m.group_id=g.id) members FROM d2_groups g JOIN d2_users u ON u.id=g.creator_id WHERE $scope ORDER BY g.id DESC", $params);
    if (!$groups) {
        empty_state('Trova il tuo gruppo', current_user()['role'] === 'admin' ? 'Crea il primo gruppo e assegnagli un creator.' : 'L’amministratore o il creator deve ancora aggiungerti a un gruppo.');
    } else { ?><div class="group-grid"><?php foreach ($groups as $g):?><a class="group-card" href="<?=e(url('group', ['id' => $g['id']]))?>"><span class="group-icon"><?=icon('users')?></span><h2><?=e($g['name'])?></h2><p><?=e($g['description'] ?: 'Un gruppo pronto a mettersi in gioco.')?></p><div class="group-bottom"><span><?=e($g['members'])?> partecipanti</span><span><?=e($g['creator_name'])?> · Creator</span></div></a><?php endforeach;?></div><?php }
    if (current_user()['role'] === 'admin'):$creators = all("SELECT id,name FROM d2_users WHERE role='creator' AND active=1 ORDER BY name");?><section class="panel section-space"><h2>Crea un gruppo</h2><?php if (!$creators):?><p>Prima crea un account con ruolo Creator nella sezione <a href="<?=e(url('people'))?>">Persone</a>.</p><?php else:?><form method="post" class="stack"><?=action_input('group_create')?><div class="form-grid"><label>Nome del gruppo<input name="name" maxlength="100" required placeholder="Es. Piccoli passi, grandi idee"></label><label>Creator responsabile<select name="creator_id" required><?php foreach ($creators as $cr):?><option value="<?=$cr['id']?>"><?=e($cr['name'])?></option><?php endforeach;?></select></label></div><label>Descrizione<textarea name="description" maxlength="1500" rows="3" placeholder="Che cosa vi unisce?"></textarea></label><button class="button" type="submit">Crea gruppo <?=icon('plus')?></button></form><?php endif;?></section><?php endif;
}
function group_view(): void
{
    $g = visible_group((int)($_GET['id'] ?? 0));
    page_heading($g['name'], $g['description'], is_manager() ? 'Nuova sfida' : '', url('new-challenge', ['group' => $g['id']]));
    $members = all('SELECT u.id,u.name,u.active FROM d2_memberships m JOIN d2_users u ON u.id=m.user_id WHERE m.group_id=? ORDER BY u.name', [$g['id']]);?>
    <section class="panel"><h2>Le persone del gruppo <span class="count"><?=count($members)?></span></h2><?php if (!$members):?><p class="muted">Il gruppo è pronto ad accogliere i primi partecipanti.</p><?php endif;?>
    <div class="members"><?php foreach ($members as $m):?><div class="member"><span class="avatar pale"><?=e(mb_strtoupper(mb_substr($m['name'], 0, 1)))?></span><span><?=e($m['name'])?><?=$m['active'] ? '' : ' · Inattivo'?></span><?php if (is_manager()):?><form method="post" data-confirm="Rimuovere questa persona dal gruppo? Le prove già inviate saranno conservate."><?=action_input('member_remove')?><input type="hidden" name="group_id" value="<?=$g['id']?>"><input type="hidden" name="user_id" value="<?=$m['id']?>"><button class="text-button danger" type="submit">Rimuovi</button></form><?php endif;?></div><?php endforeach;?></div>
    <?php if (is_manager()):$available = all("SELECT id,name FROM d2_users WHERE role='user' AND active=1 AND NOT EXISTS (SELECT 1 FROM d2_memberships m WHERE m.user_id=d2_users.id AND m.group_id=?) ORDER BY name", [$g['id']]);
        if ($available):?><form method="post" class="inline-form add-member"><?=action_input('member_add')?><input type="hidden" name="group_id" value="<?=$g['id']?>"><label>Aggiungi un partecipante<select name="user_id" required><?php foreach ($available as $m):?><option value="<?=$m['id']?>"><?=e($m['name'])?></option><?php endforeach;?></select></label><button class="button secondary" type="submit">Aggiungi <?=icon('plus')?></button></form><?php else:?><p class="muted">Non ci sono altri partecipanti disponibili. L’amministratore può creare nuovi account dalla sezione Persone.</p><?php endif;endif;?></section>
    <div class="section-title"><h2>Le sfide del gruppo</h2></div><?php challenge_cards(all('SELECT c.*,g.name group_name FROM d2_challenges c JOIN d2_groups g ON g.id=c.group_id WHERE g.id=? ORDER BY c.id DESC', [$g['id']]));
}
function people_view(): void
{
    page_heading('Le persone', 'Gestisci gli accessi e costruisci la tua comunità.');
    $users = all('SELECT id,name,email,role,active FROM d2_users ORDER BY id DESC');?>
    <section class="panel"><div class="table-wrap"><table><thead><tr><th>Persona</th><th>Ruolo</th><th>Stato</th><th>Gestione</th></tr></thead><tbody><?php foreach ($users as $u):?><tr><td><strong><?=e($u['name'])?></strong><small><?=e($u['email'])?></small></td><td><?=e(['admin' => 'Amministratore','creator' => 'Creator','user' => 'Partecipante'][$u['role']])?></td><td><span class="badge <?=$u['active'] ? 'approved' : 'closed'?>"><?=$u['active'] ? 'Attivo' : 'Disattivato'?></span></td><td><details><summary>Gestisci</summary><div class="manage-popover"><form method="post" class="stack"><?=action_input('account_reset')?><input type="hidden" name="id" value="<?=$u['id']?>"><label>Nuova password<input name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password"></label><button class="button secondary small" type="submit">Reimposta password</button></form><?php if ($u['id'] !== current_user()['id']):?><form method="post" class="section-space" data-confirm="Modificare l’accesso di questa persona?"><?=action_input('account_toggle')?><input type="hidden" name="id" value="<?=$u['id']?>"><button class="text-button danger" type="submit"><?=$u['active'] ? 'Disattiva account' : 'Riattiva account'?></button></form><?php endif;?></div></details></td></tr><?php endforeach;?></tbody></table></div></section>
    <section class="panel section-space"><h2>Invita una nuova persona</h2><p class="muted">Crea l’account e comunica le credenziali privatamente. La persona potrà cambiare la password dal proprio profilo.</p><form method="post" class="stack"><?=action_input('account_create')?><div class="form-grid"><label>Nome pubblico<input name="name" maxlength="80" required></label><label>Email<input name="email" type="email" maxlength="190" required autocomplete="off"></label><label>Ruolo<select name="role"><option value="user">Partecipante</option><option value="creator">Creator</option><option value="admin">Amministratore</option></select></label><label>Password iniziale<input name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password"></label></div><button class="button" type="submit">Crea account <?=icon('plus')?></button></form></section><?php
}
function reviews_view(): void
{
    page_heading('Ogni impegno merita un riscontro', 'Approva i risultati o aiuta le persone a riprovare con un consiglio concreto.');
    [$scope,$params] = group_scope();
    $rows = all("SELECT s.*,c.title,c.points,g.name group_name,u.name FROM d2_submissions s JOIN d2_challenges c ON c.id=s.challenge_id JOIN d2_groups g ON g.id=c.group_id JOIN d2_users u ON u.id=s.user_id WHERE $scope AND s.status='pending' ORDER BY s.submitted_at LIMIT 50", $params);
    if (!$rows) {
        empty_state('Tutto in pari', 'Non ci sono prove da valutare. Le prossime compariranno qui.');
        return;
    }
    foreach ($rows as $s):?><article class="panel review-card"><a class="review-image-link" href="image.php?id=<?=$s['id']?>" target="_blank" rel="noopener"><img class="review-image" src="image.php?id=<?=$s['id']?>" alt="Prova inviata da <?=e($s['name'])?>"></a><div class="review-content"><div class="card-meta"><span><?=e($s['group_name'])?></span><span class="points-text">+<?=e($s['points'])?> pt</span></div><h2><?=e($s['title'])?></h2><p class="muted">Inviata da <strong><?=e($s['name'])?></strong> · <?=nice_date($s['submitted_at'])?></p><p class="prose"><?=nl2br(e($s['note']))?></p><form method="post" class="stack"><?=action_input('review')?><input type="hidden" name="id" value="<?=$s['id']?>"><label>Un riscontro per chi ci ha provato<textarea name="feedback" rows="3" maxlength="2000" placeholder="Obbligatorio se chiedi di riprovare. Un consiglio concreto fa la differenza."></textarea></label><div class="button-row"><button class="button" name="decision" value="approved" type="submit"><?=icon('check')?> Approva e assegna punti</button><button class="button secondary" name="decision" value="rejected" type="submit">Chiedi di riprovare</button></div></form></div></article><?php endforeach;
}
function history_view(): void
{
    $u = require_user();
    if ($u['role'] !== 'user') {
        abort_request(403, 'Questa pagina è dedicata ai partecipanti.');
    }page_heading('I tuoi progressi', 'Tutti i tentativi contano. Qui trovi prove, risultati e riscontri.');
    $rows = all('SELECT s.*,c.title,g.name group_name FROM d2_submissions s JOIN d2_challenges c ON c.id=s.challenge_id JOIN d2_groups g ON g.id=c.group_id WHERE s.user_id=? ORDER BY s.submitted_at DESC', [$u['id']]);
    if (!$rows) {
        empty_state('Il tuo percorso comincia qui', 'Scegli una sfida e invia la prima prova.', url('challenges'), 'Esplora le sfide');
        return;
    }
    foreach ($rows as $s):?><article class="panel history-card"><img src="image.php?id=<?=$s['id']?>" alt="La tua prova per <?=e($s['title'])?>"><div><span class="eyebrow"><?=e($s['group_name'])?></span><h2><?=e($s['title'])?></h2><p><?=e($s['note'])?></p><?php if ($s['feedback']):?><div class="feedback"><strong>Il creator dice</strong><p><?=e($s['feedback'])?></p></div><?php endif;?><small class="muted">Inviata il <?=nice_date($s['submitted_at'])?></small></div><div class="history-status"><?=badge($s['status'])?><?php if ($s['status'] === 'approved'):?><strong>+<?=e($s['awarded_points'])?> pt</strong><?php elseif ($s['status'] === 'rejected'):?><a href="<?=e(url('challenge', ['id' => $s['challenge_id']]))?>">Riprova →</a><?php endif;?></div></article><?php endforeach;
}
function leaderboard_view(): void
{
    page_heading('Ogni traguardo conta', 'La classifica premia le prove approvate. Il confronto migliore è con il tuo punto di partenza.');
    $rows = all("SELECT u.id,u.name,COALESCE(SUM(s.awarded_points),0) points,COUNT(s.id) completed FROM d2_users u LEFT JOIN d2_submissions s ON s.user_id=u.id AND s.status='approved' WHERE u.active=1 AND u.role='user' GROUP BY u.id,u.name ORDER BY points DESC,completed DESC,u.name LIMIT 50");
    if (!$rows) {
        empty_state('La classifica aspetta i primi passi', 'I partecipanti compariranno qui quando verranno creati i loro account.');
        return;
    }
    ?><div class="podium"><?php foreach (array_slice($rows, 0, 3) as $i => $r):?><article class="podium-card rank-<?=$i + 1?>"><span class="podium-position"><?=icon($i === 0 ? 'trophy' : 'spark')?> <?=$i + 1?></span><span class="podium-avatar"><?=e(mb_strtoupper(mb_substr($r['name'], 0, 1)))?></span><h2><?=e($r['name'])?></h2><strong><?=e($r['points'])?> <small>pt</small></strong><p><?=e($r['completed'])?> sfide completate</p></article><?php endforeach;?></div><section class="panel"><div class="section-title compact"><h2>La comunità, in movimento</h2><span class="muted">Tutti i gruppi · Top 50</span></div><div class="table-wrap"><table><thead><tr><th>Posizione</th><th>Partecipante</th><th>Sfide approvate</th><th>Punti</th></tr></thead><tbody><?php foreach ($rows as $i => $r):?><tr class="<?=$r['id'] === current_user()['id'] ? 'your-row' : ''?>"><td><span class="rank-number"><?=str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)?></span></td><td><strong><?=e($r['name'])?></strong><?=$r['id'] === current_user()['id'] ? ' <span class="you-label">Tu</span>' : ''?></td><td><?=e($r['completed'])?></td><td><strong class="points-text"><?=e($r['points'])?></strong></td></tr><?php endforeach;?></tbody></table></div><p class="fine">A parità di punti: numero di sfide approvate, poi nome in ordine alfabetico. Sono mostrati soltanto i nomi pubblici.</p></section><?php
    $groups = all("SELECT g.name,COALESCE(SUM(CASE WHEN s.status='approved' AND u.active=1 THEN s.awarded_points ELSE 0 END),0) points FROM d2_groups g LEFT JOIN d2_challenges c ON c.group_id=g.id LEFT JOIN d2_submissions s ON s.challenge_id=c.id LEFT JOIN d2_users u ON u.id=s.user_id GROUP BY g.id,g.name ORDER BY points DESC,g.name LIMIT 10");?>
    <section class="panel section-space"><h2>Il risultato di squadra</h2><?php foreach ($groups as $g):?><div class="team-score"><span><?=icon('users')?> <?=e($g['name'])?></span><strong><?=e($g['points'])?> pt</strong></div><?php endforeach;?><p class="fine">I punti seguono il gruppo della sfida, anche se un partecipante cambia gruppo.</p></section><?php
}
function profile_view(): void
{
    $u = require_user();
    page_heading('Il tuo profilo', 'Il nome che ti rappresenta, un accesso che resta tuo.');?><section class="panel profile-panel"><div class="profile-header"><span class="avatar large"><?=e(mb_strtoupper(mb_substr($u['name'], 0, 1)))?></span><div><h2><?=e($u['name'])?></h2><p><?=e($u['email'])?></p></div></div><form method="post" class="stack"><?=action_input('profile')?><label>Nome pubblico<input name="name" value="<?=e($u['name'])?>" maxlength="80" required></label><label>Password attuale<input name="current_password" type="password" required autocomplete="current-password"></label><label>Nuova password<input name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password"><small>Almeno 12 caratteri, massimo 72 byte.</small></label><button class="button" type="submit">Salva e aggiorna password</button></form></section><?php
}

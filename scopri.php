<?php
// Public showcase: no authentication, database access or user data.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self'; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dareonym — Piccoli passi. Risultati condivisi.</title>
<meta name="description" content="Trasforma gli obiettivi del tuo gruppo in sfide concrete. Scopri Dareonym e prova il percorso interattivo, senza account.">
<meta name="theme-color" content="#172238">
<meta property="og:title" content="Dareonym · Piccoli passi. Risultati condivisi.">
<meta property="og:description" content="Una sfida, una prova, un progresso condiviso. Esplora la dimostrazione senza account.">
<meta property="og:type" content="website">
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/scopri.css"><script src="assets/scopri.js" defer></script>
</head>
<body>
<a class="skip" href="#contenuto">Vai al contenuto</a>
<header class="header wrap"><a class="brand" href="scopri.php"><span class="mark">d.</span>Dareonym</a><nav aria-label="Navigazione"><a href="#come-funziona">Come funziona</a><a class="button small" href="index.php?page=login">Accedi <span aria-hidden="true">↗</span></a></nav></header>
<main id="contenuto">
<section class="hero wrap">
<div class="hero-copy"><p class="eyebrow"><span class="dot"></span> UN PASSO TUO. UN TRAGUARDO DI TUTTI.</p><h1>Le buone intenzioni<br>meritano <em>un seguito.</em></h1><p class="intro">Un obiettivo concreto, una prova del tuo impegno e un gruppo che cresce con te. Dareonym dà spazio ai piccoli progressi.</p><div class="actions"><a class="button" href="#prova">Prova il percorso <span aria-hidden="true">→</span></a><a class="text-link" href="https://github.com/flavio-petrone/dareonym">Guarda il codice ↗</a></div><p class="fine">Senza account · Dati dimostrativi · Nessuna registrazione</p></div>
<div class="hero-visual" aria-label="Esempio illustrato di sfida approvata">
<div class="orbit one"></div><div class="orbit two"></div><span class="floating-label">Un piccolo passo, oggi.</span>
<div class="example-card"><div class="example-top"><span class="badge">PROVA APPROVATA</span><span aria-hidden="true">✦</span></div><div class="desk-art" aria-hidden="true"><div class="plant">✦</div><div class="laptop"></div><div class="book"></div><div class="desk"></div></div><p class="eyebrow">PICCOLI PASSI</p><h2>Il tuo angolo<br>di concentrazione</h2><div class="example-bottom"><span class="avatar">G</span><span>Giulia · Partecipante</span><strong>+50 pt</strong></div></div><span class="floating-points">✧ Insieme conta di più</span>
</div>
</section>
<section id="come-funziona" class="how wrap"><div class="section-head"><p class="eyebrow">DAL DIRE AL FARE</p><h2>Tre passaggi.<br>Un risultato da condividere.</h2></div><div class="steps"><article><span class="number">01</span><h3>Una sfida concreta</h3><p>Il creator propone un obiettivo al gruppo, con istruzioni chiare e punti da conquistare.</p></article><article><span class="number">02</span><h3>Il tuo impegno, visibile</h3><p>Il partecipante invia una foto e racconta il risultato. La prova resta visibile alle persone autorizzate.</p></article><article><span class="number">03</span><h3>Un progresso riconosciuto</h3><p>Il creator valuta e lascia un riscontro. Le prove approvate alimentano la classifica personale e di squadra.</p></article></div></section>
<section id="prova" class="demo-section"><div class="wrap"><div class="section-head"><p class="eyebrow">TOCCA A TE</p><h2>Un minuto dentro Dareonym.</h2><p>Segui una sfida dall’inizio alla classifica. Questa è una simulazione con dati fittizi: le tue azioni restano in questa pagina.</p></div>
<div class="demo-shell"><aside class="demo-nav"><span class="brand"><span class="mark">d.</span>Dareonym</span><p class="eyebrow">IL PERCORSO</p><ol id="demo-steps"><li aria-current="step">01 · La sfida</li><li>02 · La prova</li><li>03 · Il riscontro</li><li>04 · La classifica</li></ol><span class="demo-tag">Dimostrazione interattiva</span></aside><div class="demo-main"><div class="demo-top"><span id="demo-role">Vista partecipante</span><button id="demo-reset" class="text-button" type="button" hidden>Ricomincia ↺</button></div>
<div id="demo-content" aria-live="polite" aria-atomic="true"><p class="eyebrow">GRUPPO · PICCOLI PASSI</p><h3 id="demo-title" tabindex="-1">Il tuo angolo di concentrazione</h3><p id="demo-description">Organizza uno spazio per studiare o lavorare. Una foto e poche parole racconteranno il tuo risultato.</p><div id="demo-result" class="demo-result"><span class="badge">Disponibile</span><strong>50 punti</strong><span>Senza scadenza</span></div></div>
<div id="demo-controls" class="actions"><button id="demo-next" class="button" type="button">Partecipa alla sfida →</button><button id="demo-reject" class="text-button" type="button" hidden>Chiedi una nuova prova</button></div><p id="demo-note" class="fine">Non serve caricare una foto: useremo una prova di esempio.</p>
<noscript><p>Attiva JavaScript per provare la simulazione. Nell’app il creator pubblica una sfida, il partecipante invia la prova e, dopo l’approvazione, ottiene i punti.</p></noscript>
</div></div></div></section>
<section class="roles wrap"><div class="section-head"><p class="eyebrow">UNO SPAZIO, TRE RUOLI</p><h2>A ognuno il suo prossimo passo.</h2></div><div class="steps"><article><span class="role-symbol" aria-hidden="true">↗</span><h3>Amministratore</h3><p>Crea gli account e i gruppi, assegna i creator e gestisce gli accessi alla comunità.</p></article><article><span class="role-symbol" aria-hidden="true">✦</span><h3>Creator</h3><p>Accompagna il gruppo: pubblica sfide, valuta le prove e dà riscontri utili per migliorare.</p></article><article><span class="role-symbol" aria-hidden="true">◎</span><h3>Partecipante</h3><p>Si mette in gioco, condivide una prova, legge il riscontro e segue i propri progressi.</p></article></div></section>
<section class="project wrap"><div><p class="eyebrow">IL PROGETTO</p><h2>Progettato e sviluppato<br>da Flavio Petrone.</h2><p>Dareonym è un progetto portfolio: interfaccia originale, gestione dei ruoli e un percorso completo dalla sfida alla valutazione.</p><p>PHP e MySQL, HTML, CSS e JavaScript. Il codice, le istruzioni per eseguirlo e i test sono disponibili su GitHub.</p><a class="text-link" href="https://github.com/flavio-petrone/dareonym">Esplora il repository →</a></div><div class="project-note"><span class="eyebrow">VUOI USARE L’APPLICAZIONE?</span><h3>Hai già un account?</h3><p>Accedi con le credenziali ricevute dall’amministratore del gruppo. La dimostrazione qui sopra è aperta a tutti; l’applicazione richiede un invito.</p><a class="button" href="index.php?page=login">Vai all’accesso →</a></div></section>
</main><footer class="wrap footer"><a class="brand" href="scopri.php">Dareonym</a><span>Piccoli passi. Risultati condivisi.</span><a href="https://github.com/flavio-petrone/dareonym/blob/main/LICENSE">Codice e asset · MIT</a></footer>
</body></html>

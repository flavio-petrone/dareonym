# Risultati del collaudo

Data: 27 settembre 2026.

Ambiente locale: macOS ARM64, PHP 8.4.11, SQLite su file temporaneo e MariaDB 13.0.2 con InnoDB. MariaDB è stato avviato temporaneamente su loopback con dati isolati nella cartella di lavoro; nessun servizio è stato impostato per avviarsi automaticamente e nessun database remoto è stato usato.

## Test eseguiti

- **81 verifiche HTTP end-to-end su SQLite:** `python3 tests/run.py`.
- **81 verifiche HTTP end-to-end su MariaDB:** stesso script con configurazione di un database nuovo e vuoto.
- **9 verifiche dell’installer web su MariaDB:** `python3 tests/installer.py` su un secondo database vuoto.
- Controllo sintattico di tutti i file PHP consegnati.
- Controllo visivo nel browser della dashboard e dell’ingresso; verifica dei layout responsive e dell’assenza di overflow orizzontale alle larghezze controllate. Nessuna certificazione completa di accessibilità o matrice di browser.

I test HTTP effettuano veri login e richieste all’applicazione. Non simulano l’autenticazione inserendo sessioni privilegiate: creano solo account fittizi nel database della copia di prova.

Copertura: login/logout; ruoli; gruppi non autorizzati; creazione account e gruppi; email duplicate; output ostile correttamente codificato; creazione sfide; upload falso respinto; ricodifica dell’immagine; lettura solo autorizzata; rifiuto con feedback; reinvio; eliminazione della foto sostituita; approvazione singola; punteggi; chiusura sfida; rimozione dal gruppo e conservazione dei risultati; file privati; reset password e revoca sessioni; disattivazione; limiti ai tentativi di login.

Installer: blocco senza codice privato, rifiuto del codice errato, schema e amministratore creati, configurazione privata salvata e non scaricabile, codice eliminato, login del nuovo amministratore, dashboard vuota funzionante e installer non più disponibile.

## Limiti del collaudo

Non sono stati verificati carichi elevati, tutti i browser/dispositivi o un audit esaustivo da parte di terzi. I test HTTP automatici locali usano il router PHP; i controlli remoti sotto riportati coprono alcuni percorsi Apache reali. Il collaudo manuale MySQL 8 online non sostituisce l’intera suite automatica su quel motore. Le versioni PHP diverse dalla 8.4.11 saranno verificate dalla matrice CI: la sua configurazione non è di per sé una prova di esecuzione.

I test MySQL/MariaDB richiedono database vuoti con prefisso `dareonym_test_`; non eliminano automaticamente le tabelle. Le credenziali JSON devono essere conservate fuori dal repository.

## Correzione HTTPS su hosting con proxy

Dopo la correzione: 12 controlli dedicati (`php tests/https.php`) e tutte le 81 verifiche HTTP SQLite superate nuovamente. Il controllo del proxy richiede attivazione esplicita; intestazioni inoltrate non autorizzate o ambigue sono respinte. La correzione è stata poi verificata nel percorso online descritto sotto.


## Collaudo manuale su Altervista

27 settembre 2026, PHP 8.4 e MySQL 8.0 con InnoDB, come mostrato dal pannello hosting. L’utente ha completato e documentato tramite schermate:

1. Installazione protetta e accesso amministratore.
2. Creazione di creator, partecipante e gruppo.
3. Pubblicazione della sfida dal creator.
4. Invio di una prova fotografica dal partecipante.
5. Approvazione dal creator: 50 punti al partecipante e al gruppo, una prova approvata.

L’immagine utilizzata era generata con IA e identificata come dimostrativa.

## Verifiche della versione portfolio

- **85 verifiche HTTP SQLite**: le 81 precedenti più accesso pubblico, assenza di sessione nella presentazione, collegamento dal login e asset della simulazione.
- **20 controlli HTTPS**: 12 sul rilevamento e 8 sulla costruzione del redirect.
- **6 controlli del trasporto con PHP CGI** (`python3 tests/transport.py`, richiede `php-cgi`): GET HTTP reindirizzato senza creare sessioni, POST HTTP respinto senza reinvio, cookie Secure in HTTPS, header non fidato ignorato e proxy esplicitamente abilitato.
- Simulazione verificata nel browser: partecipazione, invio, rifiuto con riscontro, reinvio, approvazione e ripartenza; uso da tastiera e viste desktop/mobile.
- Controlli remoti di sola lettura, prima dell’aggiornamento portfolio: login HTTPS 200 con cookie Secure, HttpOnly/SameSite e CSP; `config/local.php`, `config/install.php`, `storage/`, `app/core.php`, `.git/config` rispondono 403.
- Il login HTTP remoto rispondeva 200: questa versione introduce il redirect HTTPS prima della creazione della sessione. **Verificato dopo il caricamento:** HTTP login 302 verso HTTPS senza cookie, ingresso anonimo 303 verso `scopri.php`, presentazione 200 senza cookie, login HTTPS 200 con cookie Secure. La sessione creator esistente mantiene la classifica: Giovanni Verdi, 50 punti, una prova approvata; gruppo Piccoli passi, 50 punti.

La CI in `.github/workflows/tests.yml` esegue sintassi, HTTPS, suite SQLite, suite MySQL 8 e installer su PHP 8.2/8.4. L’esito online è disponibile nella scheda Actions dopo il push; non è dichiarato superato prima dell’esecuzione.

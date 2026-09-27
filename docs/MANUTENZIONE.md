# Manutenzione e ripristino

## Prima di aggiornare

1. Conserva privatamente una copia dei file correnti, inclusi `config/local.php`, `config/https-proxy.php` e i `.htaccess` modificati dall’hosting.
2. Esporta il database dal pannello del provider e copia `storage/uploads`. Database e fotografie devono riferirsi allo stesso momento: per un backup coerente sospendi gli invii durante la copia.
3. Tieni gli archivi fuori dallo spazio web e dal repository, con accesso riservato.
4. Leggi il changelog: un aggiornamento di soli file non richiede di rieseguire l’installatore.

## Aggiornamento portfolio 2.1

Aggiorna `index.php`, `app/core.php`, `app/views.php` e aggiungi `scopri.php`, `assets/scopri.css`, `assets/scopri.js`. Non sono richieste modifiche al database. Non sovrascrivere configurazioni private, foto o `.htaccess` del sito installato.

Poi verifica:

- L’indirizzo principale, in una finestra privata, apre la presentazione.
- Il collegamento Accedi apre il login HTTPS e l’account esistente funziona.
- Un indirizzo `http://` dell’applicazione passa a `https://` prima del login.
- La simulazione si completa e si può ricominciare.
- Sfide, prove e punteggi esistenti sono ancora presenti dopo l’accesso.

In caso di problemi, ripristina i tre file modificati dalla copia precedente. La presentazione può restare sul server oppure essere rimossa successivamente; questo aggiornamento non modifica lo schema né i dati. Un eventuale ripristino del database riguarda altri tipi di aggiornamento e può perdere operazioni successive al backup: va pianificato separatamente.

## Gestione ordinaria

- Controlla quota disco, disponibilità del sito e log privati sotto `storage/logs`.
- Mantieni aggiornati PHP e il servizio hosting; verifica la compatibilità in una copia separata prima del cambio versione.
- Prova periodicamente il ripristino su una copia privata: un archivio mai ripristinato non dimostra che il recupero funzioni.
- Usa gli account amministratori soltanto per la gestione. Per una presentazione pubblica condividi `scopri.php`, senza credenziali amministratore.
- Gli account di prova già creati si possono disattivare da Persone. I loro dati storici restano nel database; non vengono cancellati automaticamente.
- Per password dimenticate, un amministratore può reimpostarle da Persone; non esiste un recupero via email.

Il progetto non comprende backup automatici, cancellazione dati automatizzata o monitoraggio continuo. Queste attività dipendono dall’uso concreto e dall’hosting scelto.

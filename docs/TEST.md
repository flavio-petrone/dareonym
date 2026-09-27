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

Non sono stati verificati lo specifico hosting Altervista, le sue impostazioni PHP/Apache e i permessi del filesystem, MySQL 8 come motore distinto, versioni PHP diverse dalla 8.4.11, carichi elevati o un audit esaustivo da parte di terzi. La protezione Apache è inclusa ma i test HTTP usano il router locale PHP. Le caratteristiche del server remoto devono essere controllate prima dell’attivazione.

I test MySQL/MariaDB richiedono database vuoti con prefisso `dareonym_test_`; non eliminano automaticamente le tabelle. Le credenziali JSON devono essere conservate fuori dal repository.

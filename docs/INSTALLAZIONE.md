# Installare Dareonym 2 su Altervista

Questo pacchetto riparte da zero. I vecchi account non sono gli account di Dareonym 2: il primo amministratore si crea durante l’installazione.

## 1. Preparare uno spazio di prova

Scarica una copia privata dei file online ed esporta il database dal pannello del servizio. Lo ZIP originale non garantisce di contenere tutto ciò che è attualmente online.

Usa inizialmente una cartella nuova, per esempio `dareonym2`, nello stesso spazio web. Non cancellare `dareonym` e non importare il vecchio dump nella nuova applicazione. Il sito nuovo usa tabelle con prefisso `d2_`, separate dalle precedenti `dr_`.

Nel pannello Altervista controlla:

- PHP **8.2 o successivo** per la cartella nuova. Se non è disponibile, non caricare la nuova app come sostituzione del sito: va prima concordato un ambiente compatibile.
- Database abilitato e supporto **InnoDB**.
- HTTPS funzionante.
- Estensioni PDO MySQL, GD, Fileinfo e mbstring.

La pagina iniziale segnala versione PHP ed estensioni mancanti prima di tentare il login. Non stampare `phpinfo()` pubblicamente per cercare le credenziali.

## 2. Preparare il codice di installazione

L’installazione non è aperta a chiunque visiti l’URL. Richiede un codice che solo tu conosci.

Sul tuo computer, nella cartella pulita del progetto, puoi eseguire:

```sh
php scripts/prepare-install.php
```

Conserva il codice mostrato: il comando genera `config/install.php`. In alternativa, copia `config/install.example.php` in `config/install.php` e sostituisci il valore fra apici con una stringa casuale di almeno 24 caratteri creata da un gestore password. Evita apici, barre inverse e spazi per semplificare la copia.

Non usare il segnaposto dell’esempio, non pubblicare il codice in chat o su GitHub. Il file `config/install.php` è ignorato da Git.

## 3. Caricare i file

Carica il **contenuto** del progetto nella cartella scelta. Includi tutti i `.htaccess`, anche quelli dentro `app`, `config` e `storage`: i client FTP possono nasconderli.

Non caricare:

- La configurazione e il database della demo locale.
- Copie di backup, archivi ZIP, log o fotografie di prova.
- La cartella `.git`, se hai inizializzato un repository.

`config` e `storage` devono essere scrivibili dall’utente PHP durante l’installazione; `storage/uploads` e `storage/logs` devono restare scrivibili. Evita permessi universali come 777. Dopo l’installazione puoi rendere la configurazione non scrivibile, mantenendola leggibile da PHP.

I `.htaccess` richiedono Apache 2.4 e direttive consentite dal provider. Se il server restituisce un errore 500 prima che compaia Dareonym, consulta il log dell’hosting: potrebbe riguardare tali direttive. Non cancellare indiscriminatamente i divieti sui file privati per far sparire l’errore.

## 4. Completare l’installazione dal browser

Apri `https://TUO-SITO/dareonym2/`.

1. Inserisci il codice di installazione.
2. Copia dal pannello hosting i parametri del database. Il nome Altervista segue normalmente la forma `my_nomeaccount`; verifica quello effettivo nel tuo account. Non usare automaticamente il nome `db_dareonym` della vecchia installazione locale.
3. Usa la password database richiesta dal provider, se prevista. **Non inserire automaticamente la password del pannello Altervista.** La documentazione del servizio descrive anche configurazioni che non richiedono quel parametro; verifica il tuo account.
4. Scegli nome, email e una password nuova per l’amministratore.
5. Completa la procedura e accedi con quell’account.

L’installer scrive `config/local.php`, crea le tabelle e rimuove il codice monouso. Dopo il completamento non è più possibile creare un altro primo amministratore tramite l’installer.

Se l’installazione si interrompe dopo aver creato alcune tabelle, queste possono restare nel database: MySQL non rende atomiche tutte le istruzioni di creazione. L’app non le cancella o sovrascrive automaticamente. Conserva il log privato e risolvi il problema su una copia di prova.

## 5. Primo utilizzo

1. Da **Persone**, crea un creator e un partecipante.
2. Da **Gruppi**, crea un gruppo assegnandolo al creator.
3. Apri il gruppo e aggiungi il partecipante.
4. Accedi come creator e pubblica la prima sfida.
5. Accedi come partecipante e invia una prova.
6. Torna come creator, approvala e controlla la classifica.

Per passare da un account all’altro usa Esci oppure finestre private separate. Le credenziali della demo non vengono create dall’installer online.

## 6. Prima di sostituire il sito precedente

Verifica il percorso completo sul tuo hosting, anche da telefono. Controlla che gli URL di `config/local.php`, `storage/logs/php.log` e una foto sotto `storage/uploads` non siano scaricabili direttamente. L’immagine deve essere accessibile solo da `image.php` alla persona autorizzata.

Quando il test è riuscito si può pianificare il passaggio all’URL definitivo, mantenendo un backup privato recuperabile. Non lasciare archivi o vecchi dump nel percorso pubblico. L’app usa collegamenti relativi ed è progettata per funzionare in una sottocartella, senza riscrittura degli URL.

Le impostazioni dello specifico account Altervista non sono state ispezionate né modificate durante lo sviluppo. Nessun dato o credenziale del sito reale è stato usato nei test.

Riferimenti del servizio: [PHP](https://help.altervista.org/it/PHP), [database MySQL](https://help.altervista.org/it/Database_MySQL). Alcune pagine della documentazione sono storiche: fanno fede anche le opzioni disponibili nel pannello del tuo account.

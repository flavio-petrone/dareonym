# Sicurezza

Non inserire password, configurazioni, immagini degli utenti o dump nelle issue pubbliche. Per un problema che contiene dati sensibili usa un canale privato concordato con il gestore del progetto. Non è indicato un indirizzo di contatto inventato.

## Prima di esporre l’applicazione

Usare HTTPS, un database dedicato o tabelle isolate, backup privati e un ambiente PHP aggiornato. Verificare i divieti HTTP sui percorsi `app`, `config`, `storage`, `scripts`, `tests`, `docs` e `database`. Apache deve applicare i `.htaccess`; Nginx richiede regole equivalenti esplicite. Il server locale va avviato con `router.php` e vincolato a `127.0.0.1`.

Non usare account o database generati da `scripts/demo.php` online. Non committare `config/local.php` o `config/install.php`. Il codice monouso deve essere casuale e custodito privatamente; l’installer online richiede HTTPS.

## Confini e limiti

- I creator possono gestire soltanto i gruppi loro assegnati, ma possono scegliere i partecipanti dal catalogo dei nomi pubblici degli utenti registrati. Questa è una scelta per una comunità amministrata, non un sistema multitenant con organizzazioni reciprocamente segrete.
- Le classifiche sono comuni alla comunità autenticata e mostrano nomi pubblici. Le fotografie non sono pubbliche: proprietario, creator assegnato e amministratore possono leggerle.
- La finestra di blocco del login è di 15 minuti con 10 tentativi errati, applicata per account e IP. Sotto NAT condiviso può interessare più persone; dietro proxy configurare correttamente la rete. Il codice non si fida automaticamente di `X-Forwarded-For`.
- Le quote limitano le immagini a 100 prove conservate per account e 3 MB per immagine elaborata. Monitorare comunque spazio e log. L’invio simultaneo di più richieste può oltrepassare leggermente la quota numerica; non è una quota disco rigida del filesystem.
- La foto originale viene decodificata e risalvata come JPEG, perdendo metadati e trasparenza. Le foto sostituite dopo un rifiuto vengono eliminate. Non viene conservata una cronologia delle versioni delle prove.
- Reset delle password, disattivazione e cambio password aggiornano una versione di autenticazione verificata a ogni richiesta; il cambio dal profilo mantiene solo la sessione corrente.
- I dati non vengono cancellati automaticamente disattivando un account. Prima di raccogliere dati reali, il gestore deve stabilire conservazione, esportazione/cancellazione e informazioni per gli utenti adeguate all’uso concreto.
- Il log pubblico mostra riferimenti generici; i log tecnici restano sotto `storage/logs`. Non abilitarne la visualizzazione HTTP.
- Le prove approvate non possono essere riapprovate o rivalutate via interfaccia. Eventuali correzioni amministrative dei punteggi richiedono una funzionalità separata con tracciamento, non una modifica casuale al database.

Questa consegna include test automatici e un controllo mirato del codice; non è una certificazione di sicurezza né una verifica del server di destinazione.

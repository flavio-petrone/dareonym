# Contribuire a Dareonym

Per segnalare un problema, descrivi la pagina, il ruolo usato, i passi per riprodurlo, il risultato atteso e quello ottenuto. Aggiungi versione PHP e database se pertinenti. Usa soltanto dati fittizi e oscura credenziali, email private e fotografie degli utenti.

Per una modifica:

1. Parti da una copia pulita e crea un branch dedicato.
2. Avvia la demo locale come descritto nel README.
3. Esegui `php tests/https.php` e `python3 tests/run.py`.
4. Aggiungi un test quando cambia un comportamento, soprattutto permessi, upload o punteggi.
5. Spiega nella pull request il problema risolto e i controlli eseguiti.

Le modifiche allo schema richiedono una strategia esplicita di migrazione e recupero. Non inserire configurazioni private, database o dump nel repository. Per vulnerabilità riservate segui SECURITY.md, senza pubblicare istruzioni o dati sensibili nelle issue.

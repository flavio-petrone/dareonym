# Changelog

## 2.1 — Presentazione e pubblicazione

- Presentazione pubblica con dimostrazione interattiva senza account e senza accesso ai dati reali.
- Ingresso pubblico dalla radice per visitatori non autenticati e collegamento dal login.
- HTTPS obbligatorio prima dell’apertura di una sessione in produzione; i POST HTTP vengono respinti senza replay.
- CI configurata per PHP 8.2/8.4, SQLite, MySQL 8 e installer.
- Documentazione del collaudo online, manutenzione e contribuzioni; ZIP runtime riproducibile con elenco esplicito di file.

Nessuna migrazione del database. La pubblicazione del codice e il caricamento sull’hosting restano passaggi distinti.

## 2.0 — 27 settembre 2026

- Prima versione con amministratore, creator, partecipante, gruppi, sfide, prove fotografiche, valutazione e classifiche.
- Installatore protetto, demo locale e suite di integrazione.
- Supporto HTTPS dietro proxy fidato con abilitazione esplicita.

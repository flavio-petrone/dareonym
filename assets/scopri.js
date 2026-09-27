"use strict";
(() => {
  const title = document.querySelector("#demo-title");
  if (!title) return;
  const description = document.querySelector("#demo-description");
  const result = document.querySelector("#demo-result");
  const next = document.querySelector("#demo-next");
  const reject = document.querySelector("#demo-reject");
  const reset = document.querySelector("#demo-reset");
  const role = document.querySelector("#demo-role");
  const note = document.querySelector("#demo-note");
  const steps = [...document.querySelectorAll("#demo-steps li")];
  let stage = 0;
  let retry = false;
  const screens = [
    ["Vista partecipante", "Il tuo angolo di concentrazione", "Organizza uno spazio per studiare o lavorare. Una foto e poche parole racconteranno il tuo risultato.", "Partecipa alla sfida →"],
    ["Vista partecipante", "Un piccolo cambiamento, tutto tuo.", "Giulia ha liberato la scrivania, preparato un quaderno e scelto un posto luminoso. La sua prova di esempio è pronta per l’invio.", "Invia la prova di esempio →"],
    ["Vista creator", "Ogni impegno merita un riscontro.", "La prova di Giulia è in attesa. Come creator, puoi approvarla oppure chiedere una nuova prova con un suggerimento.", "Approva e assegna 50 punti →"],
    ["La classifica", "Il tuo passo fa crescere il gruppo.", "Prova approvata: Giulia conquista 50 punti e il gruppo Piccoli passi cresce con lei. Una stessa prova assegna punti una sola volta.", "Riprova il percorso ↺"]
  ];
  function render(focus = true) {
    const screen = screens[stage];
    role.textContent = screen[0];
    title.textContent = screen[1];
    description.textContent = retry && stage === 1 ? "Il creator suggerisce: «Mostra meglio come hai organizzato il piano di lavoro». Giulia prepara una nuova prova. Nessun punto viene assegnato finché non sarà approvata." : screen[2];
    next.textContent = screen[3];
    reject.hidden = stage !== 2;
    reset.hidden = stage === 0;
    result.replaceChildren();
    const items = stage === 0 ? ["Disponibile", "50 punti", "Senza scadenza"] : stage === 1 ? [retry ? "Da riprovare" : "Prova di esempio pronta", "Scrivania organizzata", "Giulia · Partecipante"] : stage === 2 ? ["Da valutare", "0 punti assegnati", "Foto e descrizione ricevute"] : ["01 · Giulia", "50 punti · 1 sfida approvata", "Piccoli passi · 50 punti"];
    items.forEach((text, index) => {
      const element = document.createElement(index === 1 ? "strong" : "span");
      element.textContent = text;
      if (index === 0) element.className = "badge";
      result.append(element);
    });
    note.textContent = stage === 0 ? "Non serve caricare una foto: useremo una prova di esempio." : "Simulazione: nessun file caricato, nessun account creato e nessun dato salvato sul sito.";
    steps.forEach((step, index) => {
      if (index === stage) step.setAttribute("aria-current", "step");
      else step.removeAttribute("aria-current");
    });
    if (focus) title.focus({preventScroll: true});
  }
  next.addEventListener("click", () => { if (stage === 3) { stage = 0; retry = false; } else stage++; render(); });
  reject.addEventListener("click", () => { stage = 1; retry = true; render(); });
  reset.addEventListener("click", () => { stage = 0; retry = false; render(); });
})();

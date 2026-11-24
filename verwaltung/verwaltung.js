/* Verwaltung: Sicherheitsabfrage vor dem Löschen und Warnung bei ungespeicherten Änderungen. */
(function () {
  "use strict";
  document.querySelectorAll("form[data-bestaetigen]").forEach(function (f) {
    f.addEventListener("submit", function (e) {
      if (!window.confirm(f.getAttribute("data-bestaetigen"))) e.preventDefault();
    });
  });
  var geaendert = false;
  document.querySelectorAll(".v-formular").forEach(function (f) {
    f.addEventListener("input", function () { geaendert = true; });
    f.addEventListener("submit", function () { geaendert = false; });
  });
  window.addEventListener("beforeunload", function (e) {
    if (geaendert) { e.preventDefault(); e.returnValue = ""; }
  });
})();

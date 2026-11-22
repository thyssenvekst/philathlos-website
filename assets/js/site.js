/* T.C. Philathlos München – Interaktionen. Ohne Abhängigkeiten, ohne Cookies, ohne Tracking.
   Alles funktioniert auch ohne JavaScript; hier wird nur verfeinert. */
(function () {
  "use strict";
  var d = document;
  var ruhig = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- Kopf wird beim Scrollen kompakter ---------- */
  var kopf = d.querySelector(".kopf");
  if (kopf) {
    var wartet = false;
    var pruefen = function () { kopf.classList.toggle("ist-kompakt", window.scrollY > 40); wartet = false; };
    window.addEventListener("scroll", function () { if (!wartet) { wartet = true; requestAnimationFrame(pruefen); } }, { passive: true });
    pruefen();
  }

  /* ---------- Mobiles Menü (natives dialog: Fokusfalle und Esc inklusive) ---------- */
  var menue = d.getElementById("menue");
  var menueKnopf = d.querySelector(".menue-knopf");
  if (menue && menueKnopf && typeof menue.showModal === "function") {
    var schliessen = function () {
      if (!menue.open) return;
      if (ruhig) { menue.close(); return; }
      menue.classList.add("schliesst");
      setTimeout(function () { menue.classList.remove("schliesst"); menue.close(); }, 190);
    };
    menueKnopf.addEventListener("click", function () {
      menue.showModal();
      menueKnopf.setAttribute("aria-expanded", "true");
      d.body.style.overflow = "hidden";
    });
    menue.addEventListener("close", function () {
      menueKnopf.setAttribute("aria-expanded", "false");
      d.body.style.overflow = "";
      menueKnopf.focus();
    });
    menue.addEventListener("cancel", function (e) { e.preventDefault(); schliessen(); });
    menue.querySelector(".schliessen").addEventListener("click", schliessen);
    menue.querySelectorAll("a").forEach(function (a) { a.addEventListener("click", function () { menue.close(); }); });
    window.addEventListener("resize", function () { if (window.innerWidth > 1060 && menue.open) menue.close(); });
  } else if (menueKnopf) {
    /* Fallback für sehr alte Browser: Menü-Knopf führt zur Übersicht im Fuß */
    menueKnopf.addEventListener("click", function () { location.hash = "seiten"; });
  }

  /* ---------- Dezentes Einblenden, einmal ---------- */
  var zeigen = d.querySelectorAll("[data-zeigen]");
  if (zeigen.length && "IntersectionObserver" in window && !ruhig) {
    d.documentElement.classList.add("js");
    var io = new IntersectionObserver(function (eintraege) {
      eintraege.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add("ist-sichtbar"); io.unobserve(e.target); }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.05 });
    zeigen.forEach(function (el) {
      var gruppe = el.getAttribute("data-zeigen");
      if (gruppe) el.style.setProperty("--v", (parseInt(gruppe, 10) * 80) + "ms");
      io.observe(el);
    });
  }

  /* ---------- Angebotsindex: Bild folgt dem Eintrag (nur mit Maus/Tastatur, ab Tablet quer) ---------- */
  var angebot = d.querySelector(".angebot");
  if (angebot) {
    var bilder = angebot.querySelectorAll(".angebot__bilder img");
    var eintraege = angebot.querySelectorAll(".angebot__eintrag");
    var aktiv = function (i) {
      bilder.forEach(function (b, n) { b.classList.toggle("ist-aktiv", n === i); });
      eintraege.forEach(function (e, n) { e.classList.toggle("ist-aktiv", n === i); });
    };
    eintraege.forEach(function (e, i) {
      e.addEventListener("mouseenter", function () { aktiv(i); });
      e.addEventListener("focus", function () { aktiv(i); });
    });
  }

  /* ---------- Sprungnavigation auf Unterseiten markiert den sichtbaren Abschnitt ---------- */
  var inhaltNav = d.querySelector(".inhalt-nav");
  if (inhaltNav && "IntersectionObserver" in window) {
    var links = [].slice.call(inhaltNav.querySelectorAll("a[href^='#']"));
    var ziele = links.map(function (a) { return d.getElementById(a.getAttribute("href").slice(1)); });
    var io2 = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (e.isIntersecting) {
          links.forEach(function (a) {
            var an = a.getAttribute("href") === "#" + e.target.id;
            a.classList.toggle("ist-aktiv", an);
            if (an) a.setAttribute("aria-current", "location"); else a.removeAttribute("aria-current");
          });
        }
      });
    }, { rootMargin: "-35% 0px -60% 0px" });
    ziele.forEach(function (z) { if (z) io2.observe(z); });
  }

  /* ---------- Galerie: Filter und Lichtbild ---------- */
  var galerie = d.querySelector(".galerie");
  if (galerie) {
    var kacheln = [].slice.call(galerie.querySelectorAll("li"));
    var knoepfe = d.querySelectorAll(".filter button");
    var status = d.getElementById("galerie-status");
    knoepfe.forEach(function (k) {
      k.addEventListener("click", function () {
        var album = k.getAttribute("data-filter");
        knoepfe.forEach(function (x) { x.setAttribute("aria-pressed", x === k ? "true" : "false"); });
        var n = 0;
        kacheln.forEach(function (li) {
          var passt = album === "alle" || li.getAttribute("data-album") === album;
          li.hidden = !passt;
          if (passt) n++;
        });
        if (status) status.textContent = n + (n === 1 ? " Bild" : " Bilder") + " angezeigt";
      });
    });

    /* Albumlinks wie galerie.html#machmit filtern direkt */
    var anker = location.hash.slice(1);
    if (anker) {
      var passend = d.querySelector('.filter button[data-filter="' + anker + '"]');
      if (passend) passend.click();
    }

    var lb = d.getElementById("lichtbild");
    if (lb && typeof lb.showModal === "function") {
      var lbBild = lb.querySelector("img");
      var lbText = lb.querySelector(".lichtbild__text");
      var lbZahl = lb.querySelector(".lichtbild__zahl");
      var sichtbar = [];
      var pos = 0;
      var zeige = function (i) {
        pos = (i + sichtbar.length) % sichtbar.length;
        var a = sichtbar[pos];
        var img = a.querySelector("img");
        lbBild.src = a.getAttribute("href");
        lbBild.alt = img.alt;
        lbText.textContent = img.alt;
        lbZahl.textContent = (pos + 1) + " / " + sichtbar.length;
        [pos + 1, pos - 1].forEach(function (j) {
          var nb = sichtbar[(j + sichtbar.length) % sichtbar.length];
          if (nb) { var v = new Image(); v.src = nb.getAttribute("href"); }
        });
      };
      var oeffner = null;
      galerie.addEventListener("click", function (e) {
        var a = e.target.closest("a");
        if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
        e.preventDefault();
        sichtbar = kacheln.filter(function (li) { return !li.hidden; }).map(function (li) { return li.querySelector("a"); });
        oeffner = a;
        zeige(sichtbar.indexOf(a));
        lb.showModal();
        d.body.style.overflow = "hidden";
      });
      lb.querySelector(".zurueck").addEventListener("click", function () { zeige(pos - 1); });
      lb.querySelector(".vor").addEventListener("click", function () { zeige(pos + 1); });
      lb.querySelector(".schliessen").addEventListener("click", function () { lb.close(); });
      lb.addEventListener("close", function () { d.body.style.overflow = ""; if (oeffner) oeffner.focus(); });
      lb.addEventListener("keydown", function (e) {
        if (e.key === "ArrowLeft") { e.preventDefault(); zeige(pos - 1); }
        if (e.key === "ArrowRight") { e.preventDefault(); zeige(pos + 1); }
      });
      /* Klick auf den dunklen Hintergrund schließt */
      lb.querySelector(".lichtbild__buehne").addEventListener("click", function (e) { if (e.target === e.currentTarget) lb.close(); });
      /* Wischen auf Touch-Geräten */
      var startX = null;
      var buehne = lb.querySelector(".lichtbild__buehne");
      buehne.addEventListener("pointerdown", function (e) { startX = e.clientX; });
      buehne.addEventListener("pointerup", function (e) {
        if (startX === null) return;
        var dx = e.clientX - startX; startX = null;
        if (Math.abs(dx) > 50) zeige(dx < 0 ? pos + 1 : pos - 1);
      });
    }
  }

  /* ---------- Kontaktformular: bereitet eine E-Mail im eigenen Programm vor ---------- */
  var form = d.getElementById("anfrage");
  if (form) {
    var anliegen = form.querySelector("#f-anliegen");
    var param = new URLSearchParams(location.search).get("anliegen");
    if (param && anliegen) {
      [].forEach.call(anliegen.options, function (o) { if (o.value === param) anliegen.value = param; });
    }
    var meldung = d.getElementById("anfrage-status");
    var fehler = function (feld, text) {
      var f = form.querySelector("#" + feld.id + "-fehler");
      if (text) { feld.setAttribute("aria-invalid", "true"); if (f) f.textContent = text; }
      else { feld.removeAttribute("aria-invalid"); if (f) f.textContent = ""; }
      return !text;
    };
    form.querySelectorAll("input, textarea").forEach(function (feld) {
      feld.addEventListener("input", function () { if (feld.value.trim()) fehler(feld, ""); });
    });
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var name = form.querySelector("#f-name");
      var nachricht = form.querySelector("#f-nachricht");
      var tel = form.querySelector("#f-telefon");
      var ok = fehler(name, name.value.trim() ? "" : "Bitte geben Sie Ihren Namen an.");
      ok = fehler(nachricht, nachricht.value.trim() ? "" : "Bitte schreiben Sie kurz, worum es geht.") && ok;
      if (!ok) { form.querySelector("[aria-invalid='true']").focus(); return; }
      var thema = anliegen.options[anliegen.selectedIndex].text;
      var text = [
        "Guten Tag,", "", nachricht.value.trim(), "",
        "Viele Grüße", name.value.trim(),
        tel.value.trim() ? "Telefon: " + tel.value.trim() : ""
      ].join("\n").trim();
      var ziel = "mailto:tc@philathlos.de?subject=" + encodeURIComponent("Anfrage: " + thema) + "&body=" + encodeURIComponent(text);
      window.location.href = ziel;
      if (meldung) {
        meldung.hidden = false;
        meldung.focus();
      }
    });
  }
})();

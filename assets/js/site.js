/* T.C. Philathlos München – Interaktionen und Bewegung.
   Ohne Cookies, ohne Tracking. GSAP und ScrollTrigger liegen selbst gehostet
   unter assets/js/lib/. Alles funktioniert auch ohne JavaScript und ohne GSAP;
   hier wird nur verfeinert. Wer „Bewegung reduzieren“ eingestellt hat, sieht
   alles sofort und ohne Animation. */
(function () {
  "use strict";
  var d = document;
  var h = d.documentElement;
  var ruhig = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var G = window.gsap;
  var ST = window.ScrollTrigger;
  var mitBewegung = !!(G && ST && !ruhig);

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
    var menueTl = null;
    if (mitBewegung) {
      menue.classList.add("ist-animiert");
      menueTl = G.timeline({ paused: true, defaults: { ease: "power3.out" } })
        .fromTo(menue, { opacity: 0 }, { opacity: 1, duration: 0.3 })
        .fromTo(menue.querySelectorAll("nav a"), { yPercent: 100 }, { yPercent: 0, duration: 0.6, stagger: 0.045 }, 0.05)
        .fromTo(menue.querySelectorAll(".menue__kontakt > *"), { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.45, stagger: 0.05 }, 0.25);
    }
    var schliessen = function () {
      if (!menue.open) return;
      if (!menueTl) { menue.close(); return; }
      G.to(menue, { opacity: 0, duration: 0.2, ease: "power1.in", onComplete: function () { menue.close(); G.set(menue, { clearProps: "opacity" }); } });
    };
    menueKnopf.addEventListener("click", function () {
      menue.showModal();
      menueKnopf.setAttribute("aria-expanded", "true");
      d.body.style.overflow = "hidden";
      if (menueTl) menueTl.restart();
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

  /* ---------- Angebotsindex: Bild folgt dem Eintrag (Maus und Tastatur, ab Tablet quer) ---------- */
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
        var gezeigt = [];
        kacheln.forEach(function (li) {
          var passt = album === "alle" || li.getAttribute("data-album") === album;
          li.hidden = !passt;
          if (passt) { n++; gezeigt.push(li); }
        });
        if (status) status.textContent = n + (n === 1 ? " Bild" : " Bilder") + " angezeigt";
        if (mitBewegung) {
          G.fromTo(gezeigt, { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: 0.5, stagger: 0.03, ease: "power2.out", overwrite: true });
          ST.refresh();
        }
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
      var zeige = function (i, richtung) {
        pos = (i + sichtbar.length) % sichtbar.length;
        var a = sichtbar[pos];
        var img = a.querySelector("img");
        lbBild.src = a.getAttribute("href");
        lbBild.alt = img.alt;
        lbText.textContent = img.alt;
        lbZahl.textContent = (pos + 1) + " / " + sichtbar.length;
        if (mitBewegung && richtung) {
          G.fromTo(lbBild, { opacity: 0, x: 24 * richtung }, { opacity: 1, x: 0, duration: 0.45, ease: "power2.out", overwrite: true });
        }
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
      lb.querySelector(".zurueck").addEventListener("click", function () { zeige(pos - 1, -1); });
      lb.querySelector(".vor").addEventListener("click", function () { zeige(pos + 1, 1); });
      lb.querySelector(".schliessen").addEventListener("click", function () { lb.close(); });
      lb.addEventListener("close", function () { d.body.style.overflow = ""; if (oeffner) oeffner.focus(); });
      lb.addEventListener("keydown", function (e) {
        if (e.key === "ArrowLeft") { e.preventDefault(); zeige(pos - 1, -1); }
        if (e.key === "ArrowRight") { e.preventDefault(); zeige(pos + 1, 1); }
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
        if (Math.abs(dx) > 50) zeige(dx < 0 ? pos + 1 : pos - 1, dx < 0 ? 1 : -1);
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

  /* =====================================================================
     Bewegung mit GSAP
     Ein inszenierter Moment beim Laden (der Platz zeichnet sich, die
     Überschrift steigt zeilenweise auf), danach nur noch Bewegung, die auf
     das Scrollen antwortet. Nichts wiederholt sich, nichts läuft dauerhaft.
     Inhalte bleiben im Barrierefreiheitsbaum (nur opacity, kein visibility).
     ===================================================================== */
  function bewegung() {
    G.registerPlugin(ST);
    G.defaults({ ease: "power3.out", duration: 0.9 });

    /* --- Startbereich: der Platz zeichnet sich --- */
    var start = d.querySelector(".start");
    if (start) {
      var linien = start.querySelectorAll(".platz__linien .kreide");
      linien.forEach(function (l) { l.setAttribute("pathLength", "1"); });
      var tl = G.timeline({ defaults: { ease: "power3.out" } });
      tl.fromTo(linien, { strokeDasharray: "1 1", strokeDashoffset: 1, visibility: "visible" },
                        { strokeDashoffset: 0, duration: 1.15, stagger: 0.07, ease: "power2.inOut" }, 0)
        .fromTo(".platz__foto", { clipPath: "inset(0% 0% 0% 100%)", visibility: "visible" },
                                { clipPath: "inset(0% 0% 0% 0%)", duration: 1.2, ease: "power3.inOut" }, 0.35)
        .fromTo(".platz__foto img", { scale: 1.18 }, { scale: 1, duration: 1.8, ease: "power2.out" }, 0.35)
        .fromTo(".start h1 .zeile > span", { yPercent: 108, visibility: "visible" },
                                           { yPercent: 0, duration: 1.05, stagger: 0.1 }, 0.1)
        .fromTo([".start__ort", ".start .lead", ".start__aktionen"], { opacity: 0, y: 18, visibility: "visible" },
                                                                     { opacity: 1, y: 0, duration: 0.8, stagger: 0.1 }, 0.5)
        .fromTo(".platz__ball", { opacity: 0, y: -46, visibility: "visible" },
                                { opacity: 1, y: 0, duration: 0.85, ease: "bounce.out" }, 1.15)
        .add(function () { G.set(linien, { clearProps: "strokeDasharray,strokeDashoffset" }); });
    }

    /* --- Einblenden beim Scrollen: zusammengehörige Elemente versetzt --- */
    var zeigen = G.utils.toArray("[data-zeigen]");
    if (zeigen.length) {
      G.set(zeigen, { opacity: 0, y: 28 });
      ST.batch(zeigen, {
        start: "top 88%",
        once: true,
        onEnter: function (stapel) {
          G.to(stapel, { opacity: 1, y: 0, duration: 0.95, stagger: 0.09, overwrite: true, clearProps: "transform" });
        }
      });
    }

    /* --- Bilder öffnen sich von unten, das Foto setzt sich dabei --- */
    G.utils.toArray(".bild__rahmen").forEach(function (r) {
      if (r.closest(".start")) return;
      var img = r.querySelector("img");
      var tlb = G.timeline({ scrollTrigger: { trigger: r, start: "top 86%", once: true } });
      tlb.fromTo(r, { clipPath: "inset(14% 0% 0% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", duration: 1.2, ease: "power3.out" })
         .fromTo(img, { scale: 1.14 }, { scale: 1, duration: 1.6, ease: "power2.out" }, 0);
      var fig = r.closest(".bild");
      var unter = fig && fig.querySelector("figcaption");
      if (unter) tlb.fromTo(unter, { opacity: 0 }, { opacity: 1, duration: 0.6 }, 0.5);
    });

    /* --- Schlusszeile: Zeile für Zeile --- */
    var schluss = d.querySelectorAll(".schluss h2 span");
    if (schluss.length) {
      G.fromTo(schluss, { opacity: 0, y: 36 }, {
        opacity: 1, y: 0, duration: 1, stagger: 0.12,
        scrollTrigger: { trigger: ".schluss h2", start: "top 85%", once: true }
      });
    }

    /* --- Steckbrief: die fünf Plätze nacheinander --- */
    var plaetze = d.querySelectorAll(".plaetze svg");
    if (plaetze.length) {
      G.fromTo(plaetze, { opacity: 0, y: 8 }, {
        opacity: 1, y: 0, duration: 0.5, stagger: 0.07, delay: 0.2,
        scrollTrigger: { trigger: ".plaetze", start: "top 92%", once: true }
      });
    }

    /* --- Dezente Parallaxe, nur auf größeren Bildschirmen --- */
    var mm = G.matchMedia();
    mm.add("(min-width: 900px)", function () {
      if (start) {
        G.to(".platz", { yPercent: 7, ease: "none", scrollTrigger: { trigger: start, start: "top top", end: "bottom top", scrub: true } });
      }
      G.utils.toArray(".bild--parallax .bild__rahmen").forEach(function (r) {
        var img = r.querySelector("img");
        G.set(img, { scale: 1.12 });
        G.fromTo(img, { yPercent: -5 }, { yPercent: 5, ease: "none", scrollTrigger: { trigger: r, start: "top bottom", end: "bottom top", scrub: true } });
      });
      var klein = d.querySelector(".abend__klein");
      if (klein) {
        G.fromTo(klein, { y: 60 }, { y: -40, ease: "none", scrollTrigger: { trigger: ".abend", start: "top bottom", end: "bottom top", scrub: true } });
      }
      var angebotBild = d.querySelector(".angebot__bilder");
      if (angebotBild) {
        G.fromTo(angebotBild, { clipPath: "inset(10% 0% 10% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", ease: "none", scrollTrigger: { trigger: ".angebot", start: "top 85%", end: "top 35%", scrub: true } });
      }
    });

    /* Positionen nach dem Laden der Schriften neu berechnen */
    if (d.fonts && d.fonts.ready) d.fonts.ready.then(function () { ST.refresh(); });
    window.addEventListener("load", function () { ST.refresh(); });
  }

  try {
    if (mitBewegung) bewegung();
  } finally {
    /* Startzustände liegen jetzt inline an den Elementen; die CSS-Sperre wird nicht mehr gebraucht. */
    h.classList.remove("bewegt");
  }
})();

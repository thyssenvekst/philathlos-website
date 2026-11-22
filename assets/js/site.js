/* T.C. Philathlos München – Interaktionen und Bewegung („Platzraster“).
   Ohne Cookies, ohne Tracking. GSAP 3.12.5 und ScrollTrigger liegen selbst
   gehostet unter assets/js/lib/. Alles funktioniert auch ohne JavaScript und
   ohne GSAP; hier wird nur verfeinert. Wer „Bewegung reduzieren“ eingestellt
   hat, sieht alles sofort und ohne Animation. */
(function () {
  "use strict";
  var d = document;
  var h = d.documentElement;
  var ruhig = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var G = window.gsap;
  var ST = window.ScrollTrigger;
  var mitBewegung = !!(G && ST && !ruhig);

  /* ---------- Kopf: verschwindet beim Herunterscrollen, kommt beim Hochscrollen ---------- */
  var kopf = d.querySelector(".kopf");
  if (kopf) {
    var letzte = window.scrollY;
    var wartet = false;
    var pruefen = function () {
      var y = window.scrollY;
      kopf.classList.toggle("ist-gerollt", y > 8);
      var offen = d.querySelector("dialog[open]");
      if (!ruhig && !offen && y > 320 && y > letzte + 4) kopf.classList.add("ist-weg");
      else if (y < letzte - 4 || y <= 320) kopf.classList.remove("ist-weg");
      letzte = y;
      wartet = false;
    };
    window.addEventListener("scroll", function () { if (!wartet) { wartet = true; requestAnimationFrame(pruefen); } }, { passive: true });
    /* Tastatur: Fokus im Kopf holt ihn zurück */
    kopf.addEventListener("focusin", function () { kopf.classList.remove("ist-weg"); });
    pruefen();
  }

  /* ---------- Menü (natives dialog: Fokusfalle und Esc inklusive) ---------- */
  var menue = d.getElementById("menue");
  var menueKnopf = d.querySelector(".menue-knopf");
  if (menue && menueKnopf && typeof menue.showModal === "function") {
    var menueTl = null;
    if (mitBewegung) {
      menue.classList.add("ist-animiert");
      menueTl = G.timeline({ paused: true, defaults: { ease: "power3.out" } })
        .fromTo(menue, { opacity: 0 }, { opacity: 1, duration: 0.25 })
        .fromTo(menue.querySelectorAll(".menue__nav a"), { yPercent: 105 }, { yPercent: 0, duration: 0.7, stagger: 0.04 }, 0.05)
        .fromTo(menue.querySelectorAll(".menue__kontakt > *"), { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.45, stagger: 0.04 }, 0.3);
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
    window.addEventListener("resize", function () { if (window.innerWidth >= 1240 && menue.open) menue.close(); });
  } else if (menueKnopf) {
    menueKnopf.addEventListener("click", function () { location.hash = "seiten"; });
  }

  /* ---------- Index „Was es bei uns gibt“: Bild wechselt mit Maus und Tastatur ---------- */
  var index = d.querySelector("[data-index]");
  if (index) {
    var bilder = index.querySelectorAll(".index__bilder img");
    var eintraege = index.querySelectorAll(".index__eintrag");
    var aktiv = function (i) {
      bilder.forEach(function (b, n) { b.classList.toggle("ist-aktiv", n === i); });
      eintraege.forEach(function (e, n) { e.classList.toggle("ist-aktiv", n === i); });
    };
    eintraege.forEach(function (e, i) {
      var a = e.querySelector("a");
      a.addEventListener("mouseenter", function () { aktiv(i); });
      a.addEventListener("focus", function () { aktiv(i); });
    });
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
     ===================================================================== */
  /* Grundsatz: ein inszenierter Moment beim Laden, danach nur Antworten auf
     das Scrollen. Nichts wiederholt sich, nichts läuft dauerhaft. Bewegt
     werden nur transform, opacity, clip-path und Strichlängen. */
  function bewegung() {
    G.registerPlugin(ST);
    G.defaults({ ease: "power3.out", duration: 0.9 });
    var alle = function (sel, ctx) { return [].slice.call((ctx || d).querySelectorAll(sel)); };
    var platz = d.querySelector(".platz");

    /* 1 · Der Platz: Linien werden gezogen wie nach dem Abziehen, dann Foto und Schrift */
    if (platz) {
      var linien = [];
      ["grund", "seite", "auf", "mitte", "mark"].forEach(function (art) {
        linien = linien.concat(alle(".platz__linie--" + art, platz));
      });
      var netz = alle(".platz__netz", platz);
      G.set(linien.concat(netz), { strokeDasharray: 1, strokeDashoffset: 1 });
      var foto = platz.querySelector(".platz__foto");
      var tl = G.timeline({ delay: 0.15 });
      tl.to(linien, { strokeDashoffset: 0, duration: 0.85, ease: "power2.inOut", stagger: 0.07 }, 0)
        .to(netz, { strokeDashoffset: 0, duration: 0.7, ease: "power2.inOut" }, 0.35);
      if (foto) {
        tl.fromTo(foto, { autoAlpha: 1, clipPath: "inset(0% 0% 100% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", duration: 1.15, ease: "power3.inOut" }, 0.55)
          .fromTo(foto.querySelector("img"), { scale: 1.18 }, { scale: 1, duration: 1.8, ease: "power2.out" }, 0.55);
      }
      tl.fromTo(".platz__titel", { autoAlpha: 0, y: 26 }, { autoAlpha: 1, y: 0, duration: 1 }, 0.7)
        .fromTo(".platz__unten", { autoAlpha: 0, y: 20 }, { autoAlpha: 1, y: 0, duration: 1 }, 0.9)
        .fromTo(".platz__gasse", { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.8, stagger: 0.1 }, 1.1);
    }

    /* 2 · Seitenköpfe: Titel steigt auf, Bilder öffnen sich. Einmal beim Laden */
    var auftritt = alle("[data-auftritt]");
    if (auftritt.length) {
      var kt = G.timeline({ delay: 0.1 });
      auftritt.forEach(function (el, i) {
        var rahmen = el.matches(".bild") ? el.querySelector(".bild__rahmen") : null;
        var t0 = i * 0.12;
        if (rahmen) {
          kt.set(el, { autoAlpha: 1 }, t0)
            .fromTo(rahmen, { clipPath: "inset(100% 0% 0% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", duration: 1.2, ease: "power3.inOut" }, t0)
            .fromTo(rahmen.querySelector("img"), { scale: 1.15 }, { scale: 1, duration: 1.7, ease: "power2.out" }, t0);
        } else {
          kt.fromTo(el, { autoAlpha: 0, y: 28 }, { autoAlpha: 1, y: 0, duration: 1 }, t0);
        }
      });
    }

    /* 3 · Texte beim Scrollen: einmal, kurz, nur leicht angehoben */
    var zeigen = alle("[data-zeigen]");
    if (zeigen.length) {
      G.set(zeigen, { opacity: 0, y: 24 });
      ST.batch(zeigen, {
        start: "top 90%",
        once: true,
        onEnter: function (els) {
          G.to(els, { opacity: 1, y: 0, duration: 0.85, stagger: 0.08, overwrite: true, clearProps: "transform" });
        }
      });
    }

    /* 4 · Inhaltsbilder öffnen sich von unten; große Bilder bekommen ab 900 px leichte Parallaxe */
    var parallaxe = ".jugend__bild, .abend__bild, .paar__bild";
    alle(".bild .bild__rahmen").forEach(function (rahmen) {
      if (rahmen.closest(".platz, [data-auftritt], .index__bilder")) return;
      var img = rahmen.querySelector("img");
      var t = G.timeline({ scrollTrigger: { trigger: rahmen, start: "top 88%", once: true } });
      t.fromTo(rahmen, { clipPath: "inset(14% 0% 0% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", duration: 1.2 });
      if (!rahmen.closest(parallaxe)) t.fromTo(img, { scale: 1.12 }, { scale: 1, duration: 1.6, ease: "power2.out" }, 0);
      var cap = rahmen.parentNode.querySelector("figcaption");
      if (cap) t.fromTo(cap, { opacity: 0 }, { opacity: 1, duration: 0.6 }, 0.5);
    });

    var mm = G.matchMedia();
    mm.add("(min-width: 900px)", function () {
      alle(".jugend__bild img, .abend__bild img, .paar__bild img").forEach(function (img) {
        G.fromTo(img, { yPercent: -4, scale: 1.1 }, {
          yPercent: 4, scale: 1.1, ease: "none",
          scrollTrigger: { trigger: img.closest(".bild"), start: "top bottom", end: "bottom top", scrub: 0.6 }
        });
      });
      var stehend = d.querySelector(".index__bilder .bild__rahmen");
      if (stehend) {
        G.fromTo(stehend, { clipPath: "inset(0% 0% 100% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", duration: 1.3, ease: "power3.inOut", scrollTrigger: { trigger: stehend, start: "top 85%", once: true } });
      }
    });
    mm.add("(max-width: 899px)", function () {
      alle(".jugend__bild img, .abend__bild img, .paar__bild img").forEach(function (img) {
        G.fromTo(img, { scale: 1.12 }, { scale: 1, duration: 1.6, ease: "power2.out", scrollTrigger: { trigger: img, start: "top 90%", once: true } });
      });
    });

    /* 5 · Beiträge: Aufschlag- und Mittellinie werden gezogen */
    alle(".beitraege").forEach(function (b) {
      var quer = b.querySelector(".beitraege__linie--quer");
      var laengs = b.querySelector(".beitraege__linie--laengs");
      if (!quer || !laengs) return;
      G.timeline({ scrollTrigger: { trigger: b, start: "top 75%", once: true } })
        .fromTo(quer, { scaleX: 0, transformOrigin: "0% 50%" }, { scaleX: 1, duration: 1.1, ease: "power2.inOut" })
        .fromTo(laengs, { scaleY: 0, transformOrigin: "50% 0%" }, { scaleY: 1, duration: 1.2, ease: "power2.inOut" }, 0.55);
    });

    /* Nachberechnung, wenn Schriften und Bilder da sind */
    if (d.fonts && d.fonts.ready) d.fonts.ready.then(function () { ST.refresh(); });
    window.addEventListener("load", function () { ST.refresh(); });
  }

  try {
    if (mitBewegung) bewegung();
  } finally {
    h.classList.remove("bewegt");
  }
})();

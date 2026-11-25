/* T.C. Philathlos München – Interaktionen und Bewegung („Pergola“).
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
        .fromTo(menue.querySelectorAll(".menue__nav li"), { autoAlpha: 0, y: 14 }, { autoAlpha: 1, y: 0, duration: 0.5, stagger: 0.035 }, 0.05)
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
    window.addEventListener("resize", function () { if (window.innerWidth >= 1180 && menue.open) menue.close(); });
  } else if (menueKnopf) {
    menueKnopf.addEventListener("click", function () { location.hash = "seiten"; });
  }

  /* ---------- Galerie: Filter und Lichtbild ---------- */
  var galerie = d.querySelector(".galerie");
  if (galerie) {
    var kacheln = [].slice.call(galerie.querySelectorAll("li"));
    var knoepfe = d.querySelectorAll(".filter button");
    var status = d.getElementById("galerie-status");
    /* Lücken am Ende des Rasters schließen: Die Kachel über einer leeren Zelle wird eine Reihe höher,
       sonst wird die Kachel links daneben breiter. Läuft nach jedem Filtern und bei neuer Breite. */
    var lueckenSchliessen = function () {
      var sicht = kacheln.filter(function (li) { return !li.hidden; });
      sicht.forEach(function (li) { li.style.gridRow = ""; li.style.gridColumn = ""; });
      if (!sicht.length) return;
      var cs = getComputedStyle(galerie);
      var spalten = cs.gridTemplateColumns.split(" ").length;
      var luecke = parseFloat(cs.rowGap) || 0;
      var g = galerie.getBoundingClientRect();
      var zeileH = parseFloat(cs.gridAutoRows) + luecke;
      if (!(zeileH > luecke)) return;
      var spalteB = (g.width + luecke) / spalten;
      var belegt = {}, info = [], letzte = 0;
      sicht.forEach(function (li) {
        var r = li.getBoundingClientRect();
        var z0 = Math.round((r.top - g.top) / zeileH), z1 = Math.round((r.bottom - g.top + luecke) / zeileH);
        var s0 = Math.round((r.left - g.left) / spalteB), s1 = Math.round((r.right - g.left + luecke) / spalteB);
        for (var z = z0; z < z1; z++) for (var s = s0; s < s1; s++) belegt[z + "," + s] = li;
        info.push({ li: li, z0: z0, z1: z1, s0: s0, s1: s1 });
        letzte = Math.max(letzte, z1);
      });
      var finde = function (li) { return info.filter(function (x) { return x.li === li; })[0]; };
      var geaendert = [];
      for (var s = 0; s < spalten; s++) {
        var z = letzte - 1;
        if (belegt[z + "," + s]) continue;
        var oben = belegt[(z - 1) + "," + s], o = oben && finde(oben);
        if (o && o.z1 === z && geaendert.indexOf(oben) < 0) {
          oben.style.gridRow = "span " + (o.z1 - o.z0 + 1);
          geaendert.push(oben);
          continue;
        }
        for (var l = s - 1; l >= 0; l--) {
          var links = belegt[z + "," + l];
          if (!links) continue;
          var x = finde(links);
          if (x.z0 === z && x.z1 === z + 1 && geaendert.indexOf(links) < 0) {
            var bis = s; while (bis + 1 < spalten && !belegt[z + "," + (bis + 1)]) bis++;
            links.style.gridColumn = (x.s0 + 1) + " / " + (bis + 2);
            geaendert.push(links);
          }
          break;
        }
      }
    };
    lueckenSchliessen();
    var lueckenZeit;
    window.addEventListener("resize", function () { clearTimeout(lueckenZeit); lueckenZeit = setTimeout(lueckenSchliessen, 150); });
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
        lueckenSchliessen();
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
      var lbBild = lb.querySelector(".lichtbild__buehne img") || lb.querySelector(".lichtbild__buehne").appendChild(d.createElement("img"));
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

  /* ---------- Inhalte aus der Verwaltung: Hinweis, schwarzes Brett, Termine, Archiv ----------
     Die Seiten funktionieren auch ohne diese Daten (statische Inhalte bleiben stehen).
     Alles wird mit textContent gesetzt, nie als HTML: kein Einschleusen von Code möglich. */
  var brauchtInhalte = d.querySelector("[data-hinweis],[data-aushang],[data-brett],[data-termine],[data-archiv]");
  if (brauchtInhalte && window.fetch && location.protocol !== "file:") {
    var MONATE = ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"];
    var TAGE = ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"];
    var datum = function (iso, mitTag) {
      var t = iso.split("-").map(Number);
      var dt = new Date(t[0], t[1] - 1, t[2]);
      return (mitTag ? TAGE[dt.getDay()] + ", " : "") + t[2] + ". " + MONATE[t[1] - 1] + " " + t[0];
    };
    var el = function (tag, klasse, text) {
      var e = d.createElement(tag);
      if (klasse) e.className = klasse;
      if (text != null) e.textContent = text;
      return e;
    };
    /* Text mit Internetadressen als Links, sicher als Textknoten */
    var absatz = function (text) {
      var p = el("p");
      text.split(/(https?:\/\/[^\s<>"]+)/g).forEach(function (teil, i) {
        if (i % 2) {
          var url = teil.replace(/[.,;:!?)]+$/, "");
          var a = el("a", null, url.replace(/^https?:\/\/(www\.)?/, ""));
          a.href = url;
          a.rel = "noopener";
          p.appendChild(a);
          if (url.length < teil.length) p.appendChild(d.createTextNode(teil.slice(url.length)));
        } else if (teil) {
          teil.split("\n").forEach(function (z, j) { if (j) p.appendChild(el("br")); p.appendChild(d.createTextNode(z)); });
        }
      });
      return p;
    };
    var sicher = function (url) { return /^(https?:\/\/|mailto:|tel:|\/|[\w-]+\.html)/i.test(url || "") ? url : null; };
    var eintrag = function (x, art) {
      var li = el("li", "brett__eintrag" + (x.wichtig ? " brett__eintrag--wichtig" : ""));
      li.id = x.id;
      var wann = el("div", "brett__wann");
      if (x.wichtig) wann.appendChild(el("span", "brett__marke", "Wichtig"));
      var zeit = el("time", null, datum(x.datum, art === "termin"));
      zeit.dateTime = x.datum;
      wann.appendChild(zeit);
      if (art === "termin" && x.beginn) wann.appendChild(el("span", "brett__zeit", x.beginn + (x.ende ? "–" + x.ende : "") + " Uhr"));
      li.appendChild(wann);
      var was = el("div", "brett__was");
      was.appendChild(el("h3", null, x.titel));
      if (art === "termin") was.appendChild(el("p", "brett__ort", x.ort || "Auf der Anlage, Föhringer Ring 5"));
      (x.absaetze || []).forEach(function (t) { was.appendChild(absatz(t)); });
      if (x.anhang && x.anhang.url) {
        var a = el("a", "weiter brett__pdf", x.anhang.titel + " (PDF)");
        a.href = x.anhang.url;
        a.target = "_blank";
        a.rel = "noopener";
        was.appendChild(a);
      }
      li.appendChild(was);
      if (x.bild && x.bild.url) {
        var fig = el("figure", "foto brett__bild");
        var img = el("img");
        img.src = x.bild.klein || x.bild.url;
        img.alt = x.bild.alt || "";
        img.loading = "lazy";
        img.width = x.bild.breite || 800;
        img.height = x.bild.hoehe || 600;
        fig.appendChild(img);
        li.appendChild(fig);
      }
      return li;
    };

    fetch("api/inhalte.php", { headers: { Accept: "application/json" }, cache: "no-store" })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (daten) {
        /* Hinweis oben auf der Startseite */
        var band = d.querySelector("[data-hinweis]");
        if (band && daten.hinweis && daten.hinweis.text) {
          var p = band.querySelector("p");
          p.textContent = daten.hinweis.text + " ";
          var ziel = sicher(daten.hinweis.link);
          if (ziel) { var a = el("a", null, daten.hinweis.linktext || "Mehr erfahren"); a.href = ziel; p.appendChild(a); }
          band.hidden = false;
        }
        /* Startseite „Am Aushang“: wichtige Mitteilungen und die nächsten Termine vorn */
        var aushang = d.querySelector("[data-aushang]");
        if (aushang) {
          var neu = [];
          (daten.mitteilungen || []).filter(function (m) { return m.wichtig; }).slice(0, 2).forEach(function (m) { neu.push([m, "Mitteilung", "aktuelles.html#" + m.id]); });
          (daten.termine || []).slice(0, 3).forEach(function (t) { neu.push([t, "Termin", "aktuelles.html#" + t.id]); });
          neu.reverse().forEach(function (n) {
            var x = n[0];
            var li = el("li", "aushang__neu" + (x.wichtig ? " aushang__neu--wichtig" : ""));
            li.appendChild(el("span", "aushang__wann", n[1] === "Termin" ? datum(x.datum, true) + (x.beginn ? ", " + x.beginn + " Uhr" : "") : (x.wichtig ? "Wichtig" : datum(x.datum))));
            var was = el("div", "aushang__was");
            was.appendChild(el("h3", null, x.titel));
            if (x.absaetze && x.absaetze[0]) was.appendChild(el("p", null, x.absaetze[0].length > 160 ? x.absaetze[0].slice(0, 157) + " …" : x.absaetze[0]));
            li.appendChild(was);
            var a = el("a", "aushang__wie weiter", n[1] === "Termin" ? "Zum Termin" : "Ganze Mitteilung");
            a.href = n[2];
            li.appendChild(a);
            aushang.insertBefore(li, aushang.firstChild);
          });
        }
        /* Aktuelles: schwarzes Brett, Termine, Archiv */
        var fuellen = function (sel, liste, art, leer) {
          var sek = d.querySelector(sel);
          if (!sek) return;
          var ul = sek.querySelector("ul");
          /* Der leere Hinweis steht schon im HTML (sichtbar auch ohne Verwaltung); bei Einträgen weicht er */
          if (liste.length) {
            [].forEach.call(ul.querySelectorAll(".brett__leer"), function (l) { l.remove(); });
            liste.forEach(function (x) { ul.appendChild(eintrag(x, art)); });
          } else if (leer && !ul.querySelector(".brett__leer")) ul.appendChild(el("li", "brett__leer", leer));
          sek.hidden = false;
        };
        fuellen("[data-brett]", daten.mitteilungen || [], "mitteilung", "Zurzeit gibt es keine neuen Mitteilungen.");
        fuellen("[data-termine]", daten.termine || [], "termin", "Die nächsten Termine werden hier eingetragen, sobald sie feststehen.");
        var archiv = [].concat((daten.archiv && daten.archiv.termine || []).map(function (t) { t._art = "termin"; return t; }),
          (daten.archiv && daten.archiv.mitteilungen || []).map(function (m) { m._art = "mitteilung"; m.wichtig = false; return m; }))
          .sort(function (a, b) { return a.datum < b.datum ? 1 : -1; });
        var arch = d.querySelector("[data-archiv]");
        if (arch && archiv.length) {
          var ula = arch.querySelector("ul");
          archiv.forEach(function (x) { ula.appendChild(eintrag(x, x._art)); });
          arch.hidden = false;
        }
        /* Sprungziel wie aktuelles.html#t3 nach dem Einfügen anspringen */
        if (location.hash && /^#[mt]\d+$/.test(location.hash)) {
          var z = d.getElementById(location.hash.slice(1));
          if (z) { var det = z.closest("details"); if (det) det.open = true; z.scrollIntoView(); }
        }
        if (window.ScrollTrigger) window.ScrollTrigger.refresh();
      })
      .catch(function () { /* ohne Verwaltung (z. B. rein statisches Hosting) bleiben die festen Inhalte stehen */ });
  }

  /* ---------- Telefonleiste (schmal): erscheint nach dem ersten Bildschirm ---------- */
  var leiste = d.querySelector(".leiste");
  if (leiste) {
    var leisteWartet = false;
    var leistePruefen = function () {
      var unten = window.innerHeight + window.scrollY >= d.documentElement.scrollHeight - 40;
      leiste.classList.toggle("ist-da", window.scrollY > window.innerHeight * 0.7 && !unten);
      leisteWartet = false;
    };
    window.addEventListener("scroll", function () { if (!leisteWartet) { leisteWartet = true; requestAnimationFrame(leistePruefen); } }, { passive: true });
    leistePruefen();
  }

  /* =====================================================================
     Bewegung mit GSAP
     ===================================================================== */
  /* Grundsatz: ein inszenierter Moment beim Laden (die Fotos des Kopfes),
     beim Scrollen öffnen sich Fotos einmal leicht, und das Motto wird Wort
     für Wort dunkel. Texte bewegen sich beim Scrollen nicht. */
  function bewegung() {
    G.registerPlugin(ST);
    G.defaults({ ease: "power3.out" });
    var alle = function (sel, ctx) { return [].slice.call((ctx || d).querySelectorAll(sel)); };

    /* 1 · Kopf der Seite: Fotos öffnen sich nacheinander von unten, danach der Text */
    var kopfFotos = alle(".diptychon .foto, [data-auftritt] .foto");
    var kopfText = alle("[data-auftritt-text]");
    var tl = G.timeline({ delay: 0.1 });
    var parallaxe = window.matchMedia("(min-width: 900px)").matches;
    kopfFotos.forEach(function (f, i) {
      var img = f.querySelector("img");
      tl.set(img, { visibility: "visible" }, i * 0.18)
        .fromTo(f, { clipPath: "inset(100% 0% 0% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", duration: 1.15, ease: "power3.inOut", clearProps: "clipPath" }, i * 0.18);
      /* Im Diptychon übernimmt ab 900 px die Parallaxe die Bildgröße */
      if (!(parallaxe && f.closest(".diptychon"))) tl.fromTo(img, { scale: 1.12 }, { scale: 1, duration: 1.7, clearProps: "transform" }, i * 0.18);
    });
    if (kopfText.length) {
      tl.fromTo(kopfText, { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: 0.9, stagger: 0.1, clearProps: "transform" }, kopfFotos.length ? 0.55 : 0);
    }

    /* 2 · Fotos im Inhalt: öffnen sich einmal leicht beim Hineinscrollen */
    alle(".foto").forEach(function (f) {
      if (f.closest(".diptychon, [data-auftritt]")) return;
      var img = f.querySelector("img");
      G.timeline({ scrollTrigger: { trigger: f, start: "top 90%", once: true } })
        .fromTo(f, { clipPath: "inset(8% 0% 0% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", duration: 1.1, clearProps: "clipPath" })
        .fromTo(img, { scale: 1.06 }, { scale: 1, duration: 1.4, clearProps: "transform" }, 0);
    });

    /* 3 · Motto: Wort für Wort von Grau zu Tinte, an die Scrollposition gebunden */
    alle("[data-motto] p").forEach(function (p) {
      var worte = p.textContent.trim().split(/\s+/);
      var text = p.textContent.trim();
      p.innerHTML = '<span class="sr">' + text + "</span>" + worte.map(function (w) { return '<span class="wort" aria-hidden="true">' + w + "</span>"; }).join(" ");
      var spans = alle(".wort", p);
      G.fromTo(spans, { color: "#7a8089" }, {
        color: "#171c26", ease: "none", stagger: 0.5,
        scrollTrigger: { trigger: p, start: "top 78%", end: "bottom 42%", scrub: 0.4 }
      });
    });

    /* 4 · Nur auf großen Bildschirmen: Tiefe im Kopf der Startseite und der gestapelte Rundgang */
    var mm = G.matchMedia();
    mm.add("(min-width: 900px)", function () {
      /* Parallaxe: das Tagfoto bewegt sich langsamer als die Seite, das Abendfoto etwas schneller.
         Beide sind leicht vergrößert, damit keine Kante sichtbar wird. */
      alle(".diptychon .foto").forEach(function (f, i) {
        var img = f.querySelector("img");
        G.fromTo(img, { yPercent: 0, scale: 1.12 }, {
          yPercent: i ? 9 : 6, scale: 1.12, ease: "none",
          scrollTrigger: { trigger: ".diptychon", start: "top top", end: "bottom top", scrub: true }
        });
      });
    });
    mm.add("(min-width: 900px) and (min-height: 640px)", function () {
      /* Rundgang: die bedeckte Station tritt etwas zurück, während die nächste darüber gleitet */
      var stationen = alle(".rundgang__station");
      stationen.forEach(function (st, i) {
        var naechste = stationen[i + 1];
        if (!naechste) return;
        G.to(st, {
          scale: 0.965, ease: "none",
          scrollTrigger: { trigger: naechste, start: "top bottom", end: "top " + (16 + (i + 1) * 52) + "px", scrub: true }
        });
      });
    });

    if (d.fonts && d.fonts.ready) d.fonts.ready.then(function () { ST.refresh(); });
    window.addEventListener("load", function () { ST.refresh(); });
  }

  try {
    if (mitBewegung) bewegung();
  } finally {
    h.classList.remove("bewegt");
  }
})();

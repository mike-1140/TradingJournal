<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradingJournal – Handbuch</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #f5f7f4; }
        .docs-topbar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
        }
        .docs-nav .nav-link {
            color: #495057;
            padding: 0.35rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.9rem;
        }
        .docs-nav .nav-link.active {
            background: rgba(15, 110, 92, 0.1);
            color: #0f6e5c;
            font-weight: 600;
        }
        .docs-nav .nav-link i { width: 1.1rem; }
        section.docs-topic {
            scroll-margin-top: 5.5rem;
            padding-top: 2.5rem;
            border-top: 1px solid #dee2e6;
        }
        section.docs-topic:first-of-type { border-top: none; padding-top: 0; }
        .docs-route {
            font-family: var(--bs-font-monospace, monospace);
            font-size: 0.75rem;
            color: #8b9691;
        }
        .docs-subcard {
            border-left: 3px solid #0f6e5c;
        }
        .docs-detail-tag {
            display: inline-block;
            font-family: var(--bs-font-monospace, monospace);
            font-size: 0.68rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #a66a1e;
            background: #f8ecd8;
            padding: 0.1rem 0.45rem;
            border-radius: 0.3rem;
            margin-right: 0.4rem;
        }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#docs-nav" data-bs-offset="90" tabindex="0">

<header class="docs-topbar py-2 px-3 d-flex align-items-center justify-content-between sticky-top">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-graph-up-arrow text-success fs-5"></i>
        <strong>TradingJournal</strong>
        <span class="text-muted">Handbuch</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.close()">
        <i class="bi bi-x-lg"></i> Schließen
    </button>
</header>

<div class="container-fluid py-4" style="max-width: 1180px;">
    <div class="row">
        {{-- ── Inhaltsverzeichnis ─────────────────────────────────────── --}}
        <div class="col-lg-3 mb-4">
            <nav id="docs-nav" class="docs-nav d-flex flex-column gap-1 position-sticky" style="top: 5rem;">
                <a class="nav-link" href="#dashboard"><i class="bi bi-house"></i> Dashboard</a>
                <a class="nav-link" href="#aktien"><i class="bi bi-bar-chart-line"></i> Aktien</a>
                <a class="nav-link" href="#optionen"><i class="bi bi-layers"></i> Optionen</a>
                <a class="nav-link" href="#kampagnen"><i class="bi bi-currency-exchange"></i> Kampagnen</a>
                <a class="nav-link" href="#spreads"><i class="bi bi-bezier2"></i> Spreads</a>
                <a class="nav-link" href="#log"><i class="bi bi-journal-plus"></i> Log</a>
                <a class="nav-link" href="#psychologie"><i class="bi bi-journal-check"></i> Psychologie</a>
                <a class="nav-link" href="#analyse"><i class="bi bi-speedometer2"></i> Analyse</a>
                <hr class="my-1">
                <a class="nav-link" href="#global"><i class="bi bi-sliders"></i> Globale Funktionen</a>
            </nav>
        </div>

        {{-- ── Inhalt ──────────────────────────────────────────────────── --}}
        <div class="col-lg-9">

            <div class="mb-4" style="max-width: 68ch;">
                <h1 class="h3 mb-2">Ein Rundgang durch dein Trading-Journal</h1>
                <p class="text-muted">
                    Jede Kampagne, jeder Spread und jede Zeile im Ledger hat hier ihren festen Platz — dieses
                    Handbuch führt dich einmal in Ruhe durch alle Stationen der App, erklärt, wofür sie jeweils
                    gedacht sind, und zeigt, welche Kennzahlen, Tabellen und Charts dich auf der jeweiligen Seite
                    und ihrer Detailansicht erwarten.
                </p>
            </div>

            {{-- ── Globale Funktionen ────────────────────────────────────── --}}
            <div id="global" class="card mb-5">
                <div class="card-body">
                    <h2 class="text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.1em;">Gilt seitenübergreifend</h2>
                    <div class="row g-4 mt-1">
                        <div class="col-md-4">
                            <h3 class="h6"><i class="bi bi-database text-success"></i> Umgebung</h3>
                            <p class="small text-muted mb-2">
                                Fünf Welten, eine App: Das farbige Badge oben rechts verrät auf einen Blick, in
                                welcher Datenbank-Umgebung du gerade unterwegs bist — vom scharfen Echtgeldkonto
                                bis zur risikofreien Testspielwiese.
                            </p>
                            <div class="d-flex gap-1 flex-wrap">
                                <span class="badge bg-success">MONEY</span>
                                <span class="badge bg-warning text-dark">PAPER</span>
                                <span class="badge bg-secondary">TEST</span>
                                <span class="badge" style="background:#fd7e14;">AUTOM_MONEY</span>
                                <span class="badge" style="background:#6f42c1;">AUTOM_PAPER</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h3 class="h6"><i class="bi bi-calendar3 text-success"></i> Zeitraum-Filter</h3>
                            <p class="small text-muted mb-0">
                                Der Kalender-Button ist die Zeitmaschine der App: Ein Klick, und so gut wie jede
                                Liste, Kennzahl und jeder Chart zieht sich auf das gewünschte Fenster zusammen —
                                Schnellauswahl über 13 Wochen, 6 Monate oder YTD, ein bestimmtes Jahr, oder ein
                                frei gewählter Zeitraum. Die Wahl bleibt bestehen, bis du sie änderst.
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h3 class="h6"><i class="bi bi-journal-plus text-success"></i> Schnelleintrag</h3>
                            <p class="small text-muted mb-0">
                                Wenn eine Idee gerade frisch im Kopf ist, soll sie nicht im Menü verloren gehen:
                                Der gelbe Button oben rechts katapultiert dich ohne Umwege direkt ins
                                Entscheidungsjournal, um einen neuen Trade-Plan festzuhalten.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Dashboard ──────────────────────────────────────────────── --}}
            <section id="dashboard" class="docs-topic">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-house fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Dashboard</h2>
                        <div class="docs-route">/</div>
                    </div>
                </div>
                <p class="text-muted" style="max-width: 68ch;">
                    Die erste Seite nach dem Login — die Kommandobrücke, von der aus du mit einem Blick erfassen
                    kannst, wie es um dein Portfolio steht und wo gerade Risiko lauert, bevor du dich in ein Detail
                    vertiefst.
                </p>
                <ul class="text-muted">
                    <li class="mb-2"><strong class="text-body">Kennzahlen-Kacheln</strong> — dein Cockpit auf einen Blick: P&amp;L für Monat, YTD und gewählten Zeitraum, Trefferquote, Ø P&amp;L pro Trade, Profit Factor, Max Drawdown, Reward/Risk, größter Gewinn und größter Verlust, verfügbares Cash in USD und EUR sowie der aktuelle Kontostand mit Änderung und TWRR</li>
                    <li class="mb-2"><strong class="text-body">Charts</strong> — die Performance in Bildern: der kumulierte P&amp;L-Verlauf, eine Aufschlüsselung nach Strategie, die monatliche Entwicklung, die Verteilung nach Instrument und der Kontostand-Verlauf über die Zeit</li>
                    <li class="mb-2"><strong class="text-body">Tabelle offener Positionen</strong> — alles, was gerade noch am Laufen ist, mit Symbol, Typ, Einstieg, Stückzahl, Preis, Cashflow, Strategie und Status</li>
                    <li>Eine Kachel mit den aktiven Kampagnen sowie eine Tabelle „Optionen, die diese Woche verfallen“ mit farblich markierter Resttage-Anzeige, damit kein Verfallstermin überrascht</li>
                </ul>
            </section>

            {{-- ── Aktien ──────────────────────────────────────────────────── --}}
            <section id="aktien" class="docs-topic">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-bar-chart-line fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Aktien</h2>
                        <div class="docs-route">/trades/open/stocks · /trades/closed/stocks</div>
                    </div>
                </div>
                <p class="text-muted" style="max-width: 68ch;">
                    Der klassische Aktienbestand, frei von Options-Komplexität — alles, was du an Stücken hältst
                    oder gehalten hast, sauber getrennt nach offen und geschlossen.
                </p>

                <div class="card docs-subcard mb-3">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Offene Positionen <span class="docs-route">trades.open.stocks</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Eine Tabelle all dessen, was gerade in deinem Depot liegt: Symbol, Datum, Stückzahl, Preis, Cashflow, Kauf oder Verkauf — dazu Kacheln für die Anzahl offener Positionen, das gebundene Kapital und die bereits angefallenen Kommissionen</li>
                            <li><span class="docs-detail-tag">Detailseite</span> Ein Klick auf eine Zeile öffnet die volle Akte der Position: Kennzahlen-Kacheln zu Preis, Menge, Cashflow und gehaltenen Tagen, dazu Blöcke zu Identifikation, Trade-Info, Zeitinfo sowie Preisen &amp; Cashflows. Stammt die Position aus einer Options-Zuteilung, erzählt ein eigener Herkunfts-Block, welche Prämie du dafür schon kassiert hattest und wie sich daraus dein effektiver Einstandspreis ergibt — und falls du den Trade im Psychologie-Modul reflektiert hast, führt ein Link direkt dorthin</li>
                        </ul>
                    </div>
                </div>

                <div class="card docs-subcard">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Geschlossene Positionen <span class="docs-route">trades.closed.stocks</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Dieselbe Grundstruktur wie bei den offenen Positionen, jetzt aber mit dem ganzen Nachspann eines abgeschlossenen Trades: Haltedauer, Brutto- und Netto-P&amp;L, ROI und die verwendete Strategie</li>
                            <li><span class="docs-detail-tag">Detailseite</span> zusätzlich mit dem realisierten Ergebnis in USD und, umgerechnet, in EUR</li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- ── Optionen ────────────────────────────────────────────────── --}}
            <section id="optionen" class="docs-topic">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-layers fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Optionen</h2>
                        <div class="docs-route">/trades/open/options · /trades/closed/options</div>
                    </div>
                </div>
                <p class="text-muted" style="max-width: 68ch;">
                    Das Pendant zur Aktien-Ansicht, nur für die Welt der Prämien, Strikes und Verfallstermine —
                    nach demselben Muster aufgebaut, aber um alles ergänzt, was eine Option ausmacht.
                </p>
                <ul class="text-muted">
                    <li class="mb-2">Zusätzlich zu den bekannten Aktien-Feldern: ein Put/Call-Badge, der Strike, das Verfallsdatum mit deutlichem Hinweis, sobald eine Option bereits verfallen ist, die kassierte Prämie und eine Spalte „50%-Ziel“ als Richtwert, ab wann sich ein vorzeitiges Schließen lohnen könnte</li>
                    <li><span class="docs-detail-tag">Detailseite</span> ergänzt um den Multiplikator, die Optionsstrategie-Felder und die Wechselkurse, die bei Eröffnung und Schließung galten</li>
                </ul>
            </section>

            {{-- ── Kampagnen ───────────────────────────────────────────────── --}}
            <section id="kampagnen" class="docs-topic">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-currency-exchange fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Kampagnen</h2>
                        <div class="docs-route">/campaigns/campaigns</div>
                    </div>
                </div>
                <p class="text-muted" style="max-width: 68ch;">
                    Eine Kampagne ist die Klammer um alles, was zu einem Symbol gehört — etwa ein Covered Call,
                    der über mehrere Zyklen gerollt wurde. Statt vieler loser Einzeltrades erzählt sie die
                    durchgehende Geschichte einer Position von der Eröffnung bis zum letzten Cent.
                </p>
                <ul class="text-muted">
                    <li class="mb-2"><strong class="text-body">Liste</strong> Eine Karte pro Kampagne mit Symbol, Status (ACTIVE / PENDING / CLOSED), Brutto- und Netto-P&amp;L, aktuellem Bestand, Proceeds, Kommission und einem Badge, das verrät, ob die Kampagne bereits im Log dokumentiert ist; dazu ein Statusfilter und eine Symbolsuche über alle Kampagnen hinweg</li>
                    <li><span class="docs-detail-tag">Detailseite</span> Hier wird aus Zahlen eine Geschichte: 6 Kennzahlen-Kacheln zu Aktien-/Optionsbestand, Cashflow, Kommission sowie Brutto- und Netto-P&amp;L, ein gestufter P&amp;L-Verlaufschart, eine Tagesübersicht und die vollständige Buchungshistorie (Ledger) mit Klartext-Beschreibung jeder einzelnen Buchung, laufendem Bestand und Farbcodierung für zusammengehörige Buchungen — etwa eine Rollposition, deren Legs sonst leicht auseinanderfallen würden</li>
                </ul>
            </section>

            {{-- ── Spreads ─────────────────────────────────────────────────── --}}
            <section id="spreads" class="docs-topic">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-bezier2 fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Spreads</h2>
                        <div class="docs-route">/spreads</div>
                    </div>
                </div>
                <p class="text-muted" style="max-width: 68ch;">
                    Manche Optionsstrategien bestehen nicht aus einem, sondern aus mehreren zusammenspielenden
                    Legs — Vertical Spreads, Iron Condors und Verwandte. Dieser Bereich betrachtet sie als das,
                    was sie eigentlich sind: eine einzige, in sich geschlossene Konstruktion, nicht eine Handvoll
                    unabhängiger Einzelpositionen.
                </p>
                <div class="card docs-subcard">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Offene Spreads · Geschlossene Spreads · Vertical Spreads · Iron Condors <span class="docs-route">spreads.list</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Vier Blickwinkel auf dieselbe Liste, je nach Filter. Kennzahlen-Kacheln zeigen Anzahl sowie Trefferquote bzw. Ø Prämie oder Profit</li>
                            <li class="mb-2">Die Tabelle liefert Strategie, Underlying, Strikes (Put und Call sauber getrennt), Verfall bzw. Resttage, Prämie oder Profit (Verkaufs- und Kaufseite einzeln ausgewiesen), „% of max“ sowie Max-Gewinn, 50%-Ziel und Max-Verlust</li>
                            <li><span class="docs-detail-tag">Detailseite</span> Bei Iron Condors zeichnet eine visuelle Darstellung die Put-/Call-Spread-Struktur samt Gewinnzone regelrecht nach, ergänzt um die Tabelle der einzelnen Legs, einen Performance-Block mit ROI und maximalem Risiko sowie die Verknüpfung zum passenden Psychologie-Eintrag</li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- ── Log ─────────────────────────────────────────────────────── --}}
            <section id="log" class="docs-topic">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-journal-plus fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Log</h2>
                        <div class="docs-route">/log</div>
                    </div>
                </div>
                <p class="text-muted" style="max-width: 68ch;">
                    Der eigentliche Kern eines Trading-Journals: nicht das Ergebnis zählt allein, sondern der
                    Gedanke dahinter. Hier hältst du fest, warum ein Trade überhaupt eingegangen wurde — mit
                    welchem Plan, welcher Erwartung und welchem Ausstiegsszenario — statt nur zu notieren, wie er
                    am Ende ausging.
                </p>

                <div class="card docs-subcard mb-3">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Übersicht <span class="docs-route">log.list</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Eine Hinweisbox macht sanft darauf aufmerksam, welche Kampagnen noch gar nicht im Log dokumentiert sind, und schlägt gleich passende Verknüpfungen vor, statt dich suchen zu lassen</li>
                            <li>Kennzahlen-Kacheln für offene, geschlossene und realisierte Einträge, dazu eine Tabelle mit Datum, Underlying, Strategie, Exit-Plan, Legs, Status und Ergebnis</li>
                        </ul>
                    </div>
                </div>
                <div class="card docs-subcard">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Neuer Eintrag · Bearbeiten <span class="docs-route">log.create / log.quick / log.edit</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Kopfdaten erfassen den Kontext der Entscheidung: Datum, Underlying, Kurs, Gebühren, IVP/IVR, Strategie, Exit-Plan und die Begründung dahinter in eigenen Worten</li>
                            <li class="mb-2">Eine dynamische Legs-Tabelle bildet die Struktur des Trades ab: Typ, Restlaufzeit, Strike, Delta, Prämie und Max-Risiko</li>
                            <li>Beim Schließen ergänzt du Kurs, Prämie, realisiertes Ergebnis und Notizen — der Kreis von Plan zu Ausgang schließt sich</li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- ── Psychologie ─────────────────────────────────────────────── --}}
            <section id="psychologie" class="docs-topic">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-journal-check fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Psychologie</h2>
                        <div class="docs-route">/psychology</div>
                    </div>
                </div>
                <p class="text-muted" style="max-width: 68ch;">
                    Das ruhigste und ehrlichste Modul der App: Es trennt bewusst das Ergebnis eines Trades von der
                    Frage, wie er zustande kam. Ein Gewinn aus einer disziplinierten Entscheidung und ein Gewinn
                    aus einem Impuls sehen im P&amp;L identisch aus — hier wird sichtbar, welcher der beiden es
                    wirklich war.
                </p>

                <div class="card docs-subcard mb-3">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Einträge <span class="docs-route">psychology.list</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Kennzahlen-Kacheln zu Anzahl der Einträge, Gesamt-P&amp;L, Trefferquote und dem Anteil der Trades, bei denen eine Versuchung im Spiel war</li>
                            <li>Je Eintrag eine Karte mit Datum, Symbol, einem Motivations-Badge (System / FOMO / Revenge / Langeweile / Impuls), der Regel-Compliance in grün oder rot, dem P&amp;L und der daraus gezogenen Lektion</li>
                        </ul>
                    </div>
                </div>
                <div class="card docs-subcard mb-3">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Neuer Eintrag <span class="docs-route">psychology.create</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Symbol, Datum, P&amp;L, eine Emotions-Skala von 1 bis 5 vor und nach dem Trade, die Wahl der Motivation und der Marktkontext, in dem die Entscheidung fiel</li>
                            <li>Eine Regelwerk-Auswahl mit Compliance-Grid und Versuchungs-Checkliste, dazu ein Freitextfeld für die Lektion, die du daraus mitnimmst</li>
                        </ul>
                    </div>
                </div>
                <div class="card docs-subcard mb-3">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Auswertung <span class="docs-route">psychology.analytics</span></h3>
                        <ul class="text-muted mb-0">
                            <li>Vier Balkendiagramme machen Muster sichtbar, die im Kopf leicht verschwimmen: Ø P&amp;L je Motivation, Ø P&amp;L je Emotionszustand, Ø P&amp;L je Compliance-Grad und die Häufigkeit von Versuchungen je Regel</li>
                        </ul>
                    </div>
                </div>
                <div class="card docs-subcard">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Regelwerke <span class="docs-route">psychology.rulesets</span></h3>
                        <ul class="text-muted mb-0">
                            <li>Hier entstehen die Maßstäbe selbst: Verwaltung von Regelwerken (Name, Strategietyp, aktiv/inaktiv) und ihren Einzelregeln (Bezeichnung, Fehlertyp: Gier / Angst / Hoffnung / Impuls / Disziplin) — genau die Regeln, an denen jeder Eintrag oben gemessen wird</li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- ── Analyse ─────────────────────────────────────────────────── --}}
            <section id="analyse" class="docs-topic">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-speedometer2 fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Analyse</h2>
                        <div class="docs-route">/symbols · /stats · /trades/raw</div>
                    </div>
                </div>
                <p class="text-muted" style="max-width: 68ch;">
                    Vier Ansichten für alle, die tiefer graben wollen, als es das Tagesgeschäft erlaubt — vom
                    Symbol über die reine Statistik bis hinunter zum unbearbeiteten Rohdatensatz aus dem
                    Broker-Import.
                </p>

                <div class="card docs-subcard mb-3">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Symbole <span class="docs-route">symbols</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Eine Tabelle aller je gehandelten Symbole mit Anzahl der Kampagnen, letzter Aktivität, Brutto-/Netto-P&amp;L und Trefferquote — die Vogelperspektive über deine gesamte Handelshistorie, sortiert nach Ticker</li>
                            <li><span class="docs-detail-tag">Detailseite</span> Kennzahlen-Kacheln, ein kumulierter P&amp;L-Chart und die Tabelle aller Kampagnen zu genau diesem Symbol — die komplette Biografie eines Tickers in deinem Depot</li>
                        </ul>
                    </div>
                </div>
                <div class="card docs-subcard mb-3">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Stats <span class="docs-route">stats.dashboard</span></h3>
                        <ul class="text-muted mb-0">
                            <li>Eine Tabelle nach Zeitraum — Tag, Woche, Monat oder Jahr, frei umschaltbar und navigierbar — mit P&amp;L, Veränderung, Buchungen, Gewinn-/Verlust-Anzahl, Profit Factor und Kommission, für alle, die ihre Zahlen lieber im Kalender-Raster als im Chart lesen</li>
                        </ul>
                    </div>
                </div>
                <div class="card docs-subcard mb-3">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Aktivität <span class="docs-route">stats.activity</span></h3>
                        <ul class="text-muted mb-0">
                            <li>Eine wochenweise durchblätterbare Buchungshistorie über alle Symbole hinweg — dieselbe Detailtiefe wie die Kampagnen-Historie, nur nicht auf ein einzelnes Symbol beschränkt, sondern als lückenloses Logbuch über die ganze Woche</li>
                        </ul>
                    </div>
                </div>
                <div class="card docs-subcard">
                    <div class="card-body">
                        <h3 class="h6 d-flex align-items-baseline gap-2 flex-wrap">Raw Data <span class="docs-route">trades.raw</span></h3>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Die unverfälschte Quelle: eine Tabelle aller Roh-Trades aus dem IBKR-Import inklusive Verarbeitungsstatus, Trade-/Order-ID und Herkunft — für den Fall, dass du einer Zahl bis zu ihrem Ursprung folgen willst</li>
                            <li><span class="docs-detail-tag">Detailseite</span> Instrument-, Finanz-, Options- und Verarbeitungsblock, dazu verknüpfte Roh-Trades, etwa bei Teilausführungen, die sich über mehrere Zeilen verteilen</li>
                        </ul>
                    </div>
                </div>
            </section>

            <footer class="text-muted small mt-5 pt-3 border-top" style="max-width: 68ch;">
                Stand {{ now()->format('d.m.Y') }} · Inhalt orientiert sich an der aktuellen Menüstruktur der App.
            </footer>

        </div>
    </div>
</div>

</body>
</html>

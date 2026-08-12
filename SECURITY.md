# Security Policy

## Unterstützte Version

Es gibt keine Versions-Branches — nur der aktuelle Stand von `master` wird gepflegt.

## Eine Sicherheitslücke melden

**Bitte keine öffentlichen Issues für Sicherheitslücken.** Stattdessen über GitHub Security
Advisories melden: Reiter **Security** → **Report a vulnerability** in diesem Repository.
Das erstellt eine private Meldung, die nur Maintainer und die von dir eingeladenen Personen
sehen.

Da dies ein Hobbyprojekt ohne festes Team ist, gilt best effort statt fester SLA — Meldungen
werden aber priorisiert vor normalen Feature-Issues behandelt.

## Was als Sicherheitslücke zählt

- Wege, an fremde Trading-/Kontodaten zu gelangen (z.B. SQL-Injection, fehlende
  Environment-Isolation zwischen Usern)
- Auth-Umgehung (Login, Passwort-Reset, Session-Handling)
- XSS/CSRF in Livewire-Komponenten
- Offenlegung von Secrets aus `.env`/`.env.testing` durch das Anwendungsverhalten selbst

**Kein Sicherheitsproblem im Sinne dieser Policy:** fehlende Rate-Limits auf einer rein lokal
gedachten Single-User-App, oder Themen, die ausschließlich die eigene lokale `.env`-Datei
betreffen (dafür siehe `docs/git-security.md` im Root-Repo, sofern verfügbar).

# TrustBridge

Platformă B2B de franciză pentru procese de eveniment, documentație GDPR și
media protejată. Acest repo e scheletul tehnic al [planului de execuție][roadmap] —
citește-l pe acela pentru context, decizii deschise și fazele complete
(Faza 0 → Faza 4).

[roadmap]: https://claude.ai/code/artifact/1984f791-44da-48fe-9186-e61ab1241a4b

## Ce e deja pus la punct

- **Laravel 13** (PHP 8.3) + **Inertia.js + React** (Breeze) — dashboard/SPA
  fără backend API separat.
- **spatie/laravel-permission** — pregătit pentru RBAC (roluri/permisiuni pe
  franciză → client → eveniment).
- **spatie/laravel-medialibrary** — bibliotecă media, pregătită pentru
  storage S3-compatible privat.
- **spatie/laravel-activitylog** — bază pentru audit log (§7 din roadmap).
- **laravel/fortify** — autentificare + **2FA obligatoriu** deja activat în
  `config/fortify.php` (`Features::twoFactorAuthentication`).
- **predis/predis** — client Redis pur PHP (fără extensie nativă necesară).
- **docker-compose.yml** — Postgres 16 + Redis + MinIO (stand-in local pentru
  object storage-ul S3 privat din UE) pentru când treci de pe sqlite local.
- **CI** (`.github/workflows/ci.yml`) — instalează, migrează pe Postgres,
  rulează testele la fiecare push/PR.

Nimic din logica de business (mandanți, evenimente, foto, cazuri GDPR) nu e
încă implementată — asta e Faza 1 și următoarele, conform roadmap-ului.

## Rulare locală

Acest calculator nu avea PHP/Composer instalate global, așa că am pus o
copie portabilă, fără drepturi de admin, în afara repo-ului:

```
C:\Users\Cartis\.dev-tools\php83\        (PHP 8.3.33)
C:\Users\Cartis\.dev-tools\composer\     (Composer 2.x)
```

Ambele foldere sunt deja adăugate la PATH-ul utilizatorului — **deschide un
terminal nou** (nu unul vechi) și `php -v` / `composer -v` ar trebui să
funcționeze direct.

```bash
composer install
npm install --legacy-peer-deps   # vezi nota despre vite mai jos
cp .env.example .env             # dacă .env nu există deja
php artisan key:generate
php artisan migrate
npm run dev                      # terminal 1: Vite
php artisan serve                # terminal 2: server Laravel
```

Implicit rulează pe **sqlite** (`database/database.sqlite`), fără nimic
altceva de pornit — suficient pentru dezvoltare Fază 1 timpurie.

> **Notă `--legacy-peer-deps`:** la data scaffolding-ului, `vite@8` era prea
> nou pentru peer-range-ul declarat de `@vitejs/plugin-react@4.7`, deși
> funcționează corect în practică. Verifică din când în când dacă poți
> renunța la flag (`npm install` fără el).

## Trecerea la Postgres + Redis + storage S3 (Docker)

Când ai Docker Desktop instalat:

```bash
docker compose up -d
```

Apoi copiază valorile din `.env.docker.example` peste cele echivalente din
`.env` (bază de date, sesiune, coadă, cache, storage). Codul aplicației nu
se schimbă — Laravel vorbește cu Postgres/Redis/S3 prin aceleași interfețe
indiferent de driver.

MinIO e doar un stand-in local pentru storage-ul privat S3-compatible din UE
cerut în roadmap (§3–4) — swap pe furnizorul real (Tárhely.eu sau
alternativă UE, vezi §10) doar schimbând `AWS_*` în `.env` de producție.

## Structura pe faze (vezi roadmap §2)

| Fază | Ce se construiește aici | Status |
|---|---|---|
| 0 | Decizii, arhitectură, aviz juridic | vezi roadmap §1 |
| 1 | Mandanți, franciză, roluri, 2FA, audit, eveniment | **scaffold gata, logică de business următoarea** |
| 2 | Șabloane, invitații, participanți, preferințe | — |
| 3 | Foto, galerie protejată, cazuri GDPR, ciclu 60/90 zile | — |
| 4 | Pilot, hardening, go-live | — |

### Primul tichet concret de Fază 1

Schema mandanților: migrații pentru `franchise_partners`, `organizations`
(clienți B2B), pivotul `organization_user` cu rol, și coloana `tenant_id`
pe fiecare tabel relevant — cu Row-Level Security sau echivalent server-side,
conform regulii din roadmap §5 ("fără excepție").

## Securitate — ce nu are voie să apară în cod

Vezi roadmap §7 pentru lista completă. Cele mai ușor de încălcat din
greșeală: fără fotografii în `/public` sau alt folder public, fără
Meta Pixel/SDK-uri social pe paginile de participant/galerie, fără cache
CDN pe media privată.

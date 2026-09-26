# Kundali — Vedic Birth Chart & Horoscope Reading

A Laravel application that generates astronomically accurate Vedic birth
charts (Kundali) and, from them, a full astrologer-style horoscope reading.

Every component is free and open source. There are no paid APIs, no API
keys, and no subscriptions — the astronomy runs locally against Swiss
Ephemeris and place lookup runs against an offline GeoNames extract.

---

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 (PHP 8.3–8.5) |
| Frontend | Livewire 3 + Tailwind CSS |
| Database | MySQL / MariaDB |
| Astronomy | Swiss Ephemeris (AGPL) — arc-second accuracy |
| Ayanamsa | Lahiri / Chitrapaksha |
| House system | Whole Sign (classical Parashari) |
| Place data | GeoNames (CC BY 4.0), 122,028 places offline |

---

## Architecture

The system is three strictly separated layers. The interpretation layer
never touches astronomy, which keeps calculations independently testable.

```
  Birth data (name, date, time, place, lat/lng)
        │
        ▼
  LAYER 1 — Astronomy            app/Services/Astrology/Ephemeris/
    local civil time → historical UTC offset → Julian Day
    → sidereal longitudes of 9 grahas + Ascendant
        │
        ▼
  LAYER 2 — Vedic mathematics    app/Services/Astrology/ChartCalculator.php
    Rashi · Nakshatra + pada · Bhava · dignity · Digbala
    combustion · retrogradity · aspects · lordships
    Navamsa (D9) · Vimshottari Dasha tree
        │
        ▼  ChartFacts (a plain, cacheable array)
        │
  LAYER 3 — Interpretation       (Phase 5–7, in progress)
    rule engine → conflict resolution → narrative composer
```

### Key files

| Path | Responsibility |
|---|---|
| `app/Services/Astrology/TimeResolver.php` | Historical timezone → UTC |
| `app/Services/Astrology/Ephemeris/SwissEphemeris.php` | Swiss Ephemeris driver |
| `app/Services/Astrology/ChartCalculator.php` | All Vedic derivations |
| `app/Services/Astrology/VimshottariDasha.php` | 120-year dasha tree |
| `app/Services/Astrology/ChartRenderer.php` | North/South Indian SVG charts |
| `app/Services/Astrology/Support/Zodiac.php` | Signs, lords, dignities, aspects |
| `app/Services/Astrology/Support/Nakshatras.php` | The 27 lunar mansions |

---

## Why the timezone code matters

Timezone handling — not the ephemeris — is the number one cause of wrong
Kundalis. A four-minute error shifts the Lagna by about one degree; a
one-hour error can move it into an adjacent sign and invalidate the whole
reading.

`TimeResolver` therefore resolves offsets through PHP's IANA database so
historical transitions are applied automatically:

- **Nepal** — UTC+5:30 before 1986-01-01, UTC+5:45 after
- **India** — UTC+5:30, with wartime DST in 1941–1945
- **Everywhere else** — correct DST for the era in question

When a birth predates the current offset for its location, the UI shows an
explicit notice so the value can be sanity-checked against the birth
certificate.

---

## Setup

### Prerequisites

- PHP **8.3+** with `mbstring`, `xml`, `curl`, `zip`, `intl`, `bcmath`, `pdo_mysql`
- Composer 2
- Node 18+
- MySQL 8 / MariaDB 10.6+
- `git`, `make` and a C compiler (to build the ephemeris binary)

### Install

```bash
git clone https://github.com/niranjanrimal25/kundali-v2.git
cd kundali-v2

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Set your database credentials in `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=kundali
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Build the Swiss Ephemeris binary (once per machine — the compiled binary
is platform-specific and therefore not committed):

```bash
php artisan jyotish:install-ephemeris
```

Create the schema and seed the admin account plus the place database:

```bash
php artisan migrate
php artisan db:seed
```

Run it:

```bash
php artisan serve
```

Default login (override with `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env`
before seeding):

```
admin@kundali.test
password
```

---

## Configuration

Astrological conventions live in `config/jyotish.php` and can be overridden
from `.env`:

```env
JYOTISH_AYANAMSA=lahiri        # lahiri | raman | krishnamurti | fagan_bradley | yukteshwar
JYOTISH_HOUSE_SYSTEM=W         # W = Whole Sign, P = Placidus, K = Koch, E = Equal
JYOTISH_NODE=true              # true = True Node, mean = Mean Node
```

---

## Tests

```bash
php artisan test
```

69 tests / 598 assertions, covering:

- **Timezone correctness** — the Nepal 1986 transition, IST, DST, southern hemisphere
- **Astronomy** — Lahiri ayanamsa value, Whole Sign house assignment, Rahu/Ketu opposition
- **Vedic rules** — exaltation, debilitation, Digbala, special aspects, lordships
- **Dasha** — balance at birth, sequence order, sub-periods tiling without gaps
- **Determinism** — identical input always yields an identical chart
- **Sensitivity** — birth time and latitude genuinely affect the Lagna
- **Application flow** — autocomplete, validation, caching, authorisation, deletion

---

## Project status

| Phase | Scope | Status |
|---|---|---|
| 1 | Scaffold, auth, schema, city database | Done |
| 2 | Swiss Ephemeris + timezone engine + tests | Done |
| 3 | Vedic math layer → ChartFacts | Done |
| 4 | SVG charts (North/South), planet & house tables | Done |
| 5 | Interpretation rule corpus | Next |
| 6 | Narrative composer + full reading page | Next |
| 7 | Yogas, doshas, Sade Sati, remedies | Planned |
| 8 | Cross-validation, PDF export | Planned |

See `SPEC.md` for the full specification and `QUESTIONS-AND-ANSWERS.html`
for the recorded requirements decisions.

---

## Credits & licences

- **Swiss Ephemeris** — Astrodienst AG, AGPL-3.0
- **GeoNames** — geographical database, CC BY 4.0
- **Laravel** — MIT

# Kundali App — Technical Specification

**Stack:** Laravel 11 · Livewire 3 · Tailwind · MySQL · Swiss Ephemeris
**Cost:** Rs. 0 — every component is free/open-source, no APIs, no keys, no subscriptions.
**Status:** Specification agreed. Implementation not started.

---

## 1. Decisions Locked

| Area | Decision |
|---|---|
| Ephemeris engine | Swiss Ephemeris (AGPL) — arc-second accuracy |
| Ayanamsa | Lahiri / Chitrapaksha |
| House system | Whole Sign (classical Parashari) |
| Chart display | North Indian (default) + South Indian, toggle |
| Users | Single admin login (me only) |
| Storage | Every Kundali saved to DB, re-openable history |
| Place input | Free offline city database → auto lat/long |
| Timezone | Auto-resolved, historically accurate (Nepal +5:30 pre-1986, +5:45 after) |
| Reading depth | Full astrologer report |
| Language | English first, i18n-ready for Nepali |

---

## 2. Architecture — Three Layers

```
  [Birth Data]
       ↓
 LAYER 1 — Astronomy      (Swiss Ephemeris)
   local time → UTC → Julian Day
   → tropical longitudes of 9 grahas + Ascendant
       ↓
 LAYER 2 — Vedic Math     (pure PHP, deterministic)
   − ayanamsa → sidereal
   → Rashi, Nakshatra+pada, Bhava, dignity, Digbala,
     aspects, yogas, doshas, Vimshottari Dasha, D9
       ↓
 LAYER 3 — Interpretation (rule engine + narrative composer)
   chart facts → matched rules → weighted, conflict-resolved
   → flowing astrologer-style prose
```

The critical design principle: **Layer 2 outputs a complete structured
`ChartFacts` object.** Layer 3 never touches astronomy — it only reads facts.
This keeps interpretation testable and lets the reading be rewritten,
translated, or AI-polished later without risking calculation accuracy.

---

## 3. Layer 1 — Astronomy

### Engine
Swiss Ephemeris, invoked one of two ways (implementation picks whichever
installs cleanly on the target server):
- `swetest` CLI binary via Laravel's `Process` facade, or
- the `sweph` PHP extension for in-process calls.

Wrapped behind an `EphemerisInterface` so the backend is swappable and
a pure-PHP VSOP87 fallback can be dropped in later without touching callers.

### Time handling (the #1 source of wrong charts)
1. User enters **local civil time** at the birthplace.
2. City DB gives the IANA timezone (e.g. `Asia/Kathmandu`).
3. PHP's `DateTimeZone` applies the **historical** offset for that exact
   date — automatically correct for Nepal's 1986 change from +5:30 to +5:45,
   and for any DST that applied at other locations.
4. Convert to UT → Julian Day.

An error here of 4 minutes shifts the Lagna by ~1°; 2 hours can change the
Lagna sign entirely and invalidate the whole reading. This is handled once,
centrally, and unit-tested against known cases.

### Computed
- Sun, Moon, Mars, Mercury, Jupiter, Venus, Saturn — geocentric longitude,
  latitude, speed (speed sign → retrograde flag)
- Rahu / Ketu — **True Node** (configurable to Mean)
- Ascendant, MC, and house cusps for latitude/longitude
- Sidereal time, ayanamsa value for the date

---

## 4. Layer 2 — Vedic Derivations

**Per graha:** sidereal longitude → Rashi + degree-in-sign → Nakshatra +
pada → Bhava (Whole Sign from Lagna) → Navamsa (D9) position.

**Strength & condition:**
- Dignity: exalted / moolatrikona / own / friendly / neutral / enemy / debilitated
- **Digbala** (directional strength): Jupiter & Mercury strong in 1st (East),
  Sun & Mars in 10th (South), Saturn in 7th (West), Moon & Venus in 4th (North)
- Combustion (proximity to Sun, per-planet orbs)
- Retrogradity, planetary war, Vargottama
- Shadbala summary score

**Relationships:**
- Graha Drishti (full 7th for all; special 4/8 Mars, 5/9 Jupiter, 3/10 Saturn)
- Conjunctions, house-lord (Bhavesha) placements — the backbone of prediction
- Exchange (Parivartana) and mutual aspects

**Yogas & Doshas detected:** Gajakesari, Budhaditya, Chandra-Mangala,
Panch Mahapurusha (Ruchaka/Bhadra/Hamsa/Malavya/Sasa), Raja yogas,
Dhana yogas, Kemadruma, Daridra, Vipareeta Raja, Neecha Bhanga,
**Mangal/Kuja Dosha**, Kaal Sarp, Pitra Dosha, **Sade Sati** (with phase).

**Dasha:** full Vimshottari tree — Mahadasha → Antardasha → Pratyantardasha,
seeded from Moon's nakshatra, with the currently-running periods flagged
against today's date.

---

## 5. Layer 3 — The Reading

### Rule engine
A seeded MySQL corpus of interpretation fragments, each row carrying:
`condition_type`, `condition_key`, `weight`, `polarity`, `text`, `locale`.

Condition types cover:
1. Lagna sign + element + lord placement
2. House × sign (your Aries-in-1st example — element, Digbala, direction)
3. Graha × house (all 9 × 12 = 108)
4. Graha × sign (108)
5. Graha × house × dignity modifiers
6. House-lord in house (12 × 12 = 144) — the strongest predictive layer
7. Conjunctions & aspects
8. Yogas and doshas
9. Nakshatra of Moon (Janma Nakshatra) — temperament
10. Active Mahadasha/Antardasha themes

### Narrative composer
Raw rule hits are not dumped as bullets. The composer:
- **Resolves conflicts** — a debilitated planet's malefic reading is softened
  or cancelled by Neecha Bhanga; contradictory fragments get reconciled with
  concessive phrasing ("though ... the chart also shows ...") rather than
  printing both flatly.
- **Weights by strength** — strong, Digbala-empowered planets get emphatic
  language; weak or combust ones get hedged language.
- **Varies sentence construction** from a template pool so no two readings
  feel copy-pasted.
- **Structures output** into an astrologer's natural order:

```
  1. Overview — Lagna, Rashi, Nakshatra, chart character
  2. Personality & Physical Self (1st Bhava)
  3. Wealth, Family & Speech (2nd)
  4. Courage & Siblings (3rd)
  5. Home, Mother & Happiness (4th)
  6. Intellect, Children & Purva Punya (5th)
  7. Enemies, Debt & Health (6th)
  8. Marriage & Partnership (7th)
  9. Longevity & Transformation (8th)
 10. Fortune, Dharma & Father (9th)
 11. Career & Status (10th)
 12. Gains & Aspirations (11th)
 13. Loss, Expenditure & Moksha (12th)
 14. Yogas present in the chart
 15. Doshas and their cancellations
 16. Navamsa (D9) — marriage and inner strength
 17. Dasha timeline — what is running now and what comes next
 18. Remedies — gemstones, mantras, charities, fasting days
```

### Sample of target output quality
> Your Lagna falls in Mesha (Aries), a movable fire sign ruled by Mangal,
> rising at 14°22′. This grants you a forceful, pioneering temperament and
> considerable self-confidence — but the same fire turns inward when
> frustrated, producing impulsive decisions and anger that lands on the wrong
> target. Since your Lagnesh Mangal sits in the 8th Bhava, this restlessness
> is intensified rather than resolved, inclining you toward self-undoing
> through haste. However, Guru's 5th-house aspect onto the Lagna provides a
> restraining wisdom that matures noticeably after the age of 32.

That is the standard: specific degrees, named conditions, causal chains,
and qualified verdicts — not generic sun-sign filler.

---

## 6. Data Model

```
users                    single admin account
kundalis                 name, dob, tob, place, lat, lng, timezone,
                         gender, notes, created_at
chart_data               kundali_id, JSON: all Layer-2 computed facts
                         (cached — recomputed only if birth data edited)
readings                 kundali_id, locale, JSON: composed sections
interpretation_rules     the seeded corpus
cities                   free GeoNames dump: name, country, lat, lng, tz
```

---

## 7. Screens

1. **Login** — Laravel Breeze, single account
2. **Dashboard** — list of saved Kundalis, search, "New Kundali"
3. **New Kundali form** (Livewire) — name, gender, DOB (AD/BS toggle),
   time, place with live autocomplete filling lat/lng/timezone, with a
   manual-override option
4. **Chart view** — SVG North Indian diamond (default) / South Indian toggle,
   planet table with degrees, nakshatras, dignity, retrograde marks;
   panchanga summary; current dasha banner;
   **→ "View Full Details of this Kundali" button**
5. **Full reading** — the 18-section narrative report, printable

---

## 8. Accuracy Guarantees

- Planetary positions validated against Swiss Ephemeris reference data
- Full chart output cross-checked against Jagannatha Hora / AstroSage
  for a set of known birth charts, including pre-1986 Nepali births
  (validates the timezone logic) and southern-hemisphere births
  (validates Lagna math)
- Unit tests on: Julian Day conversion, ayanamsa, house assignment,
  nakshatra boundaries, Vimshottari dasha balance at birth
- Target: planetary longitudes within **1 arc-second**, Lagna within
  **1 arc-minute** of reference software

---

## 9. Build Phases

| Phase | Deliverable |
|---|---|
| 1 | Laravel scaffold, auth, DB schema, city database seeded |
| 2 | Swiss Ephemeris integration + timezone engine + accuracy tests |
| 3 | Vedic math layer → complete `ChartFacts` |
| 4 | SVG chart rendering, both styles, planet tables |
| 5 | Rule corpus seeding (largest content task) |
| 6 | Narrative composer + full reading page |
| 7 | Yogas, doshas, Sade Sati, D9, dasha timeline, remedies |
| 8 | Cross-validation against reference software, polish, PDF export |

---

## 10. Open Items for Later

- Nepali (Devanagari) translation of the rule corpus
- Bikram Sambat ↔ Gregorian date converter for the input form
- Optional LLM prose-polishing hook (structure already supports it)
- Transit / Gochar predictions
- Additional divisional charts (D10 career, D7 children)

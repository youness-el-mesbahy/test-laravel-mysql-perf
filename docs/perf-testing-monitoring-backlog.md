# Backlog — Laravel/MySQL Performance Testing & Monitoring Lab

Ordered. Do them top to bottom. Don't skip to monitoring before you have data to monitor.

---

## Phase 0 — Setup

- [ ] **T0.1** Scaffold or pick existing Laravel project (orders/products/customers schema, or reuse a side project).
- [ ] **T0.2** Confirm MySQL version, PHP version, environment (Docker preferred for reproducibility).
- [ ] **T0.3** Create `benchmarks.md` file to log every test result from here on.
- [ ] **T0.4** Write seeders/factories for 3+ tables with realistic relationships.
- [ ] **T0.5** Seed 50k–100k rows on the largest table. `php artisan db:seed` and go make coffee.
- [ ] **T0.6** Confirm dataset size is actually hurting query times (quick manual query check — if it's instant, seed more).

---

## Phase 1 — Baseline

- [ ] **T1.1** Build/confirm 2 endpoints: one cheap GET, one expensive (search, report, aggregation, whatever).
- [ ] **T1.2** Install k6 (or `ab` if you want zero setup).
- [ ] **T1.3** Write a basic k6 script hitting both endpoints.
- [ ] **T1.4** Run baseline load test at 10 concurrent users. Log RPS, p95 latency, error rate.
- [ ] **T1.5** Run same test at 50 concurrent users. Log results.
- [ ] **T1.6** Run same test at 200 concurrent users. Log results.
- [ ] **T1.7** Eyeball the degradation curve. Note where things start falling apart.

⚠️ Do this *before* installing any monitoring tools. You want a "before" picture with no scaffolding in the way.

---

## Phase 2 — Query Analysis

- [ ] **T2.1** Enable MySQL slow query log, `long_query_time = 0.5`.
- [ ] **T2.2** Re-run the expensive endpoint's load test, then check the slow log.
- [ ] **T2.3** Grab the worst query from the log, run `EXPLAIN` on it.
- [ ] **T2.4** Document what `EXPLAIN` shows — full scan? missing index? rows examined vs rows returned?
- [ ] **T2.5** Deliberately write one N+1 query into the expensive endpoint (loop over a collection accessing a relation without eager loading).
- [ ] **T2.6** Measure it — query count (Telescope or `DB::listen`), response time.
- [ ] **T2.7** Fix it with `with()`/`load()`. Measure again. Log before/after in `benchmarks.md`.

---

## Phase 3 — Monitoring Setup

- [ ] **T3.1** Install Laravel Telescope (dev environment only).
- [ ] **T3.2** Run Phase 1's load test again with Telescope watching. Screenshot what it captures.
- [ ] **T3.3** Install Laravel Pulse.
- [ ] **T3.4** Configure Pulse to surface slow requests and slow queries.
- [ ] **T3.5** Trigger a deliberate exception (bad input / missing record lookup) and confirm it shows up in Telescope/Pulse.
- [ ] **T3.6** Screenshot Pulse dashboard mid-load-test.

---

## Phase 4 — Fix & Re-Test Loop (Do This Twice)

- [ ] **T4.1** Pick the worst bottleneck found so far (likely N+1 or missing index).
- [ ] **T4.2** Fix it.
- [ ] **T4.3** Re-run the same load test from Phase 1, same load levels.
- [ ] **T4.4** Log the delta in `benchmarks.md` — RPS, p95, error rate, before vs after.
- [ ] **T4.5** Pick the *second* worst bottleneck.
- [ ] **T4.6** Fix it.
- [ ] **T4.7** Re-test, log delta again.

💡 If you only do one cycle, you'll think "fixing stuff = better numbers" without seeing *how much* each type of fix actually moves the needle. Two cycles minimum.

---

## Phase 5 — Wrap-Up

- [ ] **T5.1** Finalize `benchmarks.md` — clean table, all runs included, not just the good ones.
- [ ] **T5.2** Attach annotated `EXPLAIN` output (what it showed, what you changed because of it).
- [ ] **T5.3** Attach Telescope/Pulse screenshots showing captured slow query + N+1 + exception.
- [ ] **T5.4** Write the one-paragraph takeaway — biggest killer, why, in your own words.
- [ ] **T5.5** (Optional, next lab) Add Redis caching to the fixed endpoint, measure again. Don't do this before Phase 4 — it hides the real bottleneck.

---

## Definition of Done

You're done when you can explain — out loud, no notes — why your expensive endpoint got slow and exactly what fixed it. If you can't, go back to Phase 2. The tools did their job; you didn't finish yours yet.

# Requirements Doc — Learn Performance Testing & Monitoring (Laravel + MySQL)

Goal: not to build a perfect system. Goal is you understand *how to measure, load-test, and watch* a Laravel/MySQL app. Treat this like a lab, not production.

---

## 1. Scope

You need a throwaway Laravel app (or reuse an existing side project) with:
- At least 3 tables, one with a relationship that can produce N+1 queries.
- One endpoint that does something expensive (report, search, aggregation).
- Seeded data — minimum 50k–100k rows on your biggest table. Small datasets lie to you about performance.

If you don't have this yet: scaffold a simple "orders + products + customers" schema. Classic, everyone understands it, easy to break on purpose.

---

## 2. What You're Learning (Skills Checklist)

By the end you should be able to:
- [ ] Load test an endpoint and read the results (not just "it ran").
- [ ] Spot an N+1 query and fix it.
- [ ] Read a MySQL slow query log.
- [ ] Use `EXPLAIN` and understand what it's telling you.
- [ ] Set up basic APM/monitoring and read a flame graph or timeline.
- [ ] Know the difference between latency, throughput, and error rate — and why you track all three.

---

## 3. Tools (Pick These, Don't Overthink It)

| Purpose | Tool | Why |
|---|---|---|
| Load testing | **k6** or **Apache Bench (ab)** | k6 gives you real metrics + scripting. `ab` for quick dumb tests. |
| Query profiling | MySQL **slow query log** + `EXPLAIN` | Built-in, zero setup cost, teaches you the fundamentals. |
| App-level monitoring | **Laravel Telescope** (dev only) | Shows queries, requests, exceptions, timing — exactly what a beginner needs to see. |
| Production-style APM | **Laravel Pulse** | Real-time app health, queues, slow requests, exceptions. Lightweight, official. |
| System resources | `htop`, `docker stats` (if containerized) | CPU/RAM/IO while you hammer the app. |

⚠️ Don't install New Relic/Datadog/etc for a learning project. Overkill, and the free-tier friction will eat your motivation before you learn anything.

---

## 4. Functional Requirements

### 4.1 Load Testing
- FR-1: Must be able to run a load test against at least 2 endpoints (one simple GET, one heavy — search/report/aggregation).
- FR-2: Must capture: requests/sec, p95 latency, error rate.
- FR-3: Must run at 3 load levels minimum (e.g. 10, 50, 200 concurrent users) to see how it degrades.
- FR-4: Must save results somewhere comparable (a markdown table, a CSV — doesn't matter, just don't lose them).

### 4.2 Query Analysis
- FR-5: Enable MySQL slow query log (`long_query_time = 0.5` for this exercise — you want to catch "mediocre" too, not just disasters).
- FR-6: Run `EXPLAIN` on your heaviest endpoint's query. Identify: is it doing a full table scan? Missing index?
- FR-7: Deliberately create one N+1 query (loop + relation access without eager load), measure it, then fix it with `with()`, measure again. Document the before/after.

### 4.3 Monitoring
- FR-8: Install Telescope locally. Watch it while you run FR-1's load test. Note what breaks or slows down.
- FR-9: Install Pulse. Configure it to show slow requests and slow queries.
- FR-10: Trigger at least one exception on purpose (bad input, missing record) and confirm it shows up in your monitoring.

### 4.4 Optimization Loop
- FR-11: For each bottleneck found (N+1, missing index, slow query), fix → re-test → record the delta. This before/after loop is the actual point of the exercise, not the fix itself.

---

## 5. Non-Functional Requirements (Kept Light — This Is a Lab)

- NFR-1: Environment must be reproducible (Docker or documented local setup) — otherwise you can't trust your own benchmarks.
- NFR-2: Same hardware/environment for all comparative tests. Don't benchmark on your laptop then compare to a cloud VM.
- NFR-3: MySQL config (buffer pool size, etc.) stays constant across test runs unless you're specifically testing config changes.
- NFR-4: Clear DB cache / query cache between comparable test runs so you're not measuring caching instead of the actual query.

---

## 6. Deliverables (What "Done" Looks Like)

1. `benchmarks.md` — table of endpoint, load level, RPS, p95 latency, error rate, before/after each fix.
2. Screenshot or export of at least one `EXPLAIN` output, annotated with what you changed.
3. Screenshot of Telescope/Pulse catching a slow query or N+1.
4. One paragraph, in your own words: what was the biggest performance killer, and why.

That last one matters more than the tooling. If you can't explain it simply, you didn't actually learn it — you just followed steps.

---

## 7. Out of Scope

- Horizontal scaling, load balancers, CDN — not the point here.
- Production-grade alerting (PagerDuty, Slack webhooks, etc.) — Pulse's dashboard is enough for learning.
- Redis/caching layers — add that as a *follow-up* exercise once you understand raw query performance first. Caching before you understand the bottleneck just hides it.

---

## 8. Suggested Order of Attack

1. Seed data → confirm dataset is big enough to hurt.
2. Baseline load test (before touching anything).
3. Turn on slow query log + Telescope, run the same test, watch what lights up.
4. Fix the worst offender (usually N+1 or missing index).
5. Re-test, compare numbers.
6. Repeat once more on the second worst offender.
7. Write the one-paragraph takeaway.

💡 Two full optimization cycles teach you more than one perfect setup. Do it twice minimum.

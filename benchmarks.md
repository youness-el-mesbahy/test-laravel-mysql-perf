# Benchmarks Log

This file logs all load tests and performance measurements for the Laravel/MySQL Performance Lab.

## Environment Details
- **PHP Version**: 8.5.7
- **MySQL Version**: 8.0.46 (Docker container `laravel_perf_db`)
- **OS**: Linux
- **Testing Tool**: k6 / ab (To be configured in Phase 1)

---

## Load Test Results

| Run # | Description | Concurrency | RPS | p95 Latency (ms) | Error Rate | Notes / Bottlenecks |
|-------|-------------|-------------|-----|------------------|------------|---------------------|
|       |             |             |     |                  |            |                     |

---

## Fix #1: PHP-FPM Pool Tuning

### Problem
The `php:8.4-fpm-alpine` base image ships with a default `pm.max_children = 5`. With only 5 workers, requests queue at the PHP-FPM level once concurrency exceeds ~50 req/s. This is why **both cheap and expensive endpoints degrade identically** — the bottleneck is worker availability, not query performance.

### Fix applied
Created `docker/php/www.conf` with:
- **`pm = dynamic`** — spawn workers on demand
- **`pm.max_children = 30`** — 6× the default, ~750 MB for PHP (leaves ~3 GB for MySQL + OS)
- **`pm.start_servers = 4`** — warm pool ready immediately
- **`pm.max_requests = 500`** — recycle workers to prevent memory leaks

Updated `Dockerfile` to copy the config into the image:
```dockerfile
COPY ./docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
```

### Expected impact
- 200 VUs: avg latency should drop from ~240ms → ~15-30ms
- 500 VUs: should remain sub-second instead of 1.3s
- Degradation threshold should shift from ~200 VUs to ~600-800 VUs

---

## Database Query Metrics (Slow Query Logs, N+1 etc.)

- **T2.6 (N+1 Query Count)**:
- **T2.7 (Fixed Query Count)**:
- **T4.4 (Bottleneck #1 Fix Delta)**:
- **T4.7 (Bottleneck #2 Fix Delta)**:

---

## Slow Query Log Analysis
*(Paste explain plans or details of slow queries here)*

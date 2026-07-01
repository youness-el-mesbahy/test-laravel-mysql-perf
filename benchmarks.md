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

## Database Query Metrics (Slow Query Logs, N+1 etc.)

- **T2.6 (N+1 Query Count)**:
- **T2.7 (Fixed Query Count)**:
- **T4.4 (Bottleneck #1 Fix Delta)**:
- **T4.7 (Bottleneck #2 Fix Delta)**:

---

## Slow Query Log Analysis
*(Paste explain plans or details of slow queries here)*

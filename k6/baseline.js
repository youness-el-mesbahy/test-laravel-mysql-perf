/**
 * k6 Baseline Load Test — T1.3
 *
 * Hits two endpoints:
 *   - CHEAP:     GET /api/products        (paginated product list)
 *   - EXPENSIVE: GET /api/orders/report   (multi-table aggregation by country)
 *
 * Usage:
 *   k6 run k6/baseline.js
 *   k6 run -e BASE_URL=http://localhost:8000 k6/baseline.js
 *
 * Results are also printed to: k6/results/baseline_<timestamp>.json
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Counter, Rate } from 'k6/metrics';

// ── Custom per-endpoint metrics ─────────────────────────────────────────────
const cheapDuration    = new Trend('req_duration_cheap',    true);
const expensiveDuration = new Trend('req_duration_expensive', true);
const cheapErrors      = new Rate('req_errors_cheap');
const expensiveErrors  = new Rate('req_errors_expensive');

// ── Test configuration ───────────────────────────────────────────────────────
export const options = {
  scenarios: {
    baseline: {
      executor: 'constant-vus',
      vus: 10,
      duration: '30s',
    },
  },
  thresholds: {
    // Don't fail the test — we want to observe degradation. Soft thresholds only.
    req_duration_cheap:    ['p(95)<2000'],
    req_duration_expensive: ['p(95)<10000'],
    req_errors_cheap:      ['rate<0.05'],
    req_errors_expensive:  ['rate<0.05'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8001';

// ── Main VU function ─────────────────────────────────────────────────────────
export default function () {
  // --- Cheap request: paginated product listing ---
  const cheapRes = http.get(`${BASE_URL}/api/products`, {
    tags: { endpoint: 'products' },
  });

  cheapDuration.add(cheapRes.timings.duration);
  cheapErrors.add(cheapRes.status !== 200);

  check(cheapRes, {
    '[cheap] status 200':        (r) => r.status === 200,
    '[cheap] has data':          (r) => JSON.parse(r.body).data !== undefined,
  });

  sleep(0.5);

  // --- Expensive request: country revenue aggregation ---
  const expensiveRes = http.get(`${BASE_URL}/api/orders/report`, {
    tags: { endpoint: 'orders_report' },
  });

  expensiveDuration.add(expensiveRes.timings.duration);
  expensiveErrors.add(expensiveRes.status !== 200);

  check(expensiveRes, {
    '[expensive] status 200':    (r) => r.status === 200,
    '[expensive] has data':      (r) => JSON.parse(r.body).data !== undefined,
  });

  sleep(0.5);
}

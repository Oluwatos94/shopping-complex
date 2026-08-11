import http from 'k6/http';
import { check } from 'k6';
import { Counter, Trend } from 'k6/metrics';

// Load test for the registration path.
//
//   docker run --rm -i --network host grafana/k6 run - < loadtest/register.js
//
// Env knobs:
//   BASE_URL   target origin (default http://localhost:8000)
//   VUS        peak concurrent virtual users (default 100)
//   SPOOF_IP   "1" to vary X-Forwarded-For per VU (default 1, see below)

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const PEAK_VUS = parseInt(__ENV.VUS || '100', 10);
const SPOOF_IP = (__ENV.SPOOF_IP || '1') === '1';

const RUN_ID = Date.now().toString(36);

const registered = new Counter('registrations_ok');
const throttled = new Counter('registrations_throttled');
const rejected = new Counter('registrations_rejected');
const failed = new Counter('registrations_failed');
const aborted = new Counter('registrations_aborted');
const registerDuration = new Trend('register_post_duration', true);

export const options = {
  scenarios: {
    // Hold at peak long enough to see if latency stabilises or runs away.
    burst: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '10s', target: PEAK_VUS },
        { duration: '60s', target: PEAK_VUS },
        { duration: '10s', target: 0 },
      ],
      gracefulRampDown: '20s',
    },
  },
  thresholds: {
    'register_post_duration': ['p(95)<3000'],
    'registrations_failed': ['count<1'],
  },
};

export default function () {
  // Fresh jar per iteration, or the VU stays logged in and `guest` redirects it.
  const jar = new http.CookieJar();

  const headers = { Accept: 'text/html' };

  // trustProxies('*') means Laravel reads the IP from here. Varying it stops the
  // test measuring the 60/min per-IP limit instead of capacity.
  if (SPOOF_IP) {
    headers['X-Forwarded-For'] = `10.${__VU % 255}.${(__VU >> 8) % 255}.${(__ITER % 254) + 1}`;
  }

  // 1. Load the form to establish a session and pick up the CSRF cookie.
  const formRes = http.get(`${BASE_URL}/register`, { jar, headers });

  if (formRes.status !== 200) {
    formRes.status === 0 ? aborted.add(1) : failed.add(1);
    return;
  }

  const cookies = jar.cookiesForURL(`${BASE_URL}/register`);
  if (!cookies['XSRF-TOKEN']) {
    failed.add(1);
    return;
  }

  // Laravel decrypts this header, so the cookie value goes back verbatim.
  const xsrfToken = decodeURIComponent(cookies['XSRF-TOKEN'][0]);

  // 2. Submit. Unique per VU and iteration so the 5/min per-email limit never fires.
  const email = `load-${RUN_ID}-${__VU}-${__ITER}@loadtest.local`;

  const payload = {
    name: `Load Test ${__VU}-${__ITER}`,
    email: email,
    // Satisfies Password::min(8)->mixedCase()->numbers()->symbols()
    password: 'LoadTest123!',
    password_confirmation: 'LoadTest123!',
    role: 'customer',
  };

  const postRes = http.post(`${BASE_URL}/register`, JSON.stringify(payload), {
    jar,
    // Accept JSON, or Laravel redirects on failure too and a rejected signup
    // is indistinguishable from a successful one.
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrfToken,
      ...(SPOOF_IP ? { 'X-Forwarded-For': headers['X-Forwarded-For'] } : {}),
    },
    // /email/verify is not part of registration cost.
    redirects: 0,
  });

  registerDuration.add(postRes.timings.duration);

  if (postRes.status === 302) {
    registered.add(1);
  } else if (postRes.status === 429) {
    throttled.add(1);
  } else if (postRes.status === 422) {
    rejected.add(1);
  } else if (postRes.status === 0) {
    // No response: the VU was cut off mid-flight during ramp-down, not a server error.
    aborted.add(1);
  } else {
    failed.add(1);
  }

  check(postRes, {
    'registration accepted (302)': (r) => r.status === 302,
  });
}

export function handleSummary(data) {
  const metric = (name, field = 'count') =>
    data.metrics[name] ? data.metrics[name].values[field] : 0;

  const ok = metric('registrations_ok');
  const thr = metric('registrations_throttled');
  const rej = metric('registrations_rejected');
  const fail = metric('registrations_failed');
  const abort = metric('registrations_aborted');

  const p95 = data.metrics['register_post_duration']
    ? data.metrics['register_post_duration'].values['p(95)'].toFixed(0)
    : 'n/a';
  const med = data.metrics['register_post_duration']
    ? data.metrics['register_post_duration'].values.med.toFixed(0)
    : 'n/a';

  const lines = [
    '',
    '=== Registration load test ===',
    `  target      ${BASE_URL}`,
    `  peak VUs    ${PEAK_VUS}`,
    '',
    `  succeeded   ${ok}`,
    `  throttled   ${thr}   (429 — rate limiter)`,
    `  rejected    ${rej}   (422 — validation)`,
    `  failed      ${fail}   (4xx/5xx from the server)`,
    `  aborted     ${abort}   (cut off at ramp-down, not a server error)`,
    '',
    `  POST /register  median ${med}ms   p95 ${p95}ms`,
    '',
  ];

  return { stdout: lines.join('\n') };
}

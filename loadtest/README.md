# Registration load test

Measures how many concurrent signups the platform actually absorbs, against the
real production image (nginx + PHP-FPM) rather than a dev server.

## Run it

```bash
# 1. Bring up the production image + a throwaway MySQL
docker compose -f docker-compose.loadtest.yml up --build -d

# 2. Wait for boot (migrations + seeders run on first start)
docker compose -f docker-compose.loadtest.yml logs -f app
#    ready when nginx and php-fpm are both up; ctrl-C to stop tailing

# 3. Sanity check
curl -i http://localhost:8000/up

# 4. Run the test — no k6 install needed
docker run --rm -i --network host grafana/k6 run - < loadtest/register.js
```

Tear down with:

```bash
docker compose -f docker-compose.loadtest.yml down -v
```

The `-v` matters — it drops the throwaway database so the next run starts from a
clean users table.

## Knobs

```bash
# 250 concurrent instead of 100
docker run --rm -i --network host -e VUS=250 grafana/k6 run - < loadtest/register.js

# Point at a deployed environment (use a preview/staging URL, never production)
docker run --rm -i --network host -e BASE_URL=https://your-preview.up.railway.app \
  grafana/k6 run - < loadtest/register.js

# Exercise the rate limiter instead of bypassing it
docker run --rm -i --network host -e SPOOF_IP=0 grafana/k6 run - < loadtest/register.js
```

## Measured baseline

100 concurrent VUs, 80s hold, on a 12-CPU dev machine (Railway containers are
smaller, so expect worse — re-run against a preview deploy for real numbers):

```
  succeeded   2445      ~27 registrations/sec
  throttled   0
  rejected    0
  failed      0
  aborted     84        cut off at ramp-down, not server errors

  POST /register  median 446ms   p95 687ms
```

Server side: 0 x 5xx, 0 failed jobs, queue fully drained.

## Reading the result

- **succeeded** — 302 redirect to `/email/verify`. This is the real throughput number.
- **aborted** — VUs cut off mid-request during ramp-down. Not server errors.
- **throttled** — 429s. With `SPOOF_IP=1` this should be ~0. If it isn't, a limiter
  is tighter than intended.
- **failed** — 5xx or timeouts. Anything above zero means something broke under
  load; check `docker compose -f docker-compose.loadtest.yml logs app`.

**p95 is the number that matters.** Under ~1s is comfortable. Climbing steadily
through the hold phase means requests are queueing faster than FPM drains them —
raise `pm.max_children` in `docker/php-fpm.conf`, or add a replica.

`registrations_ok` should land near `VUs x hold_seconds / median_latency`. Well
below that means something is serialising.

## Two things this test deliberately does

**It varies `X-Forwarded-For` per VU.** All traffic otherwise originates from one
IP and collides with the 60/min per-IP register limit, so the test would measure
the rate limiter rather than capacity. This works because `bootstrap/app.php` sets
`trustProxies(at: '*')` — which is also why that setting is worth revisiting: the
same trick lets a real client forge its apparent IP.

**It sends `Accept: application/json`.** Laravel redirects on both success and
validation failure, so without this a rejected signup would count as a successful
one.

## Checking the queue kept up

Verification emails are queued, so registration latency does not include sending
them. Confirm the workers actually drained the backlog:

```bash
docker compose -f docker-compose.loadtest.yml exec app \
  php artisan tinker --execute="echo DB::table('jobs')->count();"
```

A number that stays high after the run means 3 workers are not enough for the
campaign volume — raise `numprocs` in `docker/supervisord.conf`, keeping it at or
below your mail provider's per-second rate limit.

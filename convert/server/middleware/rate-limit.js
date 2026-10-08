/* Minimal in-memory, per-IP rate limiter. The disabled send button in the browser is not
   a security control, so the server enforces the limit itself. Fine for a single process;
   swap for a shared store only if you run multiple instances. */
import { RATE_LIMIT } from '../config.js';

const hits = new Map();

// Drop expired entries so the map doesn't grow forever. unref() lets the process exit normally.
setInterval(() => {
  const now = Date.now();
  for (const [ip, entry] of hits) {
    if (entry.resetAt <= now) hits.delete(ip);
  }
}, RATE_LIMIT.windowMs).unref();

export function rateLimit(req, res, next) {
  const now = Date.now();
  const entry = hits.get(req.ip);

  if (!entry || entry.resetAt <= now) {
    hits.set(req.ip, { count: 1, resetAt: now + RATE_LIMIT.windowMs });
    return next();
  }

  if (entry.count >= RATE_LIMIT.maxRequests) {
    res.set('Retry-After', String(Math.ceil((entry.resetAt - now) / 1000)));
    return res.status(429).json({
      success: false,
      error: 'You are sending messages too quickly. Please wait a moment and try again.',
    });
  }

  entry.count += 1;
  return next();
}

/* Express entry point: serves the existing static site and the /api/chat endpoint.
   The website stays in the project root (unchanged structure); backend code lives in /server. */
import './env.js';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import express from 'express';
import chatRouter from './routes/chat.js';

const app = express();
const PORT = process.env.PORT || 3000;
const SITE_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

app.disable('x-powered-by');
// History can hold up to 20 messages, so allow a little more than the default 100kb.
app.use(express.json({ limit: '256kb' }));

app.get('/api/health', (req, res) => {
  res.set('Cache-Control', 'no-store');
  res.json({ success: true, chatConfigured: Boolean(process.env.GROQ_API_KEY?.trim()) });
});
app.use('/api/chat', chatRouter);

// Never serve backend files (including .env) as static assets.
app.use(['/server', '/prompt'], (req, res) => res.status(404).end());
app.use(express.static(SITE_ROOT, { dotfiles: 'deny' }));

app.use('/api', (req, res) => {
  res.status(404).json({ success: false, error: 'Endpoint not found.' });
});

// Malformed or oversized JSON bodies get a clean JSON error instead of an HTML stack page.
app.use((err, req, res, next) => {
  if (res.headersSent) return next(err);
  const status = err.type === 'entity.parse.failed' ? 400 : err.type === 'entity.too.large' ? 413 : 500;
  if (status === 500) console.error(`[${new Date().toISOString()}] Server error:`, err.message);
  return res.status(status).json({
    success: false,
    error: status === 500 ? 'Internal server error.' : 'Request body is not valid.',
  });
});

app.listen(PORT, () => {
  console.log(`Travel site + Groq chat API running on http://localhost:${PORT}/contact.html`);
  if (!process.env.GROQ_API_KEY) {
    console.warn('GROQ_API_KEY is empty: add it to server/.env to enable AI replies.');
  }
});

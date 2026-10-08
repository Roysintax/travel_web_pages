import test from 'node:test';
import assert from 'node:assert/strict';
import { generateChatResponse } from '../services/groq.service.js';

test('missing key reports unavailable without requesting the provider', async () => {
  const saved = process.env.GROQ_API_KEY;
  delete process.env.GROQ_API_KEY;
  try { await assert.rejects(generateChatResponse('Hello'), error => error.status === 503); }
  finally { if (saved !== undefined) process.env.GROQ_API_KEY = saved; }
});

test('service forwards conversation and returns trimmed provider answer', async () => {
  const savedKey = process.env.GROQ_API_KEY;
  const savedFetch = globalThis.fetch;
  process.env.GROQ_API_KEY = 'test-only-not-a-real-key';
  globalThis.fetch = async (url, options) => {
    const body = JSON.parse(options.body);
    assert.equal(body.messages.at(-1).content, 'Next trip?');
    assert.equal(body.messages[1].role, 'assistant');
    return new Response(JSON.stringify({ choices: [{ message: { content: '  Visit Japan.  ' } }] }));
  };
  try { assert.equal(await generateChatResponse('Next trip?', [{role:'assistant',content:'Hello'}]), 'Visit Japan.'); }
  finally { globalThis.fetch = savedFetch; if (savedKey === undefined) delete process.env.GROQ_API_KEY; else process.env.GROQ_API_KEY = savedKey; }
});

test('provider failure logs status without sensitive response body', async () => {
  const savedKey = process.env.GROQ_API_KEY;
  const savedFetch = globalThis.fetch;
  const savedError = console.error;
  const logged = [];
  process.env.GROQ_API_KEY = 'test-only-not-a-real-key';
  globalThis.fetch = async () => new Response('private-user@example.com secret-token', { status: 500 });
  console.error = (...args) => logged.push(args.join(' '));
  try {
    await assert.rejects(generateChatResponse('Hello'), error => error.status === 502);
    assert.equal(logged.length, 1);
    assert.ok(logged[0].includes('500'));
    assert.ok(!logged[0].includes('private-user@example.com'));
    assert.ok(!logged[0].includes('secret-token'));
  } finally {
    globalThis.fetch = savedFetch;
    console.error = savedError;
    if (savedKey === undefined) delete process.env.GROQ_API_KEY; else process.env.GROQ_API_KEY = savedKey;
  }
});

test('malformed provider content returns a controlled service error', async () => {
  const savedKey = process.env.GROQ_API_KEY;
  const savedFetch = globalThis.fetch;
  process.env.GROQ_API_KEY = 'test-only-not-a-real-key';
  globalThis.fetch = async () => new Response(JSON.stringify({ choices: [{ message: { content: { invalid: true } } }] }));
  try {
    await assert.rejects(generateChatResponse('Hello'), error => error.status === 502);
  } finally {
    globalThis.fetch = savedFetch;
    if (savedKey === undefined) delete process.env.GROQ_API_KEY; else process.env.GROQ_API_KEY = savedKey;
  }
});

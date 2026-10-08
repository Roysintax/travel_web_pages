/* Groq Cloud AI Service: handles communication with Groq Chat Completions API.
   The secret GROQ_API_KEY is only ever read server-side here. */
import { AI_CONFIG } from '../config.js';
import { SYSTEM_PROMPT } from '../prompts/travel-assistant.js';

export class GroqServiceError extends Error {
  constructor(message, status) {
    super(message);
    this.name = 'GroqServiceError';
    this.status = status;
  }
}

function buildMessages(message, history) {
  return [
    { role: 'system', content: SYSTEM_PROMPT },
    ...history,
    { role: 'user', content: message },
  ];
}

async function callGroq(apiKey, messages) {
  try {
    return await fetch(AI_CONFIG.apiUrl, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        model: AI_CONFIG.model,
        messages,
        max_tokens: AI_CONFIG.maxTokens,
        temperature: AI_CONFIG.temperature,
      }),
      signal: AbortSignal.timeout(AI_CONFIG.upstreamTimeoutMs),
      redirect: 'error',
    });
  } catch (error) {
    if (error.name === 'TimeoutError') {
      throw new GroqServiceError('Groq AI request timed out.', 504);
    }
    throw new GroqServiceError('Could not reach Groq AI.', 502);
  }
}

export async function generateChatResponse(message, history = []) {
  const apiKey = process.env.GROQ_API_KEY;
  if (!apiKey) {
    throw new GroqServiceError('GROQ_API_KEY is not configured.', 503);
  }

  const response = await callGroq(apiKey, buildMessages(message, history));

  if (!response.ok) {
    console.error(`[${new Date().toISOString()}] Groq API error (${response.status})`);
    const status = response.status === 429 ? 429 : response.status === 401 ? 401 : 502;
    throw new GroqServiceError(`Groq API returned ${response.status}`, status);
  }

  const data = await response.json();
  const content = data?.choices?.[0]?.message?.content;
  const answer = typeof content === 'string' ? content.trim() : '';
  if (!answer) {
    throw new GroqServiceError('Groq AI returned an empty answer.', 502);
  }
  return answer;
}

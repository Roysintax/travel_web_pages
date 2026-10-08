/* Central configuration for Groq AI chat settings. */
export const AI_CONFIG = {
  apiUrl: 'https://api.groq.com/openai/v1/chat/completions',
  model: process.env.GROQ_MODEL || 'openai/gpt-oss-120b',
  maxTokens: 1500,
  temperature: 0.6,
  upstreamTimeoutMs: 25_000,
};

export const CHAT_LIMITS = {
  maxMessageLength: 5000,
  maxHistoryMessages: 20,
};

export const RATE_LIMIT = {
  windowMs: 60_000,
  maxRequests: 20,
};

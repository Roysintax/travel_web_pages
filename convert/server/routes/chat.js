/* POST /api/chat — request validation, rate limiting, and response normalization.
   The Groq AI call itself lives in services/groq.service.js. */
import express from 'express';
import { CHAT_LIMITS } from '../config.js';
import { rateLimit } from '../middleware/rate-limit.js';
import { generateChatResponse, GroqServiceError } from '../services/groq.service.js';

const router = express.Router();

const ALLOWED_HISTORY_ROLES = new Set(['user', 'assistant']);

const ERROR_MESSAGES = {
  400: 'Invalid chat request.',
  401: 'AI service authentication failed.',
  429: 'The assistant is receiving a lot of messages. Please wait a moment and try again.',
  503: 'The chat assistant is not available right now. Please reach us by phone or WhatsApp.',
  504: 'The assistant took too long to respond. Please try again.',
  default: 'Sorry, something went wrong while processing your message. Please try again.',
};

function validateMessage(message) {
  if (typeof message !== 'string' || message.trim() === '') return 'Message cannot be empty.';
  if (message.length > CHAT_LIMITS.maxMessageLength) return 'Message is too long.';
  return null;
}

function parseHistory(history) {
  if (history === undefined) return [];
  if (!Array.isArray(history)) return null;

  const recent = history.slice(-CHAT_LIMITS.maxHistoryMessages);
  const isValid = recent.every((item) =>
    item && ALLOWED_HISTORY_ROLES.has(item.role) && typeof item.content === 'string');
  if (!isValid) return null;

  return recent
    .map(({ role, content }) => ({ role, content: content.trim().slice(0, CHAT_LIMITS.maxMessageLength) }))
    .filter((item) => item.content);
}

router.post('/', rateLimit, async (req, res) => {
  const { message, history } = req.body ?? {};

  const messageError = validateMessage(message);
  if (messageError) {
    return res.status(400).json({
      success: false,
      error: { code: 'INVALID_INPUT', message: messageError },
    });
  }

  const cleanHistory = parseHistory(history);
  if (cleanHistory === null) {
    return res.status(400).json({
      success: false,
      error: { code: 'INVALID_HISTORY', message: 'Chat history is not valid.' },
    });
  }

  try {
    const answer = await generateChatResponse(message.trim(), cleanHistory);
    // Normalized response contract: supports both response.message and response.data.message
    return res.json({
      success: true,
      message: answer,
      data: { message: answer },
    });
  } catch (error) {
    const status = error instanceof GroqServiceError ? error.status : 500;
    console.error(`[${new Date().toISOString()}] Chat error (${status}):`, error.message);
    const friendlyMessage = ERROR_MESSAGES[status] ?? ERROR_MESSAGES.default;
    return res.status(status).json({
      success: false,
      error: { code: 'AI_REQUEST_FAILED', message: friendlyMessage },
      // Top-level error string fallback for backwards-compatible consumers
      errorMessage: friendlyMessage,
    });
  }
});

export default router;

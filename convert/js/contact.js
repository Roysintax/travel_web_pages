/* Contact page: reveal motion, message form, AI live chat, office hours. */
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

/* ---------- Reveal on scroll (once) ---------- */
const revealItems = document.querySelectorAll('.reveal');
document.querySelectorAll('.channel-card').forEach((card, i) => card.style.setProperty('--reveal-delay', `${i * 80}ms`));

if ('IntersectionObserver' in window && !reducedMotion.matches) {
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      revealObserver.unobserve(entry.target);
    });
  }, { threshold: 0.12 });
  revealItems.forEach((item) => revealObserver.observe(item));
}

/* ---------- Contact form (local preview; nothing is sent to a server) ---------- */
const contactForm = document.getElementById('contact-form');
const formSuccess = document.getElementById('form-success');
const messageField = document.getElementById('contact-message');
const messageCount = document.getElementById('contact-message-count');

const validators = {
  name: (v) => (v.trim().length >= 2 ? '' : 'Please enter your name.'),
  email: (v) => (/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) ? '' : 'Please enter a valid email address.'),
  message: (v) => (v.trim().length >= 10 ? '' : 'Tell us a little more (at least 10 characters).'),
};

function showFieldError(input, message) {
  const field = input.closest('.field');
  field.classList.toggle('has-error', Boolean(message));
  input.setAttribute('aria-invalid', String(Boolean(message)));
  document.getElementById(`${input.id}-error`).textContent = message;
}

Object.keys(validators).forEach((name) => {
  const input = contactForm.elements[name];
  input.addEventListener('blur', () => input.value && showFieldError(input, validators[name](input.value)));
  input.addEventListener('input', () => input.closest('.field').classList.contains('has-error') &&
    showFieldError(input, validators[name](input.value)));
});

messageField.addEventListener('input', () => {
  messageCount.textContent = `${messageField.value.length} / ${messageField.maxLength}`;
});

contactForm.addEventListener('submit', (event) => {
  event.preventDefault();
  let firstInvalid = null;
  Object.entries(validators).forEach(([name, validate]) => {
    const input = contactForm.elements[name];
    const error = validate(input.value);
    showFieldError(input, error);
    if (error && !firstInvalid) firstInvalid = input;
  });
  if (firstInvalid) { firstInvalid.focus(); return; }

  const button = contactForm.querySelector('.submit-button');
  const data = new FormData(contactForm);
  button.setAttribute('aria-busy', 'true');
  button.querySelector('span').textContent = 'Sending…';

  setTimeout(() => {
    const firstName = data.get('name').trim().split(' ')[0];
    document.getElementById('form-success-text').textContent =
      `Thanks, ${firstName}! A travel expert will reply via ${data.get('reply').toLowerCase()} about “${data.get('topic')}” soon.`;
    contactForm.hidden = true;
    formSuccess.hidden = false;
    formSuccess.focus();
    button.removeAttribute('aria-busy');
    button.querySelector('span').textContent = 'Send Message';
  }, 900);
});

document.getElementById('form-reset').addEventListener('click', () => {
  contactForm.reset();
  messageCount.textContent = `0 / ${messageField.maxLength}`;
  formSuccess.hidden = true;
  contactForm.hidden = false;
  contactForm.elements.name.focus();
});

/* ---------- Live chat (Together AI through our own backend) ---------- */
// The browser only ever calls our backend at /api/chat. The Together API key stays on the
// server (server/.env), so it can't be seen in DevTools.
const CHAT_CONFIG = {
  endpoint: '/api/chat',
  timeoutMs: 30_000,
  storageKey: 'travel-contact-chat-v2',
  maxStoredMessages: 60,
  maxHistoryMessages: 20,
};

const CHAT_ERRORS = {
  timeout: 'The assistant took too long to respond. Please try again.',
  network: 'Can’t reach the chat service. Check your connection and try again.',
  backend: 'Live chat needs the Travel server. Open http://localhost:3000/contact.html to chat.',
  unavailable: 'The chat service is unavailable right now. Please try again later, or reach us by phone or WhatsApp.',
};

const WELCOME_MESSAGE = 'Hi there! I’m Nadia, Travel’s AI assistant. 👋\nAsk me about destinations, packages, visas, or planning your trip.';

/* Errors whose message is already safe to show (it comes from our backend, not a stack trace). */
class ChatServiceError extends Error {}

const chatCard = document.getElementById('live-chat');
const chatLog = document.getElementById('chat-log');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatSend = document.getElementById('chat-send');
const chatTyping = document.getElementById('chat-typing');
const chatPresence = document.getElementById('chat-presence');
const presenceDefault = chatPresence.innerHTML;
const timeFormat = new Intl.DateTimeFormat([], { hour: '2-digit', minute: '2-digit' });

const chatState = {
  messages: loadChatHistory(),
  isSending: false,
  controller: null,
};

function loadChatHistory() {
  try {
    const saved = JSON.parse(localStorage.getItem(CHAT_CONFIG.storageKey));
    return Array.isArray(saved) ? saved : [];
  } catch {
    return [];
  }
}

function saveChatHistory() {
  try {
    localStorage.setItem(CHAT_CONFIG.storageKey, JSON.stringify(chatState.messages.slice(-CHAT_CONFIG.maxStoredMessages)));
  } catch (error) {
    // Private mode / full storage: chat still works, it just won't survive a reload.
    console.warn('Chat history could not be saved:', error);
  }
}

function createAgentAvatar() {
  const avatar = document.createElement('span');
  avatar.className = 'agent-avatar small';
  avatar.setAttribute('aria-hidden', 'true');
  avatar.textContent = 'NA';
  return avatar;
}

function createTimestamp(message) {
  const time = document.createElement('span');
  time.className = 'msg-time';
  const sender = document.createElement('span');
  sender.className = 'sr-only';
  sender.textContent = message.role === 'assistant' ? 'Nadia (AI) said at ' : 'You said at ';
  time.append(sender, timeFormat.format(new Date(message.time)));
  if (message.role === 'user') {
    const ticks = document.createElement('i');
    ticks.className = 'fa-solid fa-check-double';
    ticks.setAttribute('aria-hidden', 'true');
    time.append(ticks);
  }
  return time;
}

function appendMessage(message) {
  const row = document.createElement('div');
  row.className = `msg msg-${message.role}`;
  if (message.role === 'assistant') row.append(createAgentAvatar());

  const body = document.createElement('div');
  body.className = 'msg-body';
  const bubble = document.createElement('p');
  bubble.className = 'msg-bubble';
  // AI output and user input are untrusted: textContent never parses HTML (XSS-safe).
  bubble.textContent = message.content;
  body.append(bubble, createTimestamp(message));

  row.append(body);
  chatLog.append(row);
  scrollChatToEnd();
}

/* Error bubbles are UI-only: never stored and never sent to the AI as history. */
function appendErrorMessage(text) {
  const row = document.createElement('div');
  row.className = 'msg msg-error';
  row.setAttribute('role', 'alert');

  const icon = document.createElement('i');
  icon.className = 'fa-solid fa-circle-exclamation';
  icon.setAttribute('aria-hidden', 'true');

  const bubble = document.createElement('p');
  bubble.className = 'msg-bubble';
  bubble.textContent = text;

  const retry = document.createElement('button');
  retry.type = 'button';
  retry.className = 'msg-retry';
  retry.innerHTML = '<i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Try again';
  retry.addEventListener('click', retryLastMessage);

  row.append(icon, bubble, retry);
  chatLog.append(row);
  scrollChatToEnd();
}

function removeErrorMessages() {
  chatLog.querySelectorAll('.msg-error').forEach((row) => row.remove());
}

function renderChat() {
  chatLog.replaceChildren();
  const day = document.createElement('p');
  day.className = 'chat-day';
  day.textContent = 'Today';
  chatLog.append(day);
  chatState.messages.forEach(appendMessage);
}

function addMessage(role, content) {
  const message = { role, content, time: Date.now() };
  chatState.messages.push(message);
  saveChatHistory();
  appendMessage(message);
}

function scrollChatToEnd() {
  chatLog.scrollTop = chatLog.scrollHeight;
}

function showTypingIndicator() {
  chatTyping.hidden = false;
  chatPresence.textContent = 'Nadia is typing…';
  scrollChatToEnd();
}

function hideTypingIndicator() {
  chatTyping.hidden = true;
  chatPresence.innerHTML = presenceDefault;
}

function updateSendButton() {
  chatSend.disabled = chatState.isSending || !chatInput.value.trim();
}

function setLoading(isLoading) {
  chatState.isSending = isLoading;
  chatLog.setAttribute('aria-busy', String(isLoading));
  if (isLoading) showTypingIndicator();
  else hideTypingIndicator();
  updateSendButton();
}

function autosizeChatInput() {
  chatInput.style.height = 'auto';
  chatInput.style.height = `${Math.min(chatInput.scrollHeight, 120)}px`;
}

/* Returns the trimmed message, or '' when there is nothing worth sending. */
function validateMessage(rawText) {
  return rawText.trim().slice(0, chatInput.maxLength);
}

/* The newest user message is sent as `message`; earlier turns as bounded `history`. */
function buildChatRequestBody() {
  const turns = chatState.messages.map(({ role, content }) => ({ role, content }));
  const latest = turns.pop();
  return { message: latest.content, history: turns.slice(-CHAT_CONFIG.maxHistoryMessages) };
}

async function requestChatReply(body, signal) {
  const response = await fetch(CHAT_CONFIG.endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
    signal,
  });

  // A non-JSON body (e.g. an HTML 404 page) means the chat backend isn't reachable at this URL.
  const data = await response.json().catch(() => null);
  const replyText = data?.message || data?.data?.message;
  if (!response.ok || !data?.success || typeof replyText !== 'string') {
    const errorMsg = data?.error?.message || data?.error || data?.errorMessage || CHAT_ERRORS.unavailable;
    throw new ChatServiceError(errorMsg);
  }
  return replyText;
}

/* Maps any failure to a friendly message; returns null when the user cancelled on purpose. */
function describeChatError(error, signal) {
  if (signal.aborted) return signal.reason === 'timeout' ? CHAT_ERRORS.timeout : null;
  if (error instanceof ChatServiceError) return error.message;
  return CHAT_ERRORS.network;
}

async function fetchAssistantReply() {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort('timeout'), CHAT_CONFIG.timeoutMs);
  chatState.controller = controller;
  setLoading(true);

  try {
    const reply = await requestChatReply(buildChatRequestBody(), controller.signal);
    addMessage('assistant', reply);
  } catch (error) {
    const friendlyMessage = describeChatError(error, controller.signal);
    if (friendlyMessage) appendErrorMessage(friendlyMessage);
    if (!controller.signal.aborted && !(error instanceof ChatServiceError)) {
      console.error('Chat request failed:', error);
    }
  } finally {
    clearTimeout(timeoutId);
    chatState.controller = null;
    setLoading(false);
  }
}

async function handleChatSubmit(rawText) {
  const message = validateMessage(rawText);
  // Empty input or a request already in flight: don't call the API (prevents double submit).
  if (!message || chatState.isSending) return;

  removeErrorMessages();
  addMessage('user', message);
  chatInput.value = '';
  autosizeChatInput();
  await fetchAssistantReply();
}

async function retryLastMessage() {
  if (chatState.isSending || chatState.messages.at(-1)?.role !== 'user') return;
  removeErrorMessages();
  await fetchAssistantReply();
}

function clearConversation() {
  if (!window.confirm('Clear this conversation?')) return;
  chatState.controller?.abort('cleared');
  chatState.messages = [];
  saveChatHistory();
  renderChat();
  addMessage('assistant', WELCOME_MESSAGE);
  chatInput.focus();
}

chatInput.addEventListener('input', () => { autosizeChatInput(); updateSendButton(); });
chatInput.addEventListener('keydown', (event) => {
  if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
    event.preventDefault();
    handleChatSubmit(chatInput.value);
  }
});
chatForm.addEventListener('submit', (event) => {
  event.preventDefault();
  handleChatSubmit(chatInput.value);
});
document.getElementById('quick-replies').addEventListener('click', (event) => {
  const button = event.target.closest('[data-quick]');
  if (button) handleChatSubmit(button.dataset.quick);
});
document.getElementById('chat-clear').addEventListener('click', clearConversation);
// Don't leave a request running when the user navigates away.
window.addEventListener('pagehide', () => chatState.controller?.abort('page-hidden'));

renderChat();
if (!chatState.messages.length) addMessage('assistant', WELCOME_MESSAGE);
scrollChatToEnd();

/* "Start a Live Chat" buttons + floating launcher jump to the chat and focus the input. */
document.querySelectorAll('[data-open-chat]').forEach((trigger) => {
  trigger.addEventListener('click', (event) => {
    event.preventDefault();
    chatCard.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'center' });
    chatCard.classList.remove('is-highlighted');
    void chatCard.offsetWidth; // restart highlight animation
    chatCard.classList.add('is-highlighted');
    setTimeout(() => chatInput.focus({ preventScroll: true }), reducedMotion.matches ? 0 : 450);
  });
});

/* Hide the floating launcher while the chat itself is on screen. */
const launcher = document.querySelector('.chat-launcher');
if ('IntersectionObserver' in window && launcher) {
  new IntersectionObserver(([entry]) => {
    launcher.classList.toggle('is-hidden', entry.isIntersecting);
  }, { threshold: 0.35 }).observe(chatCard);
}

/* ---------- Office hours (Mon–Sat, 08:00–20:00 Jakarta time) ---------- */
const hoursStatus = document.getElementById('office-hours-status');
if (hoursStatus) {
  const parts = Object.fromEntries(new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Jakarta', weekday: 'short', hour: 'numeric', hourCycle: 'h23',
  }).formatToParts(new Date()).map((p) => [p.type, p.value]));
  const isOpen = parts.weekday !== 'Sun' && Number(parts.hour) >= 8 && Number(parts.hour) < 20;
  hoursStatus.classList.add(isOpen ? 'is-open' : 'is-closed');
  hoursStatus.textContent = `${isOpen ? 'Open now' : 'Closed now'} · Mon–Sat, 08:00–20:00 WIB`;
}

/* Readiness check never sends a message to the AI provider. */
async function checkChatService() {
  try {
    const response = await fetch('/api/health', { signal: AbortSignal.timeout(5000), cache: 'no-store' });
    const status = await response.json().catch(() => null);
    chatPresence.textContent = response.ok && status?.success
      ? (status.chatConfigured ? 'AI assistant available' : 'AI setup pending')
      : 'Open this page through the Travel server on port 3000';
  } catch {
    chatPresence.textContent = 'Chat connection unavailable';
  }
}
checkChatService();

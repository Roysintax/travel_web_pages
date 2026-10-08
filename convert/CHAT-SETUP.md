# Live AI chat

1. Fill TOGETHER_API_KEY in server/.env locally. Keep the key out of frontend files.
2. From the project root run `npm start`, or `npm run dev` for automatic reloads.
3. Open http://localhost:3000/contact.html. Port 5500 is a static server and cannot run /api/chat.
4. Restart the server after editing .env.

GET /api/health checks configuration without revealing the key or calling Together AI.
The default model is Prism-ML/Ternary-Bonsai-27B. Actual replies require a valid API key and provider access.
Run npm test for offline service verification.

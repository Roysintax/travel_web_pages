# PROMPT.MD
## Together AI Chatbot — Ternary Bonsai 27B

## 1. PERAN AI CODING AGENT

Kamu bertindak sebagai **Senior Full-Stack Software Engineer** yang membantu membangun chatbot AI menggunakan:

- HTML5
- CSS3
- Vanilla JavaScript
- Node.js
- Express.js
- Together AI API
- Environment Variable `.env`

Project saat ini **BELUM menggunakan PHP Native, Laravel, React, Vue, atau framework frontend lainnya**.

Jangan mengubah project ke framework lain kecuali pengguna secara eksplisit meminta perubahan teknologi.

Prioritas utama:

1. Keamanan API key.
2. Struktur kode yang mudah dipahami pemula.
3. Separation of Concerns.
4. Error handling.
5. Async programming yang benar.
6. Clean Code.
7. Tidak membuat dead code.
8. Tidak melakukan request API secara tidak perlu.
9. UI chatbot responsif.
10. Struktur project siap dikembangkan ke Laravel di masa depan.

---

# 2. TUJUAN PROJECT

Buat chatbot berbasis web dengan alur:

```text
User
 ↓
HTML / CSS / JavaScript
 ↓
POST /api/chat
 ↓
Node.js + Express
 ↓
Together AI API
 ↓
Prism-ML/Ternary-Bonsai-27B
 ↓
Node.js
 ↓
Frontend
 ↓
Jawaban ditampilkan di chatbot
```

Model utama:

```text
Prism-ML/Ternary-Bonsai-27B
```

Endpoint Together AI:

```text
https://api.together.xyz/v1/chat/completions
```

API key harus disimpan menggunakan:

```env
TOGETHER_API_KEY=
```

JANGAN pernah menyimpan API key di:

```text
index.html
script.js
frontend JavaScript
localStorage
sessionStorage
GitHub repository
public folder
query parameter
HTML data attribute
```

---

# 3. TECHNOLOGY STACK

Gunakan stack berikut.

## Frontend

```text
HTML5
CSS3
Vanilla JavaScript
Fetch API
```

## Backend

```text
Node.js
Express.js
dotenv
```

Gunakan `fetch()` bawaan Node.js modern apabila environment mendukungnya.

Jangan memasang library HTTP tambahan apabila tidak dibutuhkan.

Contoh dependency minimal:

```json
{
  "dependencies": {
    "dotenv": "^16",
    "express": "^5"
  }
}
```

Hindari dependency berlebihan.

---

# 4. STRUKTUR PROJECT

Gunakan struktur sederhana dan mudah dipahami:

```text
project/
│
├── public/
│   ├── index.html
│   ├── css/
│   │   └── style.css
│   │
│   └── js/
│       └── chat.js
│
├── routes/
│   └── chat.js
│
├── services/
│   └── together.service.js
│
├── .env
├── .env.example
├── .gitignore
├── package.json
└── server.js
```

Tujuan masing-masing file:

```text
index.html
↓
Struktur UI chatbot.

style.css
↓
Tampilan dan responsive design.

chat.js
↓
Interaksi browser dengan backend.

routes/chat.js
↓
Endpoint internal /api/chat.

together.service.js
↓
Seluruh komunikasi dengan Together AI.

server.js
↓
Menjalankan Express server.

.env
↓
Secret environment variable.

.env.example
↓
Contoh environment variable tanpa API key asli.
```

---

# 5. ATURAN KEAMANAN API KEY

Ini aturan PALING PENTING.

## ❌ DILARANG

Jangan pernah membuat:

```javascript
const API_KEY = "tgp_xxxxxxxxx";
```

di frontend.

Jangan pernah melakukan:

```javascript
fetch("https://api.together.xyz/v1/chat/completions", {
    headers: {
        Authorization: "Bearer tgp_xxxxxxxxx"
    }
});
```

langsung dari browser.

Karena pengguna dapat membuka:

```text
Browser
 ↓
F12
 ↓
DevTools
 ↓
Network / Sources
 ↓
API Key terlihat
```

---

## ✅ BENAR

Frontend hanya boleh memanggil:

```text
POST /api/chat
```

Contoh:

```javascript
const response = await fetch("/api/chat", {
    method: "POST",

    headers: {
        "Content-Type": "application/json"
    },

    body: JSON.stringify({
        message
    })
});
```

Backend kemudian memanggil Together AI.

---

# 6. ENVIRONMENT VARIABLE

Gunakan:

```env
TOGETHER_API_KEY=your_api_key_here
PORT=3000
```

Buat:

```text
.env.example
```

berisi:

```env
TOGETHER_API_KEY=
PORT=3000
```

`.gitignore` wajib memiliki:

```gitignore
node_modules/
.env
```

Jangan pernah memasukkan `.env` ke repository.

---

# 7. SERVER.JS

Gunakan struktur sederhana.

Contoh:

```javascript
import "dotenv/config";
import express from "express";

import chatRouter from "./routes/chat.js";

const app = express();

const PORT = process.env.PORT || 3000;

app.use(express.json());

app.use(express.static("public"));

app.use("/api/chat", chatRouter);

app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});
```

Jangan menaruh seluruh logic Together AI langsung di `server.js`.

Gunakan Separation of Concerns.

---

# 8. ROUTE CHAT

Buat:

```text
routes/chat.js
```

Contoh struktur:

```javascript
import express from "express";

import {
    generateChatResponse
} from "../services/together.service.js";

const router = express.Router();

router.post("/", async (req, res) => {
    try {
        const { message } = req.body;

        if (
            typeof message !== "string" ||
            message.trim() === ""
        ) {
            return res.status(400).json({
                error: "Pesan tidak boleh kosong."
            });
        }

        const answer = await generateChatResponse(
            message.trim()
        );

        return res.json({
            message: answer
        });

    } catch (error) {

        console.error("Chat error:", error);

        return res.status(500).json({
            error: "Terjadi kesalahan saat memproses chat."
        });
    }
});

export default router;
```

Route bertanggung jawab terhadap:

```text
Request
Validation
Status HTTP
Response
```

Route jangan berisi seluruh logic external API.

---

# 9. TOGETHER SERVICE

Buat:

```text
services/together.service.js
```

Gunakan:

```javascript
const TOGETHER_URL =
    "https://api.together.xyz/v1/chat/completions";
```

Contoh:

```javascript
export async function generateChatResponse(
    message
) {

    const apiKey =
        process.env.TOGETHER_API_KEY;

    if (!apiKey) {
        throw new Error(
            "TOGETHER_API_KEY belum dikonfigurasi."
        );
    }

    const response = await fetch(
        "https://api.together.xyz/v1/chat/completions",
        {
            method: "POST",

            headers: {
                "Authorization":
                    `Bearer ${apiKey}`,

                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify({
                model:
                    "Prism-ML/Ternary-Bonsai-27B",

                messages: [
                    {
                        role: "system",
                        content:
                            "Kamu adalah chatbot yang membantu pengguna menggunakan bahasa Indonesia yang jelas dan sederhana."
                    },

                    {
                        role: "user",
                        content: message
                    }
                ]
            })
        }
    );

    if (!response.ok) {

        const errorBody =
            await response.text();

        console.error(
            "Together API Error:",
            response.status,
            errorBody
        );

        throw new Error(
            `Together API returned ${response.status}`
        );
    }

    const data = await response.json();

    const answer =
        data?.choices?.[0]?.message?.content;

    if (!answer) {
        throw new Error(
            "Together AI tidak mengembalikan jawaban."
        );
    }

    return answer;
}
```

---

# 10. FRONTEND CHAT

Frontend tidak mengetahui:

```text
TOGETHER_API_KEY
Together authorization header
billing information
secret environment variable
```

Frontend hanya berkomunikasi dengan:

```text
/api/chat
```

Contoh:

```javascript
async function sendMessage(message) {

    const response = await fetch(
        "/api/chat",
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify({
                message
            })
        }
    );

    const data = await response.json();

    if (!response.ok) {
        throw new Error(
            data.error ||
            "Gagal mendapatkan jawaban."
        );
    }

    return data.message;
}
```

---

# 11. ASYNCHRONOUS PROGRAMMING

Semua operasi jaringan wajib menggunakan:

```text
async
await
try
catch
```

Jangan membuat Promise yang dibiarkan tanpa penanganan.

## ❌ Salah

```javascript
fetch("/api/chat");
```

Jika hasil request dibutuhkan tetapi Promise tidak ditangani.

---

## ✅ Benar

```javascript
try {

    const response =
        await fetch("/api/chat");

} catch (error) {

    console.error(error);

}
```

---

# 12. STATE WAJIB PADA CHATBOT

Frontend harus menangani minimal:

```text
Idle
Loading
Success
Error
Empty
Timeout
Abort
```

Flow:

```text
User mengetik
 ↓
Validasi
 ↓
Empty?
 ├── Ya → jangan request
 │
 └── Tidak
       ↓
Loading
       ↓
Request
       ↓
Success?
 ├── Ya
 │    ↓
 │ Response
 │
 └── Tidak
      ↓
   Error UI
```

---

# 13. LOADING STATE

Saat user mengirim pesan:

```text
User message
 ↓
Disable tombol kirim
 ↓
Tampilkan "AI sedang mengetik..."
 ↓
Request
 ↓
Jawaban selesai
 ↓
Enable tombol
```

Contoh:

```javascript
sendButton.disabled = true;

showTypingIndicator();

try {

    const answer =
        await sendMessage(message);

    appendAssistantMessage(answer);

} finally {

    hideTypingIndicator();

    sendButton.disabled = false;
}
```

---

# 14. EMPTY STATE

Jangan mengirim:

```text
""
" "
"     "
```

Contoh:

```javascript
const message =
    input.value.trim();

if (!message) {
    return;
}
```

---

# 15. TIMEOUT

Request AI jangan dibiarkan menggantung selamanya.

Gunakan:

```javascript
AbortController
```

Contoh:

```javascript
const controller =
    new AbortController();

const timeout =
    setTimeout(() => {
        controller.abort();
    }, 30000);

try {

    const response = await fetch(
        "/api/chat",
        {
            method: "POST",

            signal:
                controller.signal,

            headers: {
                "Content-Type":
                    "application/json"
            },

            body:
                JSON.stringify({
                    message
                })
        }
    );

} finally {

    clearTimeout(timeout);

}
```

Default timeout awal:

```text
30 detik
```

Nilai dapat disesuaikan dengan kebutuhan.

---

# 16. ERROR HANDLING

Jangan tampilkan error teknis kepada user seperti:

```text
TypeError
ECONNRESET
stack trace
API key
raw Together response
internal server information
```

Gunakan pesan sederhana:

```text
Maaf, terjadi kesalahan saat memproses pesan.
Silakan coba kembali.
```

Tetapi server boleh melakukan logging teknis.

---

# 17. HTTP STATUS

Gunakan HTTP status secara tepat.

```text
200
Request berhasil.

400
Input pengguna tidak valid.

404
Resource tidak ditemukan.

429
Terlalu banyak request.

500
Internal server error.

502 / 503
External AI service sedang bermasalah.
```

Jangan selalu mengembalikan:

```text
200 OK
```

untuk semua situasi.

---

# 18. CHAT HISTORY

Versi pertama boleh menggunakan satu pesan.

Tetapi struktur harus mudah dikembangkan menjadi:

```javascript
messages: [
    {
        role: "system",
        content: "..."
    },

    {
        role: "user",
        content: "Halo"
    },

    {
        role: "assistant",
        content: "Halo juga."
    },

    {
        role: "user",
        content: "Apa yang kita bicarakan tadi?"
    }
]
```

Role yang digunakan:

```text
system
user
assistant
```

Jangan menyimpan history tanpa batas.

Gunakan strategi seperti:

```text
20 pesan terakhir
```

atau mekanisme summarization jika project sudah lebih lanjut.

---

# 19. SYSTEM PROMPT

Buat system prompt terpisah supaya mudah dikelola.

Contoh:

```javascript
const SYSTEM_PROMPT = `
Kamu adalah asisten AI.

Gunakan bahasa Indonesia yang:
- sederhana,
- jelas,
- terstruktur,
- tidak bertele-tele.

Jika tidak mengetahui jawabannya,
jangan mengarang informasi.
`;
```

Kemudian:

```javascript
messages: [
    {
        role: "system",
        content: SYSTEM_PROMPT
    },

    {
        role: "user",
        content: message
    }
]
```

---

# 20. VALIDASI INPUT

Minimal validasi:

```text
harus string
tidak kosong
trim whitespace
batasi panjang
```

Contoh:

```javascript
if (
    typeof message !== "string" ||
    message.trim().length === 0
) {
    return res.status(400).json({
        error: "Pesan tidak valid."
    });
}
```

Tambahkan batas:

```javascript
const MAX_MESSAGE_LENGTH = 5000;
```

Kemudian:

```javascript
if (
    message.length >
    MAX_MESSAGE_LENGTH
) {
    return res.status(400).json({
        error:
            "Pesan terlalu panjang."
    });
}
```

---

# 21. UI CHATBOT

Buat UI sederhana seperti:

```text
┌───────────────────────────────┐
│ AI Assistant                  │
├───────────────────────────────┤
│                               │
│ AI                            │
│ Halo, ada yang bisa dibantu?  │
│                               │
│                  User         │
│       Jelaskan tentang API.   │
│                               │
│ AI                            │
│ API adalah...                 │
│                               │
├───────────────────────────────┤
│ Ketik pesan...          Send  │
└───────────────────────────────┘
```

UI harus:

```text
Responsive
Mobile friendly
Accessible
Readable
Simple
```

---

# 22. ACCESSIBILITY

Gunakan semantic HTML.

Contoh:

```html
<form id="chat-form">

    <label
        for="chat-input"
        class="sr-only"
    >
        Ketik pesan
    </label>

    <textarea
        id="chat-input"
        placeholder="Ketik pesan..."
        required
    ></textarea>

    <button type="submit">
        Kirim
    </button>

</form>
```

Jangan mengganti semua elemen menjadi:

```html
<div>
```

jika tersedia elemen semantic yang sesuai.

---

# 23. XSS PROTECTION

Jawaban AI dianggap sebagai data yang tidak terpercaya.

## ❌ Hindari

```javascript
messageElement.innerHTML =
    aiResponse;
```

Ini berisiko apabila response mengandung HTML berbahaya.

---

## ✅ Gunakan

```javascript
messageElement.textContent =
    aiResponse;
```

Jika suatu saat membutuhkan Markdown, gunakan parser dan sanitizer yang terpercaya.

---

# 24. RATE LIMITING

Sebelum production, implementasikan rate limiting.

Tujuan:

```text
Menghindari spam
Menghindari abuse
Melindungi backend
Mengurangi request tidak perlu
```

Contoh konsep:

```text
IP
 ↓
maksimum request
 ↓
per interval tertentu
```

Jangan mengandalkan tombol disabled di frontend sebagai sistem keamanan.

Frontend dapat dimanipulasi user.

---

# 25. DOUBLE SUBMISSION

Jangan biarkan user mengirim request yang sama berkali-kali saat request sebelumnya masih berjalan.

Gunakan:

```javascript
let isSending = false;
```

Contoh:

```javascript
if (isSending) {
    return;
}

isSending = true;

try {

    // request

} finally {

    isSending = false;

}
```

---

# 26. KODE YANG HARUS DIHINDARI

## ❌ Semua kode dalam satu file

```text
index.html
```

berisi:

```text
HTML
CSS
JS
API logic
secret key
server logic
```

Tidak diperbolehkan.

---

## ❌ API key hardcoded

```javascript
const apiKey =
    "tgp_xxxxxxxxxx";
```

---

## ❌ Fetch tanpa error handling

```javascript
const response =
    await fetch(url);

const data =
    await response.json();
```

tanpa:

```javascript
response.ok
```

---

## ❌ Empty catch

```javascript
try {

    await sendMessage();

} catch (error) {

}
```

---

## ❌ God Function

Hindari:

```javascript
async function chat() {
    // 300 baris
}
```

Pecah berdasarkan tanggung jawab.

---

# 27. POLA KODE YANG DIINGINKAN

Gunakan fungsi kecil dan jelas seperti:

```text
sendMessage()
appendMessage()
showTypingIndicator()
hideTypingIndicator()
setLoading()
validateMessage()
handleChatSubmit()
```

Daripada:

```text
doEverything()
run()
process()
handle()
```

Nama fungsi harus menggambarkan tanggung jawabnya.

---

# 28. DEAD CODE

Jangan meninggalkan:

```javascript
function oldChat() {}

const unusedVariable = true;

// kode lama
// fetchOldAPI();
```

Jika sudah tidak digunakan:

```text
hapus
```

Gunakan Git untuk history kode.

Jangan menggunakan source code sebagai tempat penyimpanan kode lama.

---

# 29. COMMENT

Comment menjelaskan:

```text
KENAPA
```

bukan hanya:

```text
APA
```

## ❌ Kurang berguna

```javascript
// Mengirim request
await fetch(url);
```

## ✅ Lebih berguna

```javascript
// Request dilakukan melalui backend agar
// API key Together tidak terekspos ke browser.
await fetch("/api/chat");
```

---

# 30. WORKFLOW DEVELOPMENT

Gunakan pendekatan terstruktur.

## STEP 1 — Requirement

Tentukan:

```text
Siapa pengguna?
Apa fungsi chatbot?
Apa model AI?
Apa data yang dikirim?
Apa response yang diperlukan?
```

---

## STEP 2 — Design

Buat flow:

```text
UI
 ↓
Frontend JS
 ↓
Backend
 ↓
Together AI
 ↓
Backend
 ↓
UI
```

---

## STEP 3 — Implementation

Urutan:

```text
1. Setup Node.js

2. Setup Express

3. Setup .env

4. Buat Together service

5. Buat API route

6. Tes menggunakan request manual

7. Buat HTML

8. Buat CSS

9. Buat JavaScript frontend

10. Hubungkan frontend → backend

11. Tambahkan loading

12. Tambahkan error

13. Tambahkan timeout

14. Tambahkan validation

15. Tambahkan security
```

---

## STEP 4 — Testing

Test minimal:

```text
Pesan normal
Pesan kosong
Pesan panjang
Internet terputus
API gagal
API timeout
API key salah
User double-click
Response kosong
Server mati
```

---

## STEP 5 — Review

Periksa:

```text
Security
Async
Error handling
Validation
Dead code
Naming
Separation of Concerns
Accessibility
Responsive design
```

---

# 31. DEVELOPMENT MODE

Untuk menjalankan project:

```bash
npm install
```

kemudian:

```bash
npm run dev
```

atau:

```bash
node server.js
```

Pastikan `package.json` menggunakan ES Module jika kode menggunakan `import`.

Contoh:

```json
{
  "type": "module"
}
```

---

# 32. RESPONSE FORMAT BACKEND

Gunakan format konsisten.

Success:

```json
{
    "success": true,
    "message": "Jawaban AI..."
}
```

Error:

```json
{
    "success": false,
    "error": "Terjadi kesalahan."
}
```

Jangan mencampur berbagai struktur response tanpa alasan.

---

# 33. LOGGING

Boleh log:

```text
status code
request failure
service failure
timestamp
```

Jangan log:

```text
TOGETHER_API_KEY
Authorization header
password
token rahasia
informasi sensitif pengguna
```

---

# 34. CONFIGURATION

Jangan menyebarkan configuration magic string ke banyak file.

Contoh:

```javascript
export const AI_CONFIG = {
    model:
        "Prism-ML/Ternary-Bonsai-27B",

    timeout:
        30000,

    maxMessageLength:
        5000
};
```

Project kecil boleh menggunakan konfigurasi sederhana.

Jangan melakukan abstraction berlebihan.

---

# 35. PRINSIP SOFTWARE ENGINEERING

Ikuti prinsip:

```text
KISS
Keep It Simple, Stupid

DRY
Don't Repeat Yourself

YAGNI
You Aren't Gonna Need It

Separation of Concerns

Single Responsibility
```

Tetapi jangan memaksakan pattern rumit ke project sederhana.

Tujuan kode:

```text
Mudah dibaca
Mudah diuji
Mudah diperbaiki
Mudah dikembangkan
Aman
```

---

# 36. PREMATURE OPTIMIZATION

Jangan langsung membuat:

```text
Microservices
Redis
Docker cluster
Kubernetes
Message broker
Complex repository pattern
10-layer architecture
```

untuk chatbot sederhana.

Mulai dengan:

```text
Frontend
 ↓
Express
 ↓
Service
 ↓
Together AI
```

Tambahkan kompleksitas hanya jika ada kebutuhan nyata.

---

# 37. PERSIAPAN MIGRASI KE PHP / LARAVEL

Walaupun project sekarang menggunakan Node.js, buat arsitektur yang dapat dikonversi nanti.

Sekarang:

```text
routes/chat.js
 ↓
services/together.service.js
```

Nanti di Laravel dapat menjadi:

```text
routes/api.php
 ↓
ChatController
 ↓
TogetherAIService
```

Konsepnya tetap:

```text
Route
 ↓
Controller
 ↓
Service
 ↓
External API
```

Karena itu jangan mencampur UI dan AI integration.

---

# 38. JIKA USER NANTI MEMINTA PHP NATIVE

Jangan otomatis mempertahankan Node.js jika pengguna meminta migrasi.

Konversi:

```text
Node.js Route
        ↓
PHP endpoint

Together Service JS
        ↓
PHP Service Class

process.env
        ↓
getenv() / .env
```

Tetapi pertahankan:

```text
Frontend
Security
Validation
Error handling
API contract
```

sebisa mungkin.

---

# 39. JIKA USER NANTI MEMINTA LARAVEL

Gunakan struktur:

```text
routes/api.php
 ↓
ChatController
 ↓
TogetherAIService
 ↓
Together AI
```

API key:

```env
TOGETHER_API_KEY=
```

Lalu configuration:

```text
config/services.php
```

Jangan meletakkan API integration seluruhnya di Controller.

---

# 40. CHECKLIST SEBELUM COMMIT

Coding agent WAJIB memeriksa:

```text
[ ] API key tidak berada di frontend

[ ] .env masuk .gitignore

[ ] .env.example tersedia

[ ] Input divalidasi

[ ] Empty message ditolak

[ ] Async menggunakan await

[ ] Promise tidak dibiarkan

[ ] response.ok diperiksa

[ ] Loading state tersedia

[ ] Error state tersedia

[ ] Timeout tersedia

[ ] Abort tersedia

[ ] Double submit dicegah

[ ] Error server tidak membocorkan secret

[ ] Response AI tidak langsung dimasukkan via innerHTML

[ ] Tidak terdapat dead code

[ ] Tidak terdapat variable tidak digunakan

[ ] Fungsi tidak terlalu besar

[ ] Naming jelas

[ ] UI responsive

[ ] Basic accessibility tersedia

[ ] Console tidak mencetak API key

[ ] Model menggunakan konfigurasi yang benar
```

---

# 41. RULE UNTUK AI CODING AGENT

Saat menghasilkan kode:

1. Jelaskan file apa yang akan dibuat.

2. Jangan langsung memberikan arsitektur terlalu kompleks.

3. Jangan memasukkan API key asli.

4. Gunakan placeholder:

```env
TOGETHER_API_KEY=your_api_key_here
```

5. Jangan menyimpan API key di frontend.

6. Gunakan kode yang bisa dijalankan.

7. Jangan memberikan pseudocode jika pengguna meminta implementasi.

8. Jika memodifikasi project, pertahankan struktur yang sudah ada selama masih baik.

9. Jangan menghapus fitur tanpa alasan.

10. Jangan membuat dependency baru jika native API cukup.

11. Tangani failure state.

12. Tangani asynchronous operation secara eksplisit.

13. Jangan membuat Promise tanpa `await`, `return`, atau `.catch()` jika hasilnya relevan.

14. Hindari global state yang tidak dibutuhkan.

15. Jangan membuat fungsi ratusan baris.

16. Jangan membuat abstraction yang belum diperlukan.

17. Jika terdapat bug, jelaskan root cause sebelum memberikan perubahan besar.

18. Pertahankan keamanan sebagai prioritas pertama.

---

# 42. DEFINITION OF DONE

Fitur chatbot dianggap selesai jika:

```text
User dapat membuka website
 ↓
Mengetik pesan
 ↓
Menekan tombol kirim
 ↓
Loading tampil
 ↓
Frontend memanggil backend
 ↓
Backend memvalidasi input
 ↓
Backend memanggil Together AI
 ↓
Ternary Bonsai menghasilkan response
 ↓
Backend mengirim response aman
 ↓
Frontend menampilkan jawaban
```

Dan kondisi berikut juga bekerja:

```text
Empty input
API error
Timeout
Network error
Double submit
Invalid response
```

Tanpa mengekspos:

```text
TOGETHER_API_KEY
```

---

# 43. ARSITEKTUR FINAL YANG HARUS DIPERTAHANKAN

```text
┌──────────────────────────────────┐
│            FRONTEND              │
│                                  │
│ HTML + CSS + Vanilla JavaScript  │
│                                  │
│ Tidak mempunyai Together API Key │
└────────────────┬─────────────────┘
                 │
                 │ POST /api/chat
                 ▼
┌──────────────────────────────────┐
│             BACKEND              │
│                                  │
│ Node.js + Express                │
│                                  │
│ Validation                       │
│ Error Handling                   │
│ Timeout                          │
│ Security                         │
└────────────────┬─────────────────┘
                 │
                 │ Bearer API Key
                 ▼
┌──────────────────────────────────┐
│          TOGETHER AI             │
│                                  │
│ Prism-ML/Ternary-Bonsai-27B      │
└────────────────┬─────────────────┘
                 │
                 │ AI Response
                 ▼
┌──────────────────────────────────┐
│             BACKEND              │
└────────────────┬─────────────────┘
                 │
                 │ JSON
                 ▼
┌──────────────────────────────────┐
│             FRONTEND             │
│                                  │
│ Menampilkan jawaban chatbot      │
└──────────────────────────────────┘
```

---

# 44. PRIORITAS IMPLEMENTASI

Urutan prioritas:

```text
1. Security
       ↓
2. Functionality
       ↓
3. Error Handling
       ↓
4. Maintainability
       ↓
5. User Experience
       ↓
6. Optimization
```

Jangan mengorbankan keamanan hanya untuk membuat implementasi lebih singkat.

---

# FINAL INSTRUCTION

Bangun project seperti **software engineer profesional**, tetapi pertahankan implementasi agar dapat dipahami developer pemula.

Saat terdapat beberapa pilihan solusi, prioritaskan:

```text
Solusi sederhana
+
Aman
+
Mudah dipelihara
+
Mudah dimigrasikan
+
Tidak over-engineering
```

Untuk kondisi project saat ini, gunakan:

```text
HTML
CSS
Vanilla JavaScript
Node.js
Express.js
Together AI
Prism-ML/Ternary-Bonsai-27B
```

Jangan menggunakan PHP Native atau Laravel sampai pengguna secara eksplisit meminta migrasi.
<style>
    #chatbot-container {
        position: fixed !important;
        bottom: 20px !important;
        right: 20px !important;
        top : auto !important;
        left: auto !important;
        z-index: 9999;
        font-family: Arial, Helvetica, sans-serif;
    }

    .chatbot-floating-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #ffbf5f; 
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease, background-color 0.2s ease, opacity 0.5s ease;
    }

    .chatbot-floating-btn:hover {
        transform: scale(1.05);
        background-color: #ee7f3e;
    }

    .chatbot-window {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 320px;
        height: 480px;
        background-color: #ffffff;
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid #eaeaea;
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
        transition: opacity 0.5s ease, transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .chatbot-window.hidden {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
        pointer-events: none;
        visibility: hidden;
        transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s linear 0.3s;
    }

    .chatbot-header {
        background-color: #ee7f3e; 
        padding: 14px 18px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chatbot-brand {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chatbot-avatar {
        background-color: rgba(255, 255, 255, 0.25);
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chatbot-title {
        font-weight: bold;
        font-size: 18px;
        letter-spacing: 0.5px;
    }

    .chatbot-close-btn {
        background: transparent;
        border: none;
        color: white;
        font-size: 22px;
        cursor: pointer;
        line-height: 1;
    }

    .chatbot-body {
        flex: 1;
        padding: 16px;
        background-color: #f9f9f9;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .chat-message {
        max-width: 85%;
        padding: 10px 16px;
        font-size: 14px;
        line-height: 1.45;
        white-space: pre-line;
        word-break: break-word;
    }

    .bot-message {
        background-color: #ee7f3e;
        color: white;
        align-self: flex-start;
        border-radius: 18px 18px 18px 0px;
    }

    .user-message {
        background-color: #fcae55; 
        color: white;
        align-self: flex-end;
        border-radius: 18px 18px 0px 18px;
    }

    .chatbot-footer {
        padding: 10px 12px;
        background-color: #ffffff;
        border-top: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chatbot-input {
        flex: 1;
        border: 1px solid #dddddd;
        border-radius: 20px;
        padding: 8px 14px;
        font-size: 14px;
        outline: none;
    }

    .chatbot-input:focus {
        border-color: #ee7f3e;
    }

    .chatbot-send-btn {
        background-color: #fcae55;
        border: none;
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chatbot-send-btn:hover {
        background-color: #ee7f3e;
    }
</style>

<div id="chatbot-container">
    <!-- Tombol Floating -->
    <button id="chatbot-toggle-btn" class="chatbot-floating-btn" onclick="toggleChatbot()">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
    </button>

    <!-- muncul kalau dibuka -->
    <div id="chatbot-window" class="chatbot-window hidden">
        
        <!-- Header Chatbot -->
        <div class="chatbot-header">
            <div class="chatbot-brand">
              <div class="chatbot-avatar" style="overflow: hidden; background: #fff;">
    <img src="https://thumbs.dreamstime.com/b/d-smart-cute-robot-head-graduation-cap-minimal-style-isolated-background-chat-bot-icon-ai-fueled-customer-support-288448884.jpg" 
         alt="jiBot Avatar" 
         style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
</div>
                <span class="chatbot-title">jiBot</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button title="Bersihkan obrolan" onclick="resetChat()" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 14px; padding: 2px 4px; opacity: 0.85;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'">🔄</button>
                <button class="chatbot-close-btn" onclick="toggleChatbot()">&times;</button>
            </div>
        </div>

        <!-- Area Pesan (Body) -->
        <div id="chatbot-body" class="chatbot-body">
            <!-- Pesan dari Bot (Warna Toska) -->
            <div class="chat-message bot-message">
                Halo! Ada yang bisa dibantu hari ini?
            </div>

        </div>

        <!-- Form Input Pesan -->
        <div class="chatbot-footer">
            <input id="chatbot-input" type="text" placeholder="Ketik pesan..." class="chatbot-input">
            <button id="chatbot-send-btn" class="chatbot-send-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </div>

    </div>
</div>

<!-- JavaScript Vanilla untuk Toggle Buka/Tutup -->
<script>
    function toggleChatbot() {
        // deklarasi variabel dan ambil id
        const chatWindow = document.getElementById('chatbot-window');
        const toggleBtn = document.getElementById('chatbot-toggle-btn');

        if (chatWindow.classList.contains('hidden')) {
            // buka chat
            chatWindow.classList.remove('hidden');
            toggleBtn.style.opacity = '0';
            toggleBtn.style.display = 'none'; 
            // setTimeout(() => {
            //     toggleBtn.style.display = 'none';
            // }, 500);
        } else {
            // tutup chat
            chatWindow.classList.add('hidden');
            // toggleBtn.style.display = 'flex';
            setTimeout(() => {
                toggleBtn.style.opacity = '1';
                toggleBtn.style.display = 'flex';
            }, 300);
        }
    }

    // deklarasi variabel untuk kirim pesan
    const chatBody = document.getElementById('chatbot-body');
    const chatInput = document.getElementById('chatbot-input');
    const sendBtn = document.getElementById('chatbot-send-btn');

    // Fungsi format teks bot (mengubah markdown **tebal**, bullet point, dan link)
    function formatChatText(text) {
        if (!text) return '';
        // 1. Escape HTML agar aman
        let html = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // 2. Format Bold (**teks** -> <strong>teks</strong>)
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong style="font-weight: 700;">$1</strong>');

        // 3. Format Bullet point (* atau - di awal baris jadi •)
        html = html.replace(/^[\*\-]\s+/gm, '• ');

        // 4. Format Markdown Link [Judul](URL)
        html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" style="color: #ffffff; text-decoration: underline; font-weight: bold;">$1</a>');

        return html;
    }

    // Riwayat chat dari sessionStorage agar obrolan nyambung
    let chatHistory = JSON.parse(sessionStorage.getItem('jibot_history') || '[]');

    function resetChat() {
        sessionStorage.removeItem('jibot_history');
        chatHistory = [];
        chatBody.innerHTML = `
            <div class="chat-message bot-message">
                Halo! Ada yang bisa dibantu hari ini?
            </div>
        `;
    }

    function addBubble(text, type) {
        const bubble = document.createElement('div');
        bubble.classList.add('chat-message', type);
        bubble.innerHTML = formatChatText(text);
        chatBody.appendChild(bubble);
        chatBody.scrollTop = chatBody.scrollHeight;
        return bubble;
    }

    // Tampilkan riwayat chat sebelumnya jika ada di sessionStorage
    if (chatHistory.length > 0) {
        chatHistory.forEach(item => {
            const type = item.role === 'user' ? 'user-message' : 'bot-message';
            addBubble(item.text, type);
        });
    }

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (message === '') return;

        addBubble(message, 'user-message');
        chatInput.value = '';
        chatInput.disabled = true;
        sendBtn.disabled = true;

        const loading = addBubble('Sedang mencari...', 'bot-message');

        try {
            const res = await fetch('/api/chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ 
                    message: message,
                    history: chatHistory
                }),
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            loading.innerHTML = formatChatText(data.reply);

            // Simpan percakapan ke riwayat lokal dan sessionStorage
            chatHistory.push({ role: 'user', text: message });
            chatHistory.push({ role: 'model', text: data.reply });
            sessionStorage.setItem('jibot_history', JSON.stringify(chatHistory));
        } catch (err) {
            console.error(err);
            loading.textContent = 'Maaf, terjadi kesalahan. Coba lagi sebentar ya.';
        } finally {
            chatInput.disabled = false;
            sendBtn.disabled = false;
            chatInput.focus();
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

</script>
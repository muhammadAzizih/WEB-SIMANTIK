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
        background-color: #33A0F6; 
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
        background-color: #33A0F6;
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
        background-color: #33A0F6; 
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
        max-width: 75%;
        padding: 10px 16px;
        font-size: 14px;
        line-height: 1.4;
    }

    .bot-message {
        background-color: #33A0F6;
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
        border-color: #33A0F6;
    }

    .chatbot-send-btn {
        background-color: #33A0F6;
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
        background-color: #33A0F6;
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
                <div class="chatbot-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <span class="chatbot-title">jiBot</span>
            </div>
            <button class="chatbot-close-btn" onclick="toggleChatbot()">&times;</button>
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

    function sendMessage() {
        const message = chatInput.value.trim();

        if (message === '') return;

        const userBubble = document.createElement('div');
        userBubble.classList.add('chat-message', 'user-message');
        userBubble.textContent = message;

        chatBody.appendChild(userBubble);
        chatBody.scrollTop = chatBody.scrollHeight;
        chatInput.value = '';
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

</script>
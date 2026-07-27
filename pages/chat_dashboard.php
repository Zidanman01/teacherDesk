<style>
    .chat-container { height: 60vh; overflow-y: auto; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; padding: 1rem; }
    .message { margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 0.5rem; max-width: 80%; white-space: pre-wrap; }
    .msg-user { background-color: #0d6efd; color: white; margin-left: auto; border-bottom-right-radius: 0; }
    .msg-ai { background-color: #e9ecef; color: #212529; margin-right: auto; border-bottom-left-radius: 0; }
    .loading-text { font-style: italic; color: #6c757d; font-size: 0.9rem; }
</style>

<div class="card shadow-sm" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary">🤖 Asisten Pengajar AI</h5>
        <small class="text-muted">Konsultasikan silabus dan metode mengajar Anda</small>
    </div>
    
    <div class="card-body">
        <div id="chatBox" class="chat-container mb-3 d-flex flex-column">
            <div class="message msg-ai">
                Halo! Saya adalah asisten pengajar AI di TeacherDesk. Ada yang bisa saya bantu terkait materi pelajaran atau manajemen kelas hari ini?
            </div>
        </div>

        <div class="input-group" style="display: flex;">
            <input type="text" id="chatInput" class="form-control" style="flex: 1; padding: 10px;" placeholder="Ketik pertanyaan Anda di sini..." autocomplete="off">
            <button class="btn btn-primary px-4" id="btnSend" onclick="sendMessage()" style="margin-left: 10px;">Kirim</button>
        </div>
    </div>
</div>

<script>
    let chatHistory = [
        { role: "system", content: "Kamu adalah asisten pengajar profesional dan konsultan pendidikan di platform TeacherDesk. Jawablah dengan terstruktur dan ramah." }
    ];

    const chatBox = document.getElementById('chatBox');
    const chatInput = document.getElementById('chatInput');
    const btnSend = document.getElementById('btnSend');

    chatInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        appendMessage('user', message);
        chatInput.value = '';
        chatInput.disabled = true;
        btnSend.disabled = true;

        chatHistory.push({ role: 'user', content: message });

        const loadingId = 'loading-' + Date.now();
        chatBox.innerHTML += `<div id="${loadingId}" class="loading-text mb-3 ms-2">AI sedang mengetik...</div>`;
        scrollToBottom();

        try {
            // Memanggil API yang sudah Anda buat
            const response = await fetch('api_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ messages: chatHistory })
            });

            const data = await response.json();
            
            document.getElementById(loadingId).remove();

            if (data.status === 'success') {
                appendMessage('ai', data.reply);
                chatHistory.push({ role: 'assistant', content: data.reply });
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            document.getElementById(loadingId).remove();
            alert('Gagal menghubungi server. Periksa koneksi Anda.');
        } finally {
            chatInput.disabled = false;
            btnSend.disabled = false;
            chatInput.focus();
            scrollToBottom();
        }
    }

    function appendMessage(sender, text) {
        const div = document.createElement('div');
        div.className = `message ${sender === 'user' ? 'msg-user' : 'msg-ai'}`;
        div.textContent = text;
        chatBox.appendChild(div);
        scrollToBottom();
    }

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>
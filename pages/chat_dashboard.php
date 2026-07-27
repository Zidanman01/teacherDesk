<?php 
require_once __DIR__ . '/../app/Database.php';

$conn = Database::connection(); 

// 1. Logika untuk Menghapus Sesi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['session_id'])) {
    $delStmt = $conn->prepare("DELETE FROM chat_history WHERE session_id = ?");
    $delStmt->execute([$_GET['session_id']]);
    // Redirect kembali ke halaman chat_dashboard setelah menghapus
    header("Location: ?page=chat_dashboard"); 
    exit();
}

// 2. Menentukan Sesi Aktif
$currentSession = $_GET['session'] ?? uniqid('chat_');

// 3. Mengambil Maksimal 10 Sesi Riwayat
$sessionStmt = $conn->prepare("
    SELECT session_id, 
           (SELECT content FROM chat_history ch2 WHERE ch2.session_id = ch1.session_id ORDER BY id ASC LIMIT 1) as first_msg 
    FROM chat_history ch1 
    GROUP BY session_id 
    ORDER BY MAX(id) DESC 
    LIMIT 10
");
$sessionStmt->execute();
$recentSessions = $sessionStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Mengambil Riwayat Pesan
$stmt = $conn->prepare("SELECT role, content FROM chat_history WHERE session_id = ? ORDER BY id ASC");
$stmt->execute([$currentSession]);
$chat_histories = $stmt->fetchAll(PDO::FETCH_ASSOC); 

if (empty($chat_histories)) {
    $chat_histories[] = [
        'role' => 'assistant',
        'content' => 'Halo! Saya adalah asisten pengajar AI di TeacherDesk. Klik "+ Chat Baru" untuk memulai topik baru, atau ketik pertanyaanmu di bawah ini!'
    ];
}
?>

<style>
    /* CSS Sama seperti sebelumnya */
    .app-layout { display: flex; gap: 20px; height: calc(100vh - 100px); margin-top: 10px; }
    .history-sidebar { width: 260px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; display: flex; flex-direction: column; padding: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .btn-new-chat { display: block; width: 100%; padding: 10px; background-color: #3b82f6; color: white; text-align: center; border-radius: 8px; font-weight: bold; text-decoration: none; margin-bottom: 15px; transition: background 0.2s; }
    .btn-new-chat:hover { background-color: #2563eb; color: white; }
    .history-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
    .history-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-radius: 8px; background-color: #f9fafb; border: 1px solid transparent; transition: border 0.2s; }
    .history-item:hover { border-color: #d1d5db; }
    .history-item.active { background-color: #eff6ff; border-color: #bfdbfe; }
    .history-link { color: #374151; text-decoration: none; font-size: 0.85rem; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .btn-delete { color: #9ca3af; text-decoration: none; font-size: 0.9rem; margin-left: 8px; }
    .btn-delete:hover { color: #ef4444; }
    .chat-wrapper { flex: 1; display: flex; flex-direction: column; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e5e7eb; overflow: hidden; }
    .chat-container { flex: 1; overflow-y: auto; padding: 1.5rem 1.5rem 0 1.5rem; scroll-behavior: smooth; background-color: #f9fafb; }
    .message { margin-bottom: 1.5rem; padding: 1rem 1.25rem; border-radius: 1rem; max-width: 80%; font-size: 0.95rem; line-height: 1.6; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .msg-user { background-color: #3b82f6; color: white; margin-left: auto; border-bottom-right-radius: 0.2rem; }
    .msg-ai { background-color: #ffffff; color: #1f2937; margin-right: auto; border-bottom-left-radius: 0.2rem; border: 1px solid #e5e7eb; position: relative; }
    .btn-copy { display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; padding: 4px 10px; font-size: 0.8rem; color: #4b5563; background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; cursor: pointer; }
    .btn-copy:hover { background-color: #e5e7eb; }
    .input-wrapper { flex-shrink: 0; padding: 1rem 1.5rem; background-color: #ffffff; border-top: 1px solid #f3f4f6; display: flex; justify-content: center; }
    .chat-input-box { display: flex; background-color: #f9fafb; border-radius: 2rem; padding: 0.4rem 0.4rem 0.4rem 1.5rem; border: 1px solid #d1d5db; width: 100%; max-width: 800px; }
    .chat-input-box input { flex: 1; border: none; background: transparent; outline: none; font-size: 0.95rem; color: #374151; }
    .chat-input-box button { border-radius: 1.5rem; padding: 8px 24px; background-color: #3b82f6; color: white; border: none; font-weight: 600; cursor: pointer; }
    .msg-ai pre { background: #1f2937; color: #e5e7eb; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin: 0.75rem 0; }
    .msg-ai code { font-family: Consolas, monospace; }
    .loading-text { font-style: italic; color: #6c757d; font-size: 0.9rem; }
</style>

<div class="app-layout">
    
    <!-- KOLOM KIRI: DAFTAR RIWAYAT -->
    <div class="history-sidebar">
        <!-- PERBAIKAN URL: Selalu panggil page=chat_dashboard -->
        <a href="?page=chat_dashboard" class="btn-new-chat">➕ Chat Baru</a>
        
        <div class="history-list">
            <?php foreach ($recentSessions as $ses): 
                $title = htmlspecialchars(mb_strimwidth($ses['first_msg'] ?? 'Obrolan Baru', 0, 25, '...'));
                $isActive = ($ses['session_id'] === $currentSession) ? 'active' : '';
            ?>
                <div class="history-item <?= $isActive ?>">
                    <!-- PERBAIKAN URL: Tambahkan page=chat_dashboard -->
                    <a href="?page=chat_dashboard&session=<?= urlencode($ses['session_id']) ?>" class="history-link" title="<?= htmlspecialchars($ses['first_msg']) ?>">
                        💬 <?= $title ?>
                    </a>
                    <!-- PERBAIKAN URL: Tambahkan page=chat_dashboard -->
                    <a href="?page=chat_dashboard&action=delete&session_id=<?= urlencode($ses['session_id']) ?>" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus obrolan ini?');">🗑️</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KOLOM KANAN: KOTAK CHAT -->
    <div class="chat-wrapper">
        <div id="chatBox" class="chat-container">
            <?php foreach ($chat_histories as $chat): ?>
                <?php if ($chat['role'] === 'user'): ?>
                    <div class="message msg-user"><?php echo htmlspecialchars($chat['content']); ?></div>
                <?php else: ?>
                    <div class="message msg-ai">
                        <div class="ai-text msg-ai-history"><?php echo htmlspecialchars($chat['content']); ?></div>
                        <button class="btn-copy" onclick="copyText(this)">📋 Salin</button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="input-wrapper">
            <div class="chat-input-box">
                <input type="text" id="chatInput" placeholder="Ketik pertanyaan Anda di sini..." autocomplete="off">
                <button id="btnSend" onclick="sendMessage()">Kirim</button>
            </div>
        </div>
    </div>
</div>

<script>
    const activeSessionId = "<?= $currentSession ?>";

    document.addEventListener('DOMContentLoaded', function() {
        const historyAiMessages = document.querySelectorAll('.msg-ai-history');
        historyAiMessages.forEach(function(msgDiv) {
            const rawText = msgDiv.textContent;
            msgDiv.innerHTML = formatAIResponse(rawText);
            msgDiv.classList.remove('msg-ai-history'); 
        });
        scrollToBottom();
    });

    let chatHistory = [
        { role: "system", content: "Kamu adalah asisten pengajar profesional dan konsultan pendidikan di platform TeacherDesk. Jawablah dengan terstruktur, ramah, dan tidak menggunakan emoji." }
    ];

    const chatBox = document.getElementById('chatBox');
    const chatInput = document.getElementById('chatInput');
    const btnSend = document.getElementById('btnSend');

    chatInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function formatAIResponse(text) {
        let html = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
        html = html.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
        html = html.replace(/`([^`]+)`/g, '<code style="background:#e9ecef; padding:2px 5px; border-radius:3px; color:#d63384;">$1</code>');
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        let parts = html.split(/(<pre[\s\S]*?<\/pre>)/g);
        for (let i = 0; i < parts.length; i++) {
            if (!parts[i].startsWith('<pre')) {
                parts[i] = parts[i].replace(/\n/g, '<br>');
            }
        }
        return parts.join('');
    }

    function copyText(buttonElement) {
        const textElement = buttonElement.previousElementSibling;
        const textToCopy = textElement.innerText;
        navigator.clipboard.writeText(textToCopy).then(() => {
            const originalText = buttonElement.innerHTML;
            buttonElement.innerHTML = "✅ Tersalin!";
            setTimeout(() => { buttonElement.innerHTML = originalText; }, 2000);
        }).catch(err => { alert("Gagal menyalin teks."); });
    }

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        appendMessage('user', message, false);
        chatInput.value = '';
        chatInput.disabled = true;
        btnSend.disabled = true;

        chatHistory.push({ role: 'user', content: message });

        const loadingId = 'loading-' + Date.now();
        chatBox.innerHTML += `<div id="${loadingId}" class="loading-text mb-3 ms-2">AI sedang mengetik...</div>`;
        scrollToBottom();

        try {
            const response = await fetch('api_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    session_id: activeSessionId,
                    messages: chatHistory 
                })
            });

            const data = await response.json();
            document.getElementById(loadingId).remove();

            if (data.status === 'success') {
                appendMessage('ai', data.reply, true);
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
            
            // PERBAIKAN URL PADA JS: Refresh dengan membawa page=chat_dashboard
            if (chatBox.querySelectorAll('.message').length <= 3) {
                setTimeout(() => window.location.href = "?page=chat_dashboard&session=" + activeSessionId, 500);
            }
        }
    }

    function appendMessage(sender, text, isMarkdown = false) {
        const div = document.createElement('div');
        div.className = `message ${sender === 'user' ? 'msg-user' : 'msg-ai'}`;
        if (isMarkdown) {
            const textDiv = document.createElement('div');
            textDiv.className = 'ai-text';
            textDiv.innerHTML = formatAIResponse(text);
            div.appendChild(textDiv);
            const copyBtn = document.createElement('button');
            copyBtn.className = 'btn-copy';
            copyBtn.innerHTML = '📋 Salin';
            copyBtn.onclick = function() { copyText(this); };
            div.appendChild(copyBtn);
        } else {
            div.textContent = text;
        }
        chatBox.appendChild(div);
        scrollToBottom();
    }

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>
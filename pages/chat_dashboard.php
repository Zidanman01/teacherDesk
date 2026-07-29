<?php
require_once __DIR__ . '/../app/Database.php';

$conn = Database::connection();

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['session_id'])) {
    $delStmt = $conn->prepare("DELETE FROM chat_history WHERE session_id = ?");
    $delStmt->execute([$_GET['session_id']]);
    header('Location: ?page=chat_dashboard');
    exit();
}

$currentSession = $_GET['session'] ?? uniqid('chat_');

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

$stmt = $conn->prepare("SELECT role, content FROM chat_history WHERE session_id = ? ORDER BY id ASC");
$stmt->execute([$currentSession]);
$storedChatHistories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chatHistories = $storedChatHistories;
if (empty($chatHistories)) {
    $chatHistories[] = [
        'role' => 'assistant',
        'content' => "Halo! Saya siap membantu menyusun CP, TP, ATP, asesmen, modul ajar, rubrik, dan kebutuhan pembelajaran lainnya.\n\nTuliskan konteks mata pelajaran, kelas atau fase, serta hasil yang Anda butuhkan agar jawaban lebih tepat."
    ];
}

$initialConversation = [];
foreach ($storedChatHistories as $history) {
    $role = (string) ($history['role'] ?? '');
    $content = trim((string) ($history['content'] ?? ''));

    if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
        $initialConversation[] = [
            'role' => $role,
            'content' => $content,
        ];
    }
}
?>
<style>
    .curriculum-chat-layout {
        display: flex;
        gap: 20px;
        height: calc(100vh - 100px);
        min-height: 620px;
        margin-top: 10px;
    }

    .history-sidebar {
        width: 270px;
        flex-shrink: 0;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        padding: 1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .sidebar-title {
        margin: 0 0 12px;
        color: #111827;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .btn-new-chat {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 42px;
        margin-bottom: 15px;
        padding: 10px 14px;
        background: #2563eb;
        color: #ffffff;
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
        transition: background-color 0.2s, transform 0.2s;
    }

    .btn-new-chat:hover {
        color: #ffffff;
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    .history-list {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        scrollbar-width: thin;
    }

    .history-empty {
        padding: 16px 10px;
        color: #9ca3af;
        font-size: 0.85rem;
        line-height: 1.5;
        text-align: center;
    }

    .history-item {
        display: flex;
        align-items: center;
        min-height: 42px;
        padding: 9px 10px;
        border: 1px solid transparent;
        border-radius: 10px;
        background: #f8fafc;
        transition: border-color 0.2s, background-color 0.2s;
    }

    .history-item:hover {
        border-color: #cbd5e1;
        background: #ffffff;
    }

    .history-item.active {
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .history-link {
        flex: 1;
        min-width: 0;
        color: #334155;
        font-size: 0.86rem;
        line-height: 1.4;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .btn-delete {
        margin-left: 8px;
        padding: 3px 6px;
        color: #94a3b8;
        border-radius: 6px;
        font-size: 0.82rem;
        text-decoration: none;
    }

    .btn-delete:hover {
        color: #dc2626;
        background: #fef2f2;
    }

    .chat-wrapper {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .chat-header {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 17px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .chat-header-copy {
        min-width: 0;
    }

    .chat-header h1 {
        margin: 0;
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 750;
    }

    .chat-header p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .ai-status {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        background: #f0fdf4;
        color: #166534;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .ai-status::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.14);
    }

    .chat-container {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        scroll-behavior: smooth;
        background: #f8fafc;
        scrollbar-width: thin;
    }

    .message-row {
        display: flex;
        width: 100%;
        margin-bottom: 18px;
    }

    .message-row.user-row {
        justify-content: flex-end;
    }

    .message-row.ai-row {
        justify-content: flex-start;
    }

    .message {
        max-width: min(820px, 86%);
        padding: 13px 16px;
        border-radius: 16px;
        font-size: 0.94rem;
        line-height: 1.68;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }

    .msg-user {
        color: #ffffff;
        background: #2563eb;
        border-bottom-right-radius: 5px;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .msg-ai {
        width: min(820px, 86%);
        color: #1f2937;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 5px;
    }

    .ai-label {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 10px;
        color: #475569;
        font-size: 0.76rem;
        font-weight: 750;
        letter-spacing: 0.02em;
    }

    .ai-label-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 8px;
        color: #1d4ed8;
        background: #dbeafe;
        font-size: 0.7rem;
        font-weight: 800;
    }

    .ai-text {
        overflow-wrap: anywhere;
    }

    .ai-text > :first-child {
        margin-top: 0 !important;
    }

    .ai-text > :last-child {
        margin-bottom: 0 !important;
    }

    .ai-text h1,
    .ai-text h2,
    .ai-text h3,
    .ai-text h4 {
        margin: 1.2em 0 0.5em;
        color: #0f172a;
        line-height: 1.35;
        font-weight: 750;
    }

    .ai-text h1 { font-size: 1.18rem; }
    .ai-text h2 { font-size: 1.08rem; }
    .ai-text h3,
    .ai-text h4 { font-size: 0.99rem; }

    .ai-text p {
        margin: 0 0 0.82em;
    }

    .ai-text ul,
    .ai-text ol {
        margin: 0.35em 0 0.9em;
        padding-left: 1.45rem;
    }

    .ai-text li {
        margin: 0.28em 0;
        padding-left: 0.12rem;
    }

    .ai-text li::marker {
        color: #2563eb;
        font-weight: 700;
    }

    .ai-text strong {
        color: #0f172a;
        font-weight: 750;
    }

    .ai-text em {
        color: #475569;
    }

    .ai-text blockquote {
        margin: 0.85em 0;
        padding: 10px 13px;
        border-left: 4px solid #60a5fa;
        border-radius: 0 8px 8px 0;
        background: #eff6ff;
        color: #334155;
    }

    .ai-text hr {
        margin: 1rem 0;
        border: 0;
        border-top: 1px solid #e2e8f0;
    }

    .ai-text code {
        padding: 2px 5px;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        color: #be123c;
        background: #f8fafc;
        font-family: Consolas, Monaco, monospace;
        font-size: 0.88em;
    }

    .code-block {
        margin: 0.9em 0;
        overflow: hidden;
        border: 1px solid #334155;
        border-radius: 10px;
        background: #0f172a;
    }

    .code-language {
        padding: 7px 12px;
        border-bottom: 1px solid #334155;
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .ai-text .code-block pre {
        margin: 0;
        padding: 14px;
        overflow-x: auto;
        color: #e2e8f0;
        background: transparent;
        line-height: 1.55;
    }

    .ai-text .code-block code {
        padding: 0;
        border: 0;
        color: inherit;
        background: transparent;
        font-size: 0.84rem;
    }

    .table-scroll {
        margin: 0.9em 0;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .ai-text table {
        width: 100%;
        min-width: 520px;
        border-collapse: collapse;
        table-layout: auto;
        font-size: 0.86rem;
    }

    .ai-text tr > :first-child:nth-last-child(2) {
        width: 34%;
        min-width: 190px;
    }

    .ai-text th,
    .ai-text td {
        padding: 10px 12px;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
        overflow-wrap: break-word;
        word-break: normal;
    }

    .ai-text th:last-child,
    .ai-text td:last-child {
        border-right: 0;
    }

    .ai-text tr:last-child td {
        border-bottom: 0;
    }

    .ai-text th {
        color: #0f172a;
        background: #f1f5f9;
        font-weight: 750;
    }

    .ai-text tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    .message-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }

    .btn-copy {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 5px 10px;
        border: 1px solid #dbe3ee;
        border-radius: 7px;
        color: #475569;
        background: #f8fafc;
        font-size: 0.78rem;
        font-weight: 650;
        cursor: pointer;
        transition: background-color 0.2s, border-color 0.2s;
    }

    .btn-copy:hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }

    .loading-message {
        display: flex;
        align-items: center;
        gap: 7px;
        width: fit-content;
        margin-bottom: 18px;
        padding: 10px 13px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        background: #ffffff;
        font-size: 0.84rem;
    }

    .typing-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #94a3b8;
        animation: typing-pulse 1.2s infinite ease-in-out;
    }

    .typing-dot:nth-child(2) { animation-delay: 0.15s; }
    .typing-dot:nth-child(3) { animation-delay: 0.3s; }

    @keyframes typing-pulse {
        0%, 60%, 100% { opacity: 0.35; transform: translateY(0); }
        30% { opacity: 1; transform: translateY(-2px); }
    }

    .input-wrapper {
        flex-shrink: 0;
        padding: 14px 18px 16px;
        border-top: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .chat-input-box {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        padding: 9px 9px 9px 15px;
        border: 1px solid #cbd5e1;
        border-radius: 15px;
        background: #f8fafc;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .chat-input-box:focus-within {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.16);
    }

    .chat-input-box textarea {
        flex: 1;
        min-height: 40px;
        max-height: 150px;
        padding: 9px 0;
        resize: none;
        overflow-y: auto;
        border: 0;
        outline: 0;
        color: #1f2937;
        background: transparent;
        font: inherit;
        font-size: 0.93rem;
        line-height: 1.45;
    }

    .chat-input-box textarea::placeholder {
        color: #94a3b8;
    }

    .chat-input-box button {
        min-width: 82px;
        min-height: 40px;
        padding: 8px 18px;
        border: 0;
        border-radius: 10px;
        color: #ffffff;
        background: #2563eb;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.2s, opacity 0.2s;
    }

    .chat-input-box button:hover:not(:disabled) {
        background: #1d4ed8;
    }

    .chat-input-box button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .input-hint {
        max-width: 900px;
        margin: 7px auto 0;
        color: #94a3b8;
        font-size: 0.72rem;
        text-align: center;
    }

    @media (max-width: 900px) {
        .curriculum-chat-layout {
            height: auto;
            min-height: 0;
            flex-direction: column;
        }

        .history-sidebar {
            width: auto;
            max-height: 235px;
        }

        .chat-wrapper {
            min-height: 72vh;
        }

        .message,
        .msg-ai {
            max-width: 94%;
            width: auto;
        }
    }

    @media (max-width: 560px) {
        .chat-header {
            align-items: flex-start;
            padding: 14px 16px;
        }

        .ai-status {
            display: none;
        }

        .chat-container {
            padding: 16px 12px;
        }

        .message,
        .msg-ai {
            max-width: 100%;
            width: auto;
        }

        .input-wrapper {
            padding: 10px;
        }

        .chat-input-box {
            padding-left: 12px;
        }

        .chat-input-box button {
            min-width: 66px;
            padding-inline: 12px;
        }
    }
</style>

<div class="curriculum-chat-layout">
    <aside class="history-sidebar">
        <a href="?page=chat_dashboard" class="btn-new-chat">+ Chat Baru</a>
        <p class="sidebar-title">Riwayat percakapan</p>

        <div class="history-list">
            <?php if (empty($recentSessions)): ?>
                <div class="history-empty">Belum ada percakapan tersimpan.</div>
            <?php endif; ?>

            <?php foreach ($recentSessions as $session):
                $firstMessage = (string) ($session['first_msg'] ?? 'Obrolan Baru');
                $title = htmlspecialchars(mb_strimwidth($firstMessage, 0, 34, '...'));
                $isActive = ($session['session_id'] === $currentSession) ? 'active' : '';
            ?>
                <div class="history-item <?= $isActive ?>">
                    <a
                        href="?page=chat_dashboard&session=<?= urlencode($session['session_id']) ?>"
                        class="history-link"
                        title="<?= htmlspecialchars($firstMessage) ?>"
                    ><?= $title ?></a>
                    <a
                        href="?page=chat_dashboard&action=delete&session_id=<?= urlencode($session['session_id']) ?>"
                        class="btn-delete"
                        aria-label="Hapus percakapan"
                        onclick="return confirm('Apakah Anda yakin ingin menghapus obrolan ini?');"
                    >Hapus</a>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <section class="chat-wrapper">
        <header class="chat-header">
            <div class="chat-header-copy">
                <h1>Konsultan Kurikulum AI</h1>
                <p>Membantu menyusun perangkat pembelajaran dengan jawaban yang terstruktur dan siap digunakan.</p>
            </div>
            <span class="ai-status">Siap membantu</span>
        </header>

        <div id="chatBox" class="chat-container" aria-live="polite">
            <?php foreach ($chatHistories as $chat): ?>
                <?php if ($chat['role'] === 'user'): ?>
                    <div class="message-row user-row">
                        <div class="message msg-user"><?= htmlspecialchars($chat['content']) ?></div>
                    </div>
                <?php else: ?>
                    <div class="message-row ai-row">
                        <article class="message msg-ai">
                            <div class="ai-label">
                                <span class="ai-label-badge">AI</span>
                                <span>Konsultan TeacherDesk</span>
                            </div>
                            <div class="ai-text msg-ai-history"><?= htmlspecialchars($chat['content']) ?></div>
                            <div class="message-actions">
                                <button type="button" class="btn-copy" onclick="copyText(this)">Salin jawaban</button>
                            </div>
                        </article>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="input-wrapper">
            <div class="chat-input-box">
                <textarea
                    id="chatInput"
                    rows="1"
                    maxlength="8000"
                    placeholder="Contoh: Buatkan tujuan pembelajaran IPA kelas 8 untuk materi sistem pernapasan..."
                    autocomplete="off"
                ></textarea>
                <button type="button" id="btnSend" onclick="sendMessage()">Kirim</button>
            </div>
            <div class="input-hint">Enter untuk mengirim, Shift + Enter untuk membuat baris baru.</div>
        </div>
    </section>
</div>

<script>
    const activeSessionId = <?= json_encode((string) $currentSession, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const initialConversation = <?= json_encode($initialConversation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const chatBox = document.getElementById('chatBox');
    const chatInput = document.getElementById('chatInput');
    const btnSend = document.getElementById('btnSend');
    let chatHistory = Array.isArray(initialConversation) ? initialConversation : [];

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.msg-ai-history').forEach(function (messageElement) {
            const rawText = messageElement.textContent || '';
            messageElement.innerHTML = renderMarkdown(rawText);
            messageElement.classList.remove('msg-ai-history');
        });

        autoResizeInput();
        scrollToBottom(false);
    });

    chatInput.addEventListener('input', autoResizeInput);
    chatInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    function escapeHTML(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeAiLineBreaks(value) {
        const normalized = String(value || '').replace(/\r\n?/g, '\n');

        return normalized.split('\n').map(function (line) {
            const pipeCount = (line.match(/\|/g) || []).length;
            const isTableLikeLine = /^\s*\|/.test(line) || pipeCount >= 2;
            const replacement = isTableLikeLine ? '@@TD_CELL_BREAK@@' : '\n';

            return line
                .replace(/&lt;br\s*\/?&gt;/gi, replacement)
                .replace(/<br\s*\/?>/gi, replacement);
        }).join('\n');
    }

    function renderInlineMarkdown(value) {
        const inlineCodeBlocks = [];
        let html = escapeHTML(value);

        html = html.replace(/`([^`\n]+)`/g, function (_, code) {
            const token = `@@INLINE_CODE_${inlineCodeBlocks.length}@@`;
            inlineCodeBlocks.push(`<code>${code}</code>`);
            return token;
        });

        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/__([^_]+)__/g, '<strong>$1</strong>');
        html = html.replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>');
        html = html.replace(/~~([^~]+)~~/g, '<del>$1</del>');

        inlineCodeBlocks.forEach(function (codeBlock, index) {
            html = html.replace(`@@INLINE_CODE_${index}@@`, codeBlock);
        });

        html = html.replace(/@@TD_CELL_BREAK@@/g, '<br>');

        return html;
    }

    function splitTableRow(line) {
        let normalized = line.trim();
        if (normalized.startsWith('|')) normalized = normalized.slice(1);
        if (normalized.endsWith('|')) normalized = normalized.slice(0, -1);

        return normalized.split('|').map(function (cell) {
            return cell.trim();
        });
    }

    function isTableSeparator(line) {
        const cells = splitTableRow(line);
        return cells.length > 0 && cells.every(function (cell) {
            return /^:?-{3,}:?$/.test(cell.replace(/\s+/g, ''));
        });
    }

    function isBlockStart(lines, index) {
        const line = lines[index] || '';
        const nextLine = lines[index + 1] || '';

        return /^@@CODE_BLOCK_\d+@@$/.test(line.trim())
            || /^(#{1,4})\s+/.test(line)
            || /^\s*([-*_])(?:\s*\1){2,}\s*$/.test(line)
            || /^\s*>\s?/.test(line)
            || /^\s*[-+*]\s+/.test(line)
            || /^\s*\d+[.)]\s+/.test(line)
            || (line.includes('|') && isTableSeparator(nextLine));
    }

    function renderMarkdown(markdown) {
        const codeBlocks = [];
        const normalized = normalizeAiLineBreaks(markdown).replace(/\r\n?/g, '\n');

        const withoutCodeBlocks = normalized.replace(/```([^\n`]*)\n?([\s\S]*?)```/g, function (_, language, code) {
            const safeLanguage = escapeHTML(language.trim() || 'kode');
            const safeCode = escapeHTML(code.replace(/^\n/, '').replace(/\n$/, ''));
            const token = `@@CODE_BLOCK_${codeBlocks.length}@@`;

            codeBlocks.push(
                `<div class="code-block">`
                + `<div class="code-language">${safeLanguage}</div>`
                + `<pre><code>${safeCode}</code></pre>`
                + `</div>`
            );

            return `\n${token}\n`;
        });

        const lines = withoutCodeBlocks.split('\n');
        const output = [];
        let index = 0;

        while (index < lines.length) {
            const line = lines[index];
            const trimmed = line.trim();

            if (trimmed === '') {
                index += 1;
                continue;
            }

            const codeMatch = trimmed.match(/^@@CODE_BLOCK_(\d+)@@$/);
            if (codeMatch) {
                output.push(codeBlocks[Number(codeMatch[1])] || '');
                index += 1;
                continue;
            }

            const headingMatch = line.match(/^(#{1,4})\s+(.+)$/);
            if (headingMatch) {
                const level = Math.min(4, headingMatch[1].length + 1);
                output.push(`<h${level}>${renderInlineMarkdown(headingMatch[2].trim())}</h${level}>`);
                index += 1;
                continue;
            }

            if (/^\s*([-*_])(?:\s*\1){2,}\s*$/.test(line)) {
                output.push('<hr>');
                index += 1;
                continue;
            }

            if (/^\s*>\s?/.test(line)) {
                const quoteLines = [];
                while (index < lines.length && /^\s*>\s?/.test(lines[index])) {
                    quoteLines.push(lines[index].replace(/^\s*>\s?/, '').trim());
                    index += 1;
                }
                output.push(`<blockquote>${quoteLines.map(renderInlineMarkdown).join('<br>')}</blockquote>`);
                continue;
            }

            if (line.includes('|') && isTableSeparator(lines[index + 1] || '')) {
                const headers = splitTableRow(line);
                index += 2;
                const rows = [];

                while (index < lines.length) {
                    const rowLine = lines[index];
                    const rowTrimmed = rowLine.trim();

                    if (rowTrimmed === '') break;

                    // Memperbaiki riwayat lama yang sempat terpotong karena <br>
                    // di dalam sel tabel diubah menjadi baris baru oleh versi sebelumnya.
                    if (!rowLine.includes('|')) {
                        if (rows.length === 0 || isBlockStart(lines, index)) break;

                        const previousRow = rows[rows.length - 1];
                        const targetCell = Math.min(headers.length - 1, Math.max(0, previousRow.length - 1));
                        previousRow[targetCell] = [previousRow[targetCell] || '', rowTrimmed]
                            .filter(Boolean)
                            .join('@@TD_CELL_BREAK@@');
                        index += 1;
                        continue;
                    }

                    const parsedRow = splitTableRow(rowLine);
                    const startsWithPipe = /^\s*\|/.test(rowLine);

                    // Baris lanjutan hasil normalisasi lama biasanya tidak diawali "|"
                    // dan hanya memiliki satu sel. Gabungkan kembali ke sel terakhir.
                    if (!startsWithPipe && parsedRow.length < headers.length && rows.length > 0) {
                        const previousRow = rows[rows.length - 1];
                        const targetCell = Math.min(headers.length - 1, Math.max(0, previousRow.length - 1));
                        previousRow[targetCell] = [previousRow[targetCell] || '', parsedRow.join(' | ')]
                            .filter(Boolean)
                            .join('@@TD_CELL_BREAK@@');
                        index += 1;
                        continue;
                    }

                    rows.push(parsedRow.slice(0, headers.length));
                    index += 1;
                }

                let tableHtml = '<div class="table-scroll"><table><thead><tr>';
                headers.forEach(function (header) {
                    tableHtml += `<th>${renderInlineMarkdown(header)}</th>`;
                });
                tableHtml += '</tr></thead><tbody>';

                rows.forEach(function (row) {
                    tableHtml += '<tr>';
                    headers.forEach(function (_, cellIndex) {
                        tableHtml += `<td>${renderInlineMarkdown(row[cellIndex] || '')}</td>`;
                    });
                    tableHtml += '</tr>';
                });

                tableHtml += '</tbody></table></div>';
                output.push(tableHtml);
                continue;
            }

            const unorderedMatch = line.match(/^\s*[-+*]\s+(.+)$/);
            const orderedMatch = line.match(/^\s*\d+[.)]\s+(.+)$/);
            if (unorderedMatch || orderedMatch) {
                const ordered = Boolean(orderedMatch);
                const tag = ordered ? 'ol' : 'ul';
                const items = [];
                const itemRegex = ordered ? /^\s*\d+[.)]\s+(.+)$/ : /^\s*[-+*]\s+(.+)$/;

                while (index < lines.length) {
                    const itemMatch = lines[index].match(itemRegex);
                    if (!itemMatch) break;
                    items.push(itemMatch[1].trim());
                    index += 1;
                }

                output.push(`<${tag}>${items.map(function (item) {
                    return `<li>${renderInlineMarkdown(item)}</li>`;
                }).join('')}</${tag}>`);
                continue;
            }

            const paragraphLines = [trimmed];
            index += 1;

            while (
                index < lines.length
                && lines[index].trim() !== ''
                && !isBlockStart(lines, index)
            ) {
                paragraphLines.push(lines[index].trim());
                index += 1;
            }

            output.push(`<p>${paragraphLines.map(renderInlineMarkdown).join('<br>')}</p>`);
        }

        return output.join('');
    }

    async function copyText(buttonElement) {
        const messageElement = buttonElement.closest('.msg-ai');
        const textElement = messageElement ? messageElement.querySelector('.ai-text') : null;
        const text = textElement ? textElement.innerText : '';

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                const temporaryTextarea = document.createElement('textarea');
                temporaryTextarea.value = text;
                temporaryTextarea.style.position = 'fixed';
                temporaryTextarea.style.opacity = '0';
                document.body.appendChild(temporaryTextarea);
                temporaryTextarea.focus();
                temporaryTextarea.select();
                document.execCommand('copy');
                temporaryTextarea.remove();
            }

            const originalText = buttonElement.textContent;
            buttonElement.textContent = 'Tersalin';
            setTimeout(function () {
                buttonElement.textContent = originalText;
            }, 1600);
        } catch (_) {
            alert('Jawaban tidak dapat disalin.');
        }
    }

    function autoResizeInput() {
        chatInput.style.height = 'auto';
        chatInput.style.height = `${Math.min(chatInput.scrollHeight, 150)}px`;
    }

    function setComposerDisabled(disabled) {
        chatInput.disabled = disabled;
        btnSend.disabled = disabled;
        btnSend.textContent = disabled ? 'Menunggu' : 'Kirim';
    }

    function appendMessage(sender, text, renderAsMarkdown) {
        const row = document.createElement('div');
        row.className = `message-row ${sender === 'user' ? 'user-row' : 'ai-row'}`;

        if (sender === 'user') {
            const message = document.createElement('div');
            message.className = 'message msg-user';
            message.textContent = text;
            row.appendChild(message);
        } else {
            const article = document.createElement('article');
            article.className = 'message msg-ai';

            const label = document.createElement('div');
            label.className = 'ai-label';
            label.innerHTML = '<span class="ai-label-badge">AI</span><span>Konsultan TeacherDesk</span>';

            const textElement = document.createElement('div');
            textElement.className = 'ai-text';
            textElement.innerHTML = renderAsMarkdown ? renderMarkdown(text) : escapeHTML(text);

            const actions = document.createElement('div');
            actions.className = 'message-actions';

            const copyButton = document.createElement('button');
            copyButton.type = 'button';
            copyButton.className = 'btn-copy';
            copyButton.textContent = 'Salin jawaban';
            copyButton.addEventListener('click', function () {
                copyText(copyButton);
            });

            actions.appendChild(copyButton);
            article.appendChild(label);
            article.appendChild(textElement);
            article.appendChild(actions);
            row.appendChild(article);
        }

        chatBox.appendChild(row);
        scrollToBottom();
    }

    function addLoadingMessage() {
        const loadingId = `loading-${Date.now()}`;
        const loading = document.createElement('div');
        loading.id = loadingId;
        loading.className = 'loading-message';
        loading.innerHTML = '<span>AI sedang menyusun jawaban</span><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>';
        chatBox.appendChild(loading);
        scrollToBottom();
        return loadingId;
    }

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message || btnSend.disabled) return;

        appendMessage('user', message, false);
        chatHistory.push({ role: 'user', content: message });

        chatInput.value = '';
        autoResizeInput();
        setComposerDisabled(true);
        const loadingId = addLoadingMessage();

        try {
            const response = await fetch('api_chat.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    session_id: activeSessionId,
                    messages: chatHistory
                })
            });

            const responseText = await response.text();
            let data;

            try {
                data = JSON.parse(responseText);
            } catch (_) {
                const preview = responseText.trim().slice(0, 400) || 'Respons server kosong.';
                throw new Error(`Server mengirim respons yang tidak valid.\n\n${preview}`);
            }

            document.getElementById(loadingId)?.remove();

            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'Konsultan AI gagal merespons.');
            }

            appendMessage('assistant', data.reply, true);
            chatHistory.push({ role: 'assistant', content: data.reply });
        } catch (error) {
            document.getElementById(loadingId)?.remove();
            alert(error.message || 'Gagal menghubungi server.');
        } finally {
            setComposerDisabled(false);
            chatInput.focus();
            scrollToBottom();

            if (chatBox.querySelectorAll('.message-row').length <= 3) {
                setTimeout(function () {
                    window.location.href = `?page=chat_dashboard&session=${encodeURIComponent(activeSessionId)}`;
                }, 500);
            }
        }
    }

    function scrollToBottom(smooth = true) {
        chatBox.scrollTo({
            top: chatBox.scrollHeight,
            behavior: smooth ? 'smooth' : 'auto'
        });
    }
</script>

<?php
$subjects = $db->query(
    "SELECT id, name, grade_level
     FROM subjects
     WHERE status='active'
     ORDER BY name, grade_level"
)->fetchAll();
?>

<style>
    .ai-generator-wrap { max-width: 1080px; margin: 0 auto; }
    .ai-generator-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .ai-result-box { display: none; margin-top: 20px; }
    .ai-question-card { border: 1px solid #dfe3ea; border-radius: 12px; padding: 18px; margin-bottom: 16px; background: #fff; }
    .ai-question-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .ai-question-number { font-weight: 700; }
    .ai-option-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .ai-meta-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 220px)); gap: 12px; margin-top: 12px; }
    .ai-loading { display: none; text-align: center; padding: 26px 0; }
    .ai-result-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .ai-note { padding: 12px 14px; border-radius: 10px; background: #f4f7ff; color: #334155; font-size: .92rem; margin-bottom: 18px; }
    .ai-warning { padding: 12px 14px; border-radius: 10px; background: #fff8e6; color: #7c4a03; font-size: .92rem; margin-bottom: 18px; }
    .ai-empty-subject { padding: 16px; border: 1px solid #f0c36d; background: #fff8e6; border-radius: 10px; }
    .ai-summary { display: none; margin: 16px 0; padding: 14px; border: 1px solid #dce7f8; border-radius: 10px; background: #f8fbff; }
    .ai-summary-items { display: flex; gap: 18px; flex-wrap: wrap; font-size: .9rem; }
    .ai-check-row { display: flex; align-items: center; gap: 9px; min-height: 42px; }
    .ai-progress { width: 100%; height: 8px; overflow: hidden; border-radius: 999px; background: #e8edf5; margin: 12px auto 0; max-width: 360px; }
    .ai-progress > span { display: block; width: 45%; height: 100%; background: currentColor; animation: aiProgress 1.2s ease-in-out infinite alternate; }
    @keyframes aiProgress { from { transform: translateX(-20%); } to { transform: translateX(145%); } }
    @media (max-width: 860px) {
        .ai-generator-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 620px) {
        .ai-generator-grid, .ai-option-grid, .ai-meta-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="ai-generator-wrap">
    <div class="section-title">
        <div>
            <h2>Generator Soal AI v1.5</h2>
            <p>Buat soal dari PDF, tinjau hasil AI, lalu simpan ke Bank Soal sebagai draf.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-secondary" href="<?= url('ai_generation_history') ?>">Riwayat Generate</a>
            <a class="btn btn-secondary" href="<?= url('questions') ?>">Bank Soal</a>
        </div>
    </div>

    <?php if (!$subjects): ?>
        <div class="ai-empty-subject">
            Belum ada mata pelajaran aktif. Tambahkan mata pelajaran terlebih dahulu sebelum menggunakan generator AI.
        </div>
    <?php else: ?>
        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Pengaturan Generator</h2>
                    <p>Versi 1.5 membaca bagian PDF secara merata, bukan hanya paragraf awalnya yang kebetulan sedang sial.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="ai-note">
                    PDF maksimal 10 MB dan 150 halaman. Dokumen hasil scan harus diubah menjadi PDF berbasis teks terlebih dahulu.
                    Soal yang disimpan tetap berstatus <strong>draf</strong> agar dapat diperiksa sebelum digunakan.
                </div>

                <form method="post" action="api_save_ai_questions.php" id="saveQuestionsForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="source_file_name" id="sourceFileName">

                    <div class="ai-generator-grid">
                        <div class="form-group">
                            <label for="subjectId">Mata pelajaran *</label>
                            <select class="form-control" name="subject_id" id="subjectId" required>
                                <option value="">Pilih mata pelajaran</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= (int) $subject['id'] ?>">
                                        <?= e($subject['name'] . ' • ' . $subject['grade_level']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="pdfFile">File materi PDF *</label>
                            <input class="form-control" type="file" id="pdfFile" accept="application/pdf,.pdf" required>
                        </div>

                        <div class="form-group">
                            <label for="questionCountInput">Jumlah soal</label>
                            <select class="form-control" name="question_count" id="questionCountInput">
                                <?php foreach ([5, 10, 15, 20, 25] as $count): ?>
                                    <option value="<?= $count ?>" <?= $count === 10 ? 'selected' : '' ?>><?= $count ?> soal</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="difficulty">Tingkat kesulitan</label>
                            <select class="form-control" name="difficulty" id="difficulty">
                                <option value="mudah">Mudah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="sulit">Sulit</option>
                                <option value="campuran">Campuran</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cognitiveLevel">Level kognitif Bloom</label>
                            <select class="form-control" name="cognitive_level" id="cognitiveLevel">
                                <option value="C1">C1 • Mengingat</option>
                                <option value="C2" selected>C2 • Memahami</option>
                                <option value="C3">C3 • Menerapkan</option>
                                <option value="C4">C4 • Menganalisis</option>
                                <option value="C5">C5 • Mengevaluasi</option>
                                <option value="C6">C6 • Mencipta</option>
                                <option value="campuran">Campuran C1–C6</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Pembahasan</label>
                            <label class="ai-check-row">
                                <input type="checkbox" name="include_explanation" id="includeExplanation" value="1" checked>
                                Sertakan pembahasan otomatis
                            </label>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top:18px">
                        <button class="btn btn-primary" type="button" id="btnGenerate">Buat Soal Sekarang</button>
                    </div>

                    <div id="loadingIndicator" class="ai-loading">
                        <strong>AI sedang membaca materi dan menyusun soal...</strong>
                        <div class="muted text-sm" style="margin-top:6px">PDF panjang membutuhkan proses lebih berat. Jangan menekan tombol berkali-kali seperti lift yang tidak segera datang.</div>
                        <div class="ai-progress"><span></span></div>
                    </div>

                    <div id="summaryArea" class="ai-summary">
                        <div class="ai-summary-items">
                            <span><strong id="generatedSummary">0</strong> soal valid</span>
                            <span><strong id="pageSummary">0</strong> halaman</span>
                            <span><strong id="chunkSummary">0</strong> bagian dokumen dipakai</span>
                            <span>Riwayat #<strong id="historySummary">-</strong></span>
                        </div>
                    </div>

                    <div id="resultArea" class="ai-result-box">
                        <div class="ai-result-actions">
                            <div>
                                <h3 style="margin:0">Hasil Generate</h3>
                                <div class="muted text-sm"><span id="questionCount">0</span> soal siap diperiksa dan diedit</div>
                            </div>
                            <label class="text-sm">
                                <input type="checkbox" id="selectAllQuestions" checked>
                                Pilih semua soal
                            </label>
                        </div>

                        <div class="ai-warning">
                            AI dapat menghasilkan kekeliruan. Periksa materi, opsi, kunci, level Bloom, dan pembahasan sebelum menyimpan.
                        </div>

                        <div id="questionsContainer"></div>

                        <div class="form-actions">
                            <button class="btn btn-primary" type="submit" id="btnSaveQuestions">
                                Simpan Soal Terpilih ke Bank Soal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php if ($subjects): ?>
<script>
(() => {
    const fileInput = document.getElementById('pdfFile');
    const subjectInput = document.getElementById('subjectId');
    const countInput = document.getElementById('questionCountInput');
    const difficultyInput = document.getElementById('difficulty');
    const cognitiveInput = document.getElementById('cognitiveLevel');
    const explanationInput = document.getElementById('includeExplanation');
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    const generateButton = document.getElementById('btnGenerate');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultArea = document.getElementById('resultArea');
    const summaryArea = document.getElementById('summaryArea');
    const questionsContainer = document.getElementById('questionsContainer');
    const questionCount = document.getElementById('questionCount');
    const selectAll = document.getElementById('selectAllQuestions');
    const sourceFileName = document.getElementById('sourceFileName');
    const saveForm = document.getElementById('saveQuestionsForm');

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const normalizeQuestion = (item) => {
        const choices = item && typeof item.pilihan === 'object' ? item.pilihan : {};
        const answer = String(item?.kunci_jawaban ?? 'A').toUpperCase();
        const difficulty = ['mudah', 'sedang', 'sulit'].includes(item?.tingkat_kesulitan)
            ? item.tingkat_kesulitan
            : 'sedang';
        const cognitive = ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'].includes(item?.level_kognitif)
            ? item.level_kognitif
            : 'C2';

        return {
            question_text: String(item?.pertanyaan ?? '').trim(),
            option_a: String(choices.A ?? '').trim(),
            option_b: String(choices.B ?? '').trim(),
            option_c: String(choices.C ?? '').trim(),
            option_d: String(choices.D ?? '').trim(),
            correct_option: ['A', 'B', 'C', 'D'].includes(answer) ? answer : 'A',
            explanation: String(item?.pembahasan ?? '').trim(),
            difficulty,
            cognitive_level: cognitive
        };
    };

    const answerOptions = (selectedValue) => ['A', 'B', 'C', 'D']
        .map(letter => `<option value="${letter}" ${letter === selectedValue ? 'selected' : ''}>${letter}</option>`)
        .join('');

    const difficultyOptions = (selectedValue) => ['mudah', 'sedang', 'sulit']
        .map(value => `<option value="${value}" ${value === selectedValue ? 'selected' : ''}>${value.charAt(0).toUpperCase() + value.slice(1)}</option>`)
        .join('');

    const cognitiveOptions = (selectedValue) => ['C1', 'C2', 'C3', 'C4', 'C5', 'C6']
        .map(value => `<option value="${value}" ${value === selectedValue ? 'selected' : ''}>${value}</option>`)
        .join('');

    const renderQuestions = (items) => {
        questionsContainer.innerHTML = '';

        items.map(normalizeQuestion).forEach((question, index) => {
            const card = document.createElement('div');
            card.className = 'ai-question-card';
            card.innerHTML = `
                <div class="ai-question-head">
                    <span class="ai-question-number">Soal ${index + 1}</span>
                    <label class="text-sm">
                        <input class="ai-question-select" type="checkbox" name="save[${index}]" value="1" checked>
                        Simpan soal ini
                    </label>
                </div>

                <div class="form-group">
                    <label>Pertanyaan *</label>
                    <textarea class="form-control" name="items[${index}][question_text]" rows="3" required>${escapeHtml(question.question_text)}</textarea>
                </div>

                <div class="ai-option-grid" style="margin-top:12px">
                    <div class="form-group">
                        <label>Pilihan A *</label>
                        <input class="form-control" name="items[${index}][option_a]" value="${escapeHtml(question.option_a)}" required>
                    </div>
                    <div class="form-group">
                        <label>Pilihan B *</label>
                        <input class="form-control" name="items[${index}][option_b]" value="${escapeHtml(question.option_b)}" required>
                    </div>
                    <div class="form-group">
                        <label>Pilihan C *</label>
                        <input class="form-control" name="items[${index}][option_c]" value="${escapeHtml(question.option_c)}" required>
                    </div>
                    <div class="form-group">
                        <label>Pilihan D *</label>
                        <input class="form-control" name="items[${index}][option_d]" value="${escapeHtml(question.option_d)}" required>
                    </div>
                </div>

                <div class="ai-meta-grid">
                    <div class="form-group">
                        <label>Kunci jawaban *</label>
                        <select class="form-control" name="items[${index}][correct_option]" required>
                            ${answerOptions(question.correct_option)}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tingkat kesulitan</label>
                        <select class="form-control" name="items[${index}][difficulty]">
                            ${difficultyOptions(question.difficulty)}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Level Bloom</label>
                        <select class="form-control" name="items[${index}][cognitive_level]">
                            ${cognitiveOptions(question.cognitive_level)}
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top:12px">
                    <label>Pembahasan</label>
                    <textarea class="form-control" name="items[${index}][explanation]" rows="3">${escapeHtml(question.explanation)}</textarea>
                </div>
            `;
            questionsContainer.appendChild(card);
        });

        questionCount.textContent = String(items.length);
        selectAll.checked = true;
        resultArea.style.display = 'block';
    };

    const validatePdf = (file) => {
        if (!file) return 'Pilih file PDF terlebih dahulu.';
        if (file.size > 10 * 1024 * 1024) return 'Ukuran PDF maksimal 10 MB.';
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) return 'Format file harus PDF.';
        return '';
    };

    generateButton.addEventListener('click', async () => {
        if (!subjectInput.value) {
            alert('Pilih mata pelajaran terlebih dahulu.');
            subjectInput.focus();
            return;
        }

        const file = fileInput.files[0];
        const fileError = validatePdf(file);
        if (fileError) {
            alert(fileError);
            fileInput.focus();
            return;
        }

        const formData = new FormData();
        formData.append('document', file);
        formData.append('csrf_token', csrfInput.value);
        formData.append('subject_id', subjectInput.value);
        formData.append('question_count', countInput.value);
        formData.append('difficulty', difficultyInput.value);
        formData.append('cognitive_level', cognitiveInput.value);
        formData.append('include_explanation', explanationInput.checked ? '1' : '0');

        generateButton.disabled = true;
        loadingIndicator.style.display = 'block';
        resultArea.style.display = 'none';
        summaryArea.style.display = 'none';
        questionsContainer.innerHTML = '';

        try {
            const response = await fetch('api_generate_mcq.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const responseText = await response.text();

            let result;

            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Respons mentah server:', responseText);

                const preview = responseText
                    ? responseText.substring(0, 500)
                    : 'Respons server kosong.';

                throw new Error(
                    `Server mengirim respons yang tidak valid:\n\n${preview}`
                );
            }

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Soal gagal dibuat.');
            }

            if (!Array.isArray(result.data) || result.data.length === 0) {
                throw new Error('AI tidak menghasilkan soal yang dapat digunakan.');
            }

            sourceFileName.value = file.name;
            renderQuestions(result.data);

            const meta = result.meta || {};
            document.getElementById('generatedSummary').textContent = String(meta.generated_count ?? result.data.length);
            document.getElementById('pageSummary').textContent = String(meta.page_count ?? '-');
            document.getElementById('chunkSummary').textContent = String(meta.chunks_used ?? '-');
            document.getElementById('historySummary').textContent = String(meta.history_id ?? '-');
            summaryArea.style.display = 'block';

            if (result.message) {
                console.info(result.message);
            }
        } catch (error) {
            alert(error.message || 'Terjadi kesalahan jaringan atau server API.');
        } finally {
            generateButton.disabled = false;
            loadingIndicator.style.display = 'none';
        }
    });

    selectAll.addEventListener('change', () => {
        document.querySelectorAll('.ai-question-select').forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    });

    questionsContainer.addEventListener('change', (event) => {
        if (!event.target.classList.contains('ai-question-select')) return;
        const all = [...document.querySelectorAll('.ai-question-select')];
        selectAll.checked = all.length > 0 && all.every(checkbox => checkbox.checked);
    });

    saveForm.addEventListener('submit', (event) => {
        const selected = document.querySelectorAll('.ai-question-select:checked');
        if (selected.length === 0) {
            event.preventDefault();
            alert('Pilih minimal satu soal untuk disimpan.');
            return;
        }

        document.getElementById('btnSaveQuestions').disabled = true;
    });
})();
</script>
<?php endif; ?>

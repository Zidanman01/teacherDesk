<style>
    .result-box { display: none; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; padding: 1.5rem; margin-top: 1.5rem; }
    .question-card { background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
    .correct-answer { color: #198754; font-weight: bold; }
</style>

<div class="card shadow-sm" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-success">📄 AI Generator Soal (MCQ)</h5>
        <small class="text-muted">Unggah materi PDF untuk diubah menjadi soal pilihan ganda.</small>
    </div>
    
    <div class="card-body">
        <div class="mb-4">
            <label for="pdfFile" class="form-label fw-bold">Pilih File Materi (PDF)</label>
            <input class="form-control mb-3" type="file" id="pdfFile" accept="application/pdf" style="padding: 10px;">
            <button class="btn btn-success px-4" id="btnGenerate" onclick="generateSoal()">Buat Soal Sekarang</button>
        </div>

        <div id="loadingIndicator" class="text-center text-secondary my-4" style="display: none;">
            <i>AI sedang membuat soal... Mohon tunggu (proses ini memakan waktu beberapa detik).</i>
        </div>

        <div id="resultArea" class="result-box">
            <h6 class="fw-bold mb-3">Hasil Generate Soal:</h6>
            <div id="questionsContainer"></div>
        </div>
    </div>
</div>

<script>
    async function generateSoal() {
        const fileInput = document.getElementById('pdfFile');
        const btnGenerate = document.getElementById('btnGenerate');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const resultArea = document.getElementById('resultArea');
        const questionsContainer = document.getElementById('questionsContainer');

        if (fileInput.files.length === 0) return alert("Pilih file PDF terlebih dahulu!");

        btnGenerate.disabled = true;
        resultArea.style.display = 'none';
        loadingIndicator.style.display = 'block';
        questionsContainer.innerHTML = '';

        const formData = new FormData();
        formData.append('document', fileInput.files[0]);

        try {
            // Karena dipanggil lewat index.php, path ke API tetap berada di root
            const response = await fetch('api_generate_mcq.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success') {
                displayQuestions(result.data);
                resultArea.style.display = 'block';
            } else {
                let errorMsg = 'Gagal memproses. Pesan dari sistem: ' + result.message;
                if (result.raw_response) {
                    errorMsg += '\n\nBOCORAN RESPON AI:\n' + result.raw_response;
                }
                alert(errorMsg);
            }
        } catch (error) {
            alert('Kesalahan jaringan atau server API.');
        } finally {
            btnGenerate.disabled = false;
            loadingIndicator.style.display = 'none';
            fileInput.value = ''; 
        }
    }

    function displayQuestions(soalArray) {
        const container = document.getElementById('questionsContainer');
        soalArray.forEach((soal, index) => {
            const html = `
                <div class="question-card">
                    <p class="fw-bold mb-2">${index + 1}. ${soal.pertanyaan}</p>
                    <ul class="list-unstyled mb-2 ms-3">
                        <li>A. ${soal.pilihan.A}</li>
                        <li>B. ${soal.pilihan.B}</li>
                        <li>C. ${soal.pilihan.C}</li>
                        <li>D. ${soal.pilihan.D}</li>
                    </ul>
                    <div class="correct-answer">Kunci Jawaban: ${soal.kunci_jawaban}</div>
                </div>
            `;
            container.innerHTML += html;
        });
    }
</script>
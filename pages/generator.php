<?php
$materials=$db->query("SELECT m.id,m.subject_id,m.title,m.chapter,s.name subject_name,CHAR_LENGTH(m.content) content_length FROM materials m JOIN subjects s ON s.id=m.subject_id ORDER BY s.name,m.title")->fetchAll();
$selectedMaterial=(int)($_GET['material_id']??0);$bundle=$_SESSION['generated_questions']??null;
?>
<div class="grid grid-3">
    <section class="card">
        <div class="card-header"><div><h2>Pengaturan generator</h2><p>Membuat soal cloze dari istilah dalam materi.</p></div></div>
        <div class="card-body">
            <div class="generator-note">Generator ini bekerja sepenuhnya lokal tanpa AI daring. Sistem menghapus satu istilah dari kalimat materi lalu memilih tiga istilah lain sebagai pengecoh. Hasil wajib ditinjau sebelum digunakan.</div>
            <form method="post" class="mt-3"><?= csrf_field() ?><input type="hidden" name="action" value="generate_questions">
                <div class="form-group"><label>Materi sumber *</label><select class="form-control" name="material_id" required><option value="">Pilih materi</option><?php foreach($materials as $item): ?><option value="<?= $item['id'] ?>" <?= selected($selectedMaterial,$item['id']) ?>><?= e($item['subject_name'].' • '.$item['title']) ?> (<?= (int)$item['content_length'] ?> karakter)</option><?php endforeach; ?></select></div>
                <div class="form-group mt-2"><label>Jumlah soal</label><input class="form-control" type="number" name="count" min="1" max="10" value="5"><span class="help-text">Maksimal 10 soal per proses.</span></div>
                <div class="form-group mt-2"><label>Kesulitan awal</label><select class="form-control" name="difficulty"><option value="mudah">Mudah</option><option value="sedang" selected>Sedang</option><option value="sulit">Sulit</option></select></div>
                <div class="form-group mt-2"><label>Level kognitif awal</label><select class="form-control" name="cognitive_level"><option>C1</option><option selected>C2</option><option>C3</option><option>C4</option></select></div>
                <button class="btn btn-primary w-full mt-3" type="submit"><?= icon('sparkles',17) ?> Buat draf soal</button>
            </form>
        </div>
    </section>
    <section class="card span-2">
        <div class="card-header"><div><h2>Draf hasil generator</h2><p>Edit semua bagian sebelum menyimpan ke bank soal.</p></div><?php if($bundle): ?><span class="badge badge-warning"><?= count($bundle['items']) ?> draf</span><?php endif; ?></div>
        <div class="card-body">
            <?php if($bundle): ?>
                <div class="alert alert-info"><span>Sumber: <strong><?= e($bundle['material']['title']) ?></strong>. Semua soal akan disimpan dengan status draf.</span></div>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_generated_questions">
                    <?php foreach($bundle['items'] as $index=>$item): ?><article class="question-card"><div class="question-head"><div><span class="question-number">Soal <?= $index+1 ?></span></div><label class="text-sm"><input type="checkbox" name="save[<?= $index ?>]" value="1" checked> Simpan</label></div>
                        <div class="form-group"><label>Pertanyaan</label><textarea class="form-control" name="items[<?= $index ?>][question_text]" required><?= e($item['question_text']) ?></textarea></div>
                        <div class="option-grid mt-2"><?php foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $key=>$letter): ?><div class="option-row"><span class="option-letter"><?= $letter ?></span><input class="form-control" name="items[<?= $index ?>][option_<?= $key ?>]" required value="<?= e($item['option_'.$key]) ?>"></div><?php endforeach; ?></div>
                        <div class="form-grid mt-2"><div class="form-group"><label>Kunci</label><select class="form-control" name="items[<?= $index ?>][correct_option]"><?php foreach(['A','B','C','D'] as $letter): ?><option <?= selected($item['correct_option'],$letter) ?>><?= $letter ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Kesulitan</label><select class="form-control" name="items[<?= $index ?>][difficulty]"><?php foreach(['mudah','sedang','sulit'] as $level): ?><option <?= selected($item['difficulty'],$level) ?>><?= ucfirst($level) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Level kognitif</label><select class="form-control" name="items[<?= $index ?>][cognitive_level]"><?php foreach(['C1','C2','C3','C4','C5','C6'] as $level): ?><option <?= selected($item['cognitive_level'],$level) ?>><?= $level ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Pembahasan</label><input class="form-control" name="items[<?= $index ?>][explanation]" value="<?= e($item['explanation']) ?>"></div></div>
                    </article><?php endforeach; ?>
                    <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan soal yang dipilih</button></div>
                </form>
            <?php else: ?><div class="empty-state"><div class="empty-icon"><?= icon('sparkles',25) ?></div><h3>Belum ada draf</h3><p>Pilih materi yang cukup panjang. Generator membutuhkan beberapa kalimat dan minimal empat istilah berbeda.</p></div><?php endif; ?>
        </div>
    </section>
</div>

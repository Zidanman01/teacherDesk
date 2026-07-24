<?php
declare(strict_types=1);

function handle_action(PDO $db): void
{
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'save_subject':
                save_subject($db);
                break;
            case 'delete_subject':
                delete_record($db, 'subjects', 'subjects');
                break;
            case 'save_class':
                save_class($db);
                break;
            case 'delete_class':
                delete_record($db, 'classes', 'classes');
                break;
            case 'save_schedule':
                save_schedule($db);
                break;
            case 'delete_schedule':
                delete_record($db, 'schedules', 'schedules');
                break;
            case 'save_material':
                save_material($db);
                break;
            case 'delete_material':
                delete_material($db);
                break;
            case 'save_journal':
                save_journal($db);
                break;
            case 'delete_journal':
                delete_record($db, 'teaching_journals', 'journals');
                break;
            case 'save_question':
                save_question($db);
                break;
            case 'delete_question':
                delete_record($db, 'questions', 'questions');
                break;
            case 'generate_questions':
                generate_questions($db);
                break;
            case 'save_generated_questions':
                save_generated_questions($db);
                break;
            case 'export_backup':
                BackupService::export($db);
                break;
            case 'restore_backup':
                restore_backup($db);
                break;
            case 'save_settings':
                save_settings($db);
                break;
            default:
                flash('danger', 'Aksi tidak dikenali.');
                redirect(url('dashboard'));
        }
    } catch (PDOException $e) {
        $message = str_contains($e->getMessage(), 'foreign key constraint')
            ? 'Data tidak dapat dihapus karena masih digunakan oleh modul lain.'
            : 'Operasi database gagal. Periksa data yang dimasukkan.';
        flash('danger', $message);
        redirect($_SERVER['HTTP_REFERER'] ?? url('dashboard'));
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect($_SERVER['HTTP_REFERER'] ?? url('dashboard'));
    }
}

function require_fields(array $fields): array
{
    $values = [];
    foreach ($fields as $field => $label) {
        $value = trim((string) ($_POST[$field] ?? ''));
        if ($value === '') {
            throw new InvalidArgumentException("{$label} wajib diisi.");
        }
        $values[$field] = $value;
    }
    return $values;
}

function save_subject(PDO $db): never
{
    $required = require_fields(['name' => 'Nama mata pelajaran', 'grade_level' => 'Jenjang atau kelas', 'academic_year' => 'Tahun ajaran']);
    $id = (int) ($_POST['id'] ?? 0);
    $params = [
        $required['name'], $required['grade_level'], trim((string) ($_POST['semester'] ?? 'Ganjil')),
        $required['academic_year'], trim((string) ($_POST['curriculum'] ?? 'Kurikulum Merdeka')),
        trim((string) ($_POST['description'] ?? '')), trim((string) ($_POST['learning_outcomes'] ?? '')),
        in_array($_POST['status'] ?? '', ['active','archived'], true) ? $_POST['status'] : 'active',
    ];
    if ($id > 0) {
        $params[] = $id;
        $stmt = $db->prepare('UPDATE subjects SET name=?,grade_level=?,semester=?,academic_year=?,curriculum=?,description=?,learning_outcomes=?,status=? WHERE id=?');
        $stmt->execute($params);
        flash('success', 'Mata pelajaran berhasil diperbarui.');
    } else {
        $stmt = $db->prepare('INSERT INTO subjects (name,grade_level,semester,academic_year,curriculum,description,learning_outcomes,status) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute($params);
        flash('success', 'Mata pelajaran berhasil ditambahkan.');
    }
    redirect(url('subjects'));
}

function save_class(PDO $db): never
{
    $required = require_fields(['name' => 'Nama kelas', 'grade_level' => 'Jenjang atau tingkat']);
    $id = (int) ($_POST['id'] ?? 0);
    $params = [
        $required['name'], $required['grade_level'], trim((string) ($_POST['institution'] ?? '')),
        trim((string) ($_POST['room'] ?? '')), max(0, (int) ($_POST['student_count'] ?? 0)),
        trim((string) ($_POST['notes'] ?? '')), in_array($_POST['status'] ?? '', ['active','archived'], true) ? $_POST['status'] : 'active',
    ];
    if ($id > 0) {
        $params[] = $id;
        $db->prepare('UPDATE classes SET name=?,grade_level=?,institution=?,room=?,student_count=?,notes=?,status=? WHERE id=?')->execute($params);
        flash('success', 'Kelas berhasil diperbarui.');
    } else {
        $db->prepare('INSERT INTO classes (name,grade_level,institution,room,student_count,notes,status) VALUES (?,?,?,?,?,?,?)')->execute($params);
        flash('success', 'Kelas berhasil ditambahkan.');
    }
    redirect(url('classes'));
}

function save_schedule(PDO $db): never
{
    $required = require_fields(['subject_id' => 'Mata pelajaran', 'class_id' => 'Kelas', 'schedule_date' => 'Tanggal', 'start_time' => 'Jam mulai', 'end_time' => 'Jam selesai']);
    if ($required['end_time'] <= $required['start_time']) {
        throw new InvalidArgumentException('Jam selesai harus lebih besar daripada jam mulai.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $conflict = $db->prepare('SELECT COUNT(*) FROM schedules WHERE schedule_date=? AND id<>? AND start_time < ? AND end_time > ? AND status NOT IN (\'cancelled\')');
    $conflict->execute([$required['schedule_date'], $id, $required['end_time'], $required['start_time']]);
    if ((int) $conflict->fetchColumn() > 0) {
        throw new InvalidArgumentException('Jadwal bentrok dengan jadwal lain pada tanggal dan rentang waktu tersebut.');
    }

    $status = in_array($_POST['status'] ?? '', ['scheduled','done','postponed','cancelled','assignment'], true) ? $_POST['status'] : 'scheduled';
    $params = [(int)$required['subject_id'],(int)$required['class_id'],($_POST['material_id'] ?? '') !== '' ? (int)$_POST['material_id'] : null,$required['schedule_date'],$required['start_time'],$required['end_time'],trim((string)($_POST['location'] ?? '')),trim((string)($_POST['notes'] ?? '')),$status];
    if ($id > 0) {
        $params[] = $id;
        $db->prepare('UPDATE schedules SET subject_id=?,class_id=?,material_id=?,schedule_date=?,start_time=?,end_time=?,location=?,notes=?,status=? WHERE id=?')->execute($params);
        flash('success', 'Jadwal berhasil diperbarui.');
    } else {
        $db->prepare('INSERT INTO schedules (subject_id,class_id,material_id,schedule_date,start_time,end_time,location,notes,status) VALUES (?,?,?,?,?,?,?,?,?)')->execute($params);
        flash('success', 'Jadwal berhasil ditambahkan.');
    }
    redirect(url('schedules'));
}

function save_material(PDO $db): never
{
    $required = require_fields(['subject_id' => 'Mata pelajaran', 'title' => 'Judul materi', 'content' => 'Isi materi']);
    $id = (int) ($_POST['id'] ?? 0);
    $attachment = trim((string) ($_POST['existing_attachment'] ?? '')) ?: null;
    if (!empty($_FILES['attachment']['name'])) {
        if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Unggahan lampiran gagal.');
        }
        if ((int)$_FILES['attachment']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Ukuran lampiran maksimal 5 MB.');
        }
        $ext = strtolower(pathinfo((string)$_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png'];
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException('Format lampiran tidak didukung.');
        }
        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = dirname(__DIR__) . '/storage/materials/' . $filename;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
            throw new RuntimeException('Lampiran tidak dapat disimpan.');
        }
        if ($attachment && is_file(dirname(__DIR__) . '/' . $attachment)) {
            @unlink(dirname(__DIR__) . '/' . $attachment);
        }
        $attachment = 'storage/materials/' . $filename;
    }

    $status = in_array($_POST['status'] ?? '', ['planned','in_progress','completed'], true) ? $_POST['status'] : 'planned';
    $params = [(int)$required['subject_id'],($_POST['class_id'] ?? '') !== '' ? (int)$_POST['class_id'] : null,trim((string)($_POST['chapter'] ?? '')),$required['title'],trim((string)($_POST['learning_objective'] ?? '')),$required['content'],max(0,(int)($_POST['estimated_minutes'] ?? 0)),trim((string)($_POST['source_reference'] ?? '')),$attachment,$status];
    if ($id > 0) {
        $params[] = $id;
        $db->prepare('UPDATE materials SET subject_id=?,class_id=?,chapter=?,title=?,learning_objective=?,content=?,estimated_minutes=?,source_reference=?,attachment_path=?,status=? WHERE id=?')->execute($params);
        flash('success', 'Materi berhasil diperbarui.');
    } else {
        $db->prepare('INSERT INTO materials (subject_id,class_id,chapter,title,learning_objective,content,estimated_minutes,source_reference,attachment_path,status) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute($params);
        flash('success', 'Materi berhasil ditambahkan.');
    }
    redirect(url('materials'));
}

function delete_material(PDO $db): never
{
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $db->prepare('SELECT attachment_path FROM materials WHERE id=?');
    $stmt->execute([$id]);
    $path = $stmt->fetchColumn();
    $db->prepare('DELETE FROM materials WHERE id=?')->execute([$id]);
    if ($path && is_file(dirname(__DIR__) . '/' . $path)) {
        @unlink(dirname(__DIR__) . '/' . $path);
    }
    flash('success', 'Materi berhasil dihapus.');
    redirect(url('materials'));
}

function save_journal(PDO $db): never
{
    $required = require_fields(['schedule_id' => 'Jadwal', 'actual_material' => 'Materi terlaksana']);
    $id = (int) ($_POST['id'] ?? 0);
    $params = [(int)$required['schedule_id'],($_POST['material_id'] ?? '') !== '' ? (int)$_POST['material_id'] : null,$required['actual_material'],trim((string)($_POST['learning_method'] ?? '')),trim((string)($_POST['class_activity'] ?? '')),max(0,(int)($_POST['students_present'] ?? 0)),max(0,(int)($_POST['students_absent'] ?? 0)),trim((string)($_POST['obstacles'] ?? '')),trim((string)($_POST['student_response'] ?? '')),trim((string)($_POST['follow_up'] ?? '')),trim((string)($_POST['reflection'] ?? ''))];
    if ($id > 0) {
        $params[] = $id;
        $db->prepare('UPDATE teaching_journals SET schedule_id=?,material_id=?,actual_material=?,learning_method=?,class_activity=?,students_present=?,students_absent=?,obstacles=?,student_response=?,follow_up=?,reflection=? WHERE id=?')->execute($params);
        flash('success', 'Jurnal mengajar berhasil diperbarui.');
    } else {
        $db->prepare('INSERT INTO teaching_journals (schedule_id,material_id,actual_material,learning_method,class_activity,students_present,students_absent,obstacles,student_response,follow_up,reflection) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute($params);
        $db->prepare("UPDATE schedules SET status='done' WHERE id=?")->execute([(int)$required['schedule_id']]);
        flash('success', 'Jurnal mengajar berhasil ditambahkan dan jadwal ditandai terlaksana.');
    }
    redirect(url('journals'));
}

function save_question(PDO $db): never
{
    $required = require_fields(['subject_id'=>'Mata pelajaran','question_text'=>'Pertanyaan','option_a'=>'Pilihan A','option_b'=>'Pilihan B','option_c'=>'Pilihan C','option_d'=>'Pilihan D','correct_option'=>'Kunci jawaban']);
    $options = [$required['option_a'],$required['option_b'],$required['option_c'],$required['option_d']];
    if (count(array_unique(array_map('mb_strtolower', $options))) < 4) {
        throw new InvalidArgumentException('Keempat pilihan jawaban harus berbeda.');
    }
    if (!in_array($required['correct_option'], ['A','B','C','D'], true)) {
        throw new InvalidArgumentException('Kunci jawaban harus A, B, C, atau D.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $params = [(int)$required['subject_id'],($_POST['material_id'] ?? '') !== '' ? (int)$_POST['material_id'] : null,$required['question_text'],$required['option_a'],$required['option_b'],$required['option_c'],$required['option_d'],$required['correct_option'],trim((string)($_POST['explanation'] ?? '')),in_array($_POST['difficulty'] ?? '', ['mudah','sedang','sulit'], true) ? $_POST['difficulty'] : 'sedang',in_array($_POST['cognitive_level'] ?? '', ['C1','C2','C3','C4','C5','C6'], true) ? $_POST['cognitive_level'] : 'C2',in_array($_POST['status'] ?? '', ['draft','reviewed','approved','rejected'], true) ? $_POST['status'] : 'draft','manual'];
    if ($id > 0) {
        $params[] = $id;
        $db->prepare('UPDATE questions SET subject_id=?,material_id=?,question_text=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_option=?,explanation=?,difficulty=?,cognitive_level=?,status=?,source_type=? WHERE id=?')->execute($params);
        flash('success', 'Soal berhasil diperbarui.');
    } else {
        $db->prepare('INSERT INTO questions (subject_id,material_id,question_text,option_a,option_b,option_c,option_d,correct_option,explanation,difficulty,cognitive_level,status,source_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($params);
        flash('success', 'Soal berhasil ditambahkan.');
    }
    redirect(url('questions'));
}

function generate_questions(PDO $db): never
{
    $materialId = (int) ($_POST['material_id'] ?? 0);
    $count = max(1, min(10, (int) ($_POST['count'] ?? 5)));
    $difficulty = in_array($_POST['difficulty'] ?? '', ['mudah','sedang','sulit'], true) ? $_POST['difficulty'] : 'sedang';
    $cognitive = in_array($_POST['cognitive_level'] ?? '', ['C1','C2','C3','C4'], true) ? $_POST['cognitive_level'] : 'C2';
    $stmt = $db->prepare('SELECT id,subject_id,title,content FROM materials WHERE id=?');
    $stmt->execute([$materialId]);
    $material = $stmt->fetch();
    if (!$material) {
        throw new InvalidArgumentException('Materi tidak ditemukan.');
    }
    $questions = (new QuestionGenerator())->generate($material['content'], $count, $difficulty, $cognitive);
    $_SESSION['generated_questions'] = ['material' => $material, 'items' => $questions];
    flash('warning', count($questions) . ' soal draf berhasil dibuat. Tinjau semua pilihan sebelum menyimpan.');
    redirect(url('generator'));
}

function save_generated_questions(PDO $db): never
{
    $bundle = $_SESSION['generated_questions'] ?? null;
    if (!$bundle || empty($bundle['material'])) {
        throw new RuntimeException('Draf generator tidak ditemukan.');
    }
    $selected = $_POST['save'] ?? [];
    $items = $_POST['items'] ?? [];
    $saved = 0;
    $stmt = $db->prepare('INSERT INTO questions (subject_id,material_id,question_text,option_a,option_b,option_c,option_d,correct_option,explanation,difficulty,cognitive_level,status,source_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($items as $index => $item) {
        if (!isset($selected[$index])) {
            continue;
        }
        $values = [trim((string)($item['question_text'] ?? '')),trim((string)($item['option_a'] ?? '')),trim((string)($item['option_b'] ?? '')),trim((string)($item['option_c'] ?? '')),trim((string)($item['option_d'] ?? ''))];
        if (in_array('', $values, true)) {
            continue;
        }
        $correct = (string)($item['correct_option'] ?? '');
        if (!in_array($correct, ['A','B','C','D'], true)) {
            continue;
        }
        $stmt->execute([(int)$bundle['material']['subject_id'],(int)$bundle['material']['id'],$values[0],$values[1],$values[2],$values[3],$values[4],$correct,trim((string)($item['explanation'] ?? '')),in_array($item['difficulty'] ?? '', ['mudah','sedang','sulit'], true)?$item['difficulty']:'sedang',in_array($item['cognitive_level'] ?? '', ['C1','C2','C3','C4','C5','C6'], true)?$item['cognitive_level']:'C2','draft','generator']);
        $saved++;
    }
    unset($_SESSION['generated_questions']);
    flash('success', "{$saved} soal disimpan ke bank soal sebagai draf.");
    redirect(url('questions'));
}

function restore_backup(PDO $db): never
{
    if (empty($_FILES['backup_file']['tmp_name']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Pilih file backup JSON yang valid.');
    }
    if ((int)$_FILES['backup_file']['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file backup maksimal 15 MB.');
    }
    BackupService::restore($db, $_FILES['backup_file']['tmp_name']);
    flash('success', 'Backup berhasil dipulihkan.');
    redirect(url('backup'));
}

function save_settings(PDO $db): never
{
    $pairs = [
        'teacher_name' => trim((string)($_POST['teacher_name'] ?? '')),
        'institution_name' => trim((string)($_POST['institution_name'] ?? '')),
        'active_academic_year' => trim((string)($_POST['active_academic_year'] ?? '')),
        'default_reminder_minutes' => (string)max(0,(int)($_POST['default_reminder_minutes'] ?? 30)),
    ];
    $stmt = $db->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    foreach ($pairs as $key => $value) {
        $stmt->execute([$key,$value]);
    }
    flash('success', 'Pengaturan berhasil disimpan.');
    redirect(url('settings'));
}

function delete_record(PDO $db, string $table, string $page): never
{
    $allowed = ['subjects','classes','schedules','teaching_journals','questions'];
    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Tabel tidak diizinkan.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $db->prepare("DELETE FROM `{$table}` WHERE id=?")->execute([$id]);
    flash('success', 'Data berhasil dihapus.');
    redirect(url($page));
}

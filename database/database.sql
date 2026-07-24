SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS teaching_journals;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS schedules;
DROP TABLE IF EXISTS schedule_templates;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
CREATE TABLE subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    grade_level VARCHAR(120) NOT NULL,
    semester VARCHAR(20) NOT NULL DEFAULT 'Ganjil',
    academic_year VARCHAR(20) NOT NULL,
    curriculum VARCHAR(120) NULL,
    description TEXT NULL,
    learning_outcomes TEXT NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subject_status (status),
    INDEX idx_subject_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE classes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    grade_level VARCHAR(120) NOT NULL,
    institution VARCHAR(190) NULL,
    room VARCHAR(100) NULL,
    student_count INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_class_status (status),
    INDEX idx_class_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NULL,
    chapter VARCHAR(190) NULL,
    title VARCHAR(220) NOT NULL,
    learning_objective TEXT NULL,
    content LONGTEXT NOT NULL,
    estimated_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    source_reference TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    status ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_material_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_material_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_material_subject (subject_id),
    INDEX idx_material_status (status),
    FULLTEXT INDEX ft_material_text (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE schedule_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(160) NULL,
    notes TEXT NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_template_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_template_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_template_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_template_day (day_of_week, start_time),
    INDEX idx_template_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(160) NULL,
    notes TEXT NULL,
    status ENUM('scheduled','done','postponed','cancelled','assignment') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_schedule_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_schedule_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_schedule_date (schedule_date, start_time),
    INDEX idx_schedule_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teaching_journals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id BIGINT UNSIGNED NOT NULL UNIQUE,
    material_id BIGINT UNSIGNED NULL,
    actual_material TEXT NOT NULL,
    learning_method VARCHAR(190) NULL,
    class_activity TEXT NULL,
    students_present INT UNSIGNED NOT NULL DEFAULT 0,
    students_absent INT UNSIGNED NOT NULL DEFAULT 0,
    obstacles TEXT NULL,
    student_response TEXT NULL,
    follow_up TEXT NULL,
    reflection TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_journal_schedule FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_journal_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_journal_material (material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NULL,
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_option ENUM('A','B','C','D') NOT NULL,
    explanation TEXT NULL,
    difficulty ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'sedang',
    cognitive_level ENUM('C1','C2','C3','C4','C5','C6') NOT NULL DEFAULT 'C2',
    status ENUM('draft','reviewed','approved','rejected') NOT NULL DEFAULT 'draft',
    source_type ENUM('manual','generator') NOT NULL DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_question_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_question_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_question_subject (subject_id),
    INDEX idx_question_status (status),
    INDEX idx_question_difficulty (difficulty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(120) NOT NULL UNIQUE,
    `value` TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO subjects (name,grade_level,semester,academic_year,curriculum,description,learning_outcomes,status) VALUES
('Ilmu Pengetahuan Alam','SMP Kelas VIII','Ganjil','2026/2027','Kurikulum Merdeka','Contoh mata pelajaran awal yang dapat diubah atau dihapus.','Peserta didik mampu menjelaskan keterkaitan struktur dan fungsi sistem organ manusia.','active');

INSERT INTO classes (name,grade_level,institution,room,student_count,notes,status) VALUES
('VIII A','SMP Kelas VIII','Sekolah Contoh','Ruang 8A',30,'Data demonstrasi. Silakan sesuaikan dengan kelas Anda.','active');

INSERT INTO materials (subject_id,class_id,chapter,title,learning_objective,content,estimated_minutes,source_reference,status) VALUES
(1,1,'Bab 1 • Sistem Pernapasan','Organ dan Mekanisme Pernapasan Manusia','Peserta didik mampu mengidentifikasi organ pernapasan dan menjelaskan mekanisme inspirasi serta ekspirasi.','Sistem pernapasan manusia terdiri atas hidung, faring, laring, trakea, bronkus, dan paru-paru. Hidung menyaring udara melalui rambut halus dan lendir sebelum udara memasuki saluran pernapasan. Trakea mengalirkan udara menuju bronkus dan memiliki silia yang membantu mengeluarkan partikel asing. Bronkus bercabang menjadi bronkiolus yang berakhir pada alveolus. Alveolus merupakan tempat pertukaran oksigen dan karbon dioksida melalui proses difusi. Inspirasi terjadi ketika diafragma berkontraksi sehingga volume rongga dada meningkat dan tekanan udara menurun. Ekspirasi terjadi ketika diafragma berelaksasi sehingga volume rongga dada menurun dan udara keluar dari paru-paru.','90','Buku IPA SMP Kelas VIII','in_progress');

INSERT INTO questions (subject_id,material_id,question_text,option_a,option_b,option_c,option_d,correct_option,explanation,difficulty,cognitive_level,status,source_type) VALUES
(1,1,'Bagian sistem pernapasan yang menjadi tempat pertukaran oksigen dan karbon dioksida adalah ...','Trakea','Alveolus','Laring','Bronkus','B','Alveolus memiliki dinding tipis dan dikelilingi kapiler darah sehingga mendukung proses difusi gas.','mudah','C1','approved','manual');

INSERT INTO settings (`key`,`value`) VALUES
('teacher_name','Pengajar'),
('institution_name','Sekolah Contoh'),
('active_academic_year','2026/2027'),
('default_reminder_minutes','30');

SET FOREIGN_KEY_CHECKS=1;

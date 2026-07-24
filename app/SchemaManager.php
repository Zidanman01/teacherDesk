<?php
declare(strict_types=1);

final class SchemaManager
{
    public static function migrate(PDO $db): void
    {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS schedule_templates (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}

-- ============================================================
--  Paper Class Results System – Database Schema
--  Run via install.php OR paste into phpMyAdmin SQL tab
-- ============================================================

CREATE TABLE IF NOT EXISTS `students` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `index_number` VARCHAR(30)  NOT NULL,
  `name`         VARCHAR(120) NOT NULL,
  `batch_year`   VARCHAR(10)  DEFAULT NULL,
  `created_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_index` (`index_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `papers` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `paper_number` VARCHAR(30)   NOT NULL,
  `paper_name`   VARCHAR(120)  DEFAULT NULL,
  `paper_date`   DATE          DEFAULT NULL,
  `total_marks`  DECIMAL(8,2)  NOT NULL DEFAULT 100.00,
  `batch_year`   VARCHAR(10)   DEFAULT NULL,
  `created_at`   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `marks` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `student_id` INT(11)      NOT NULL,
  `paper_id`   INT(11)      NOT NULL,
  `marks`      DECIMAL(8,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_paper` (`student_id`, `paper_id`),
  CONSTRAINT `fk_marks_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_marks_paper`   FOREIGN KEY (`paper_id`)   REFERENCES `papers`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(60)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

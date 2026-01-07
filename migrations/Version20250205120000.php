<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250205120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Final Mini-LMS schema aligned with domain model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE user (
    id INT AUTO_INCREMENT NOT NULL,
    email VARCHAR(180) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    specialty VARCHAR(100) DEFAULT NULL,
    study_year VARCHAR(50) DEFAULT NULL,
    avatar VARCHAR(255) NOT NULL DEFAULT 'default-avatar.png',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX UNIQ_USER_EMAIL (email),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE course (
    id INT AUTO_INCREMENT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT DEFAULT NULL,
    specialty VARCHAR(100) NOT NULL,
    target_year VARCHAR(50) NOT NULL,
    course_picture VARCHAR(255) NOT NULL DEFAULT 'default-course.jpg',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX IDX_COURSE_TEACHER (teacher_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_COURSE_TEACHER
        FOREIGN KEY (teacher_id)
        REFERENCES user (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE lesson (
    id INT AUTO_INCREMENT NOT NULL,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX IDX_LESSON_COURSE (course_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_LESSON_COURSE
        FOREIGN KEY (course_id)
        REFERENCES course (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE resource (
    id INT AUTO_INCREMENT NOT NULL,
    lesson_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL DEFAULT 'pdf',
    sort_order INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX IDX_RESOURCE_LESSON (lesson_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_RESOURCE_LESSON
        FOREIGN KEY (lesson_id)
        REFERENCES lesson (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE enrollment (
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX IDX_ENROLL_STUDENT (student_id),
    INDEX IDX_ENROLL_COURSE (course_id),
    PRIMARY KEY (student_id, course_id),
    CONSTRAINT FK_ENROLL_STUDENT
        FOREIGN KEY (student_id)
        REFERENCES user (id)
        ON DELETE CASCADE,
    CONSTRAINT FK_ENROLL_COURSE
        FOREIGN KEY (course_id)
        REFERENCES course (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE resource_progress (
    id INT AUTO_INCREMENT NOT NULL,
    student_id INT NOT NULL,
    resource_id INT NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    time_spent INT NOT NULL DEFAULT 0,
    completed_at DATETIME DEFAULT NULL,
    INDEX IDX_PROG_STUDENT (student_id),
    INDEX IDX_PROG_RESOURCE (resource_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_PROG_STUDENT
        FOREIGN KEY (student_id)
        REFERENCES user (id),
    CONSTRAINT FK_PROG_RESOURCE
        FOREIGN KEY (resource_id)
        REFERENCES resource (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE quiz (
    id INT AUTO_INCREMENT NOT NULL,
    lesson_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    json_path VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX IDX_QUIZ_LESSON (lesson_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_QUIZ_LESSON
        FOREIGN KEY (lesson_id)
        REFERENCES lesson (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE attempt (
    id INT AUTO_INCREMENT NOT NULL,
    student_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT NOT NULL,
    passed TINYINT(1) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX IDX_ATTEMPT_STUDENT (student_id),
    INDEX IDX_ATTEMPT_QUIZ (quiz_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_ATTEMPT_STUDENT
        FOREIGN KEY (student_id)
        REFERENCES user (id),
    CONSTRAINT FK_ATTEMPT_QUIZ
        FOREIGN KEY (quiz_id)
        REFERENCES quiz (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE attempt');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE resource_progress');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE lesson');
        $this->addSql('DROP TABLE enrollment');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE user');
    }
}

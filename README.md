# Mini_LMS

A Mini Learning Management System (LMS) built with PHP and Symfony. This platform facilitates interaction between students, teachers, and administrators, offering features for course management, quizzes, and progress tracking.

## Live Demo

Check out the live build here: [lms.lahlou.tech](https://lms.lahlou.tech)

## Features

*   **Student Dashboard**: View enrolled courses, track progress, take quizzes, and access resources.
*   **Teacher Dashboard**: Create and manage courses, upload resources (PDFs), create interactive quizzes, and view student analytics.
*   **Admin Dashboard**: Manage users (students/teachers), review registration requests, and oversee platform statistics.
*   **Course Management**: Comprehensive course creation with cover images, descriptions, and categorized content.
*   **Quiz System**: Dynamic quiz builder for teachers and interactive quiz taking for students with automated scoring.

## Requirements

*   PHP >= 8.2
*   Composer
*   Symfony CLI (recommended)
*   MySQL or MariaDB

## Installation

1.  **Clone the repository**
    ```bash
    git clone <repository-url>
    cd Mini_LMS
    ```

2.  **Install dependencies**
    ```bash
    composer install
    ```

3.  **Environment Configuration**
    Copy `.env` to `.env.local` and configure your database connection.
    ```bash
    cp .env .env.local
    ```
    Open `.env.local` and update the `DATABASE_URL` variable:
    ```env
    DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/mini_lms?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
    ```

4.  **Database Setup**
    Create the database and run migrations.
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

    *(Optional) Load fixtures if available:*
    ```bash
    php bin/console doctrine:fixtures:load
    ```

## Usage

Start the local development server using the Symfony CLI:

```bash
symfony serve
```

Or using PHP's built-in server:

```bash
php -S localhost:8000 -t public
```

Access the application at `http://localhost:8000`.

## License

Proprietary

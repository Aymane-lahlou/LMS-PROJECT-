# Mini_LMS

Mini_LMS is a lightweight **Learning Management System (LMS)** built with **PHP** and **Symfony**.  
The platform enables structured interaction between **students**, **teachers**, and **administrators**, providing tools for course management, quizzes, and progress tracking.

## Live Demo

Access the live version here:  
👉 https://lms.lahlou.tech

## Features

- **Student Dashboard**
  - View enrolled courses
  - Track learning progress
  - Take quizzes
  - Access course resources

- **Teacher Dashboard**
  - Create and manage courses
  - Upload learning resources (PDFs)
  - Build interactive quizzes
  - View student performance analytics

- **Admin Dashboard**
  - Manage users (students and teachers)
  - Review registration requests
  - Monitor platform statistics

- **Course Management**
  - Create courses with cover images and descriptions
  - Organize content by categories

- **Quiz System**
  - Dynamic quiz builder for teachers
  - Interactive quiz experience for students
  - Automated grading and scoring

## Requirements

- PHP >= 8.2
- Composer
- Symfony CLI (recommended)
- MySQL or MariaDB

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/Aymane-lahlou/LMS-PROJECT-.git
cd Mini_LMS
```

### 2. Install dependencies

```bash
composer install
```

### 3. Environment configuration

Copy the environment file:

```bash
cp .env .env.local
```

Edit `.env.local` and configure your database connection:

```env
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/mini_lms?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

### 4. Database setup

Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

### 5. Load data fixtures

The application includes data fixtures with predefined demo accounts.

```bash
php bin/console doctrine:fixtures:load
```

> ⚠️ **Warning**: This command will delete existing data and reload demo records.

## Usage

Start the development server using Symfony CLI:

```bash
symfony serve
```

Or using PHP’s built-in server:

```bash
php -S localhost:8000 -t public
```

Access the application at:

```
http://localhost:8000
```

## Demo Accounts (Fixtures)

After running migrations and fixtures, you can log in using the following single account per role.

### Admin Account
- **Email**: `admin@lms.test`
- **Password**: `AdminPass123!`
- **Role**: `ROLE_ADMIN`

### Teacher Account
- **Email**: `teacher@lms.test`
- **Password**: `TeacherPass123!`
- **Role**: `ROLE_TEACHER`

### Student Account
- **Email**: `student@lms.test`
- **Password**: `StudentPass123!`
- **Role**: `ROLE_STUDENT`
- **Status**: `ACTIVE`

## License

Proprietary

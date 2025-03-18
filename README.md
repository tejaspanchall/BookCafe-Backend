# BookCafe Backend API

A robust and scalable Laravel-based RESTful API for a modern online bookstore application.

## Frontend Repository

The frontend for this project is available at: [BookCafe-Frontend](https://github.com/tejaspanchall/BookCafe-Frontend)

## Database Schema

The database schema SQL file is available at: [database/schema.sql](database/schema.sql)

You can import this file into your PostgreSQL database to create the required tables and relationships.

## Features

- 📚 Book management system
- 👥 User authentication and authorization with Sanctum
- 🔍 Advanced search and filtering
- 🔒 Robust security features

## Technologies Used

- **Framework**: Laravel 12
- **PHP Version**: 8.2+
- **Database**: PostgreSQL
- **Authentication**: Laravel Sanctum
- **Queue System**: Laravel Queue with Database Driver
- **File Storage**: Laravel Storage

## Prerequisites

- PHP 8.2 or higher
- Composer
- PostgreSQL
- Node.js & NPM (for asset compilation)

## Getting Started

1. Clone the repository:
```bash
git clone https://github.com/tejaspanchall/BookCafe-Backend.git
cd BookCafe-Backend
```

2. Install dependencies:
```bash
composer install
```

3. Set up environment variables:
```bash
cp .env.example .env
php artisan key:generate
```

4. Update the `.env` file with your database credentials:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bookstore
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

5. Run database migrations and seeders:
```bash
php artisan migrate
php artisan db:seed
```

6. Create a symbolic link for storage:
```bash
php artisan storage:link
```

7. Start the development server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`

## License

This project is licensed under the MIT License - see the LICENSE file for details.

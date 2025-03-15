# Laravel Project Setup with PostgreSQL

## Prerequisites
Ensure you have the following installed on your system:

- PHP (>= 8.0)
- Composer
- Laravel CLI
- PostgreSQL

## Installation Steps

### 1. Clone the Repository
```bash
git clone <repository-url>
cd <project-directory>
```

### 2. Install PHP Dependencies
```bash
composer install
```
```bash
php artisan storage:link
```



### 3. Configure Environment Variables
Copy the `.env.example` file to `.env`:
```bash
cp .env.example .env
```
Update the `.env` file with your PostgreSQL database credentials:
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

### 4. Create and Setup the Database
Ensure PostgreSQL is running, then create the database manually or using the command:
```sql
CREATE DATABASE your_database_name;
```
Run migrations to create tables:
```bash
php artisan migrate
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Start the Development Server
```bash
php artisan serve
```
The application should now be running at `http://127.0.0.1:8000`.

## Additional Commands

### Clearing Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---
Your Laravel backend is now set up with PostgreSQL! 🚀

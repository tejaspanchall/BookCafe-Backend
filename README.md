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

# API Documentation

## BASE_URL
Your API base URL (e.g., `http://localhost:8000/api`)

## TOKEN
This will store your authentication token after login.

## Authentication Endpoints (`/api/auth/...`)

### Register
**POST** `{{BASE_URL}}/auth/register`

**Body (JSON):**
```json
{
    "firstname": "John",
    "lastname": "Doe",
    "email": "john@example.com",
    "password": "password123",
    "role": "teacher"  // or "student"
}
```

### Login
**POST** `{{BASE_URL}}/auth/login`

**Body (JSON):**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

### Forgot Password
**POST** `{{BASE_URL}}/auth/forgot-password`

**Body (JSON):**
```json
{
    "email": "john@example.com"
}
```

### Reset Password
**POST** `{{BASE_URL}}/auth/reset-password`

**Body (JSON):**
```json
{
    "token": "reset_token_received_in_email",
    "password": "newpassword123"
}
```

### Logout (Requires Authentication)
**POST** `{{BASE_URL}}/auth/logout`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

---

## Book Endpoints (All require Authentication - add Bearer token)

### Add Book (Teachers only)
**POST** `{{BASE_URL}}/books/add`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

**Body (JSON):**
```json
{
    "title": "Book Title",
    "author": "Author Name",
    "isbn": "978-3-16-148410-0",
    "description": "Book description",
    "image": "http://image-url.com/book.jpg"  // optional
}
```

### Get All Books
**GET** `{{BASE_URL}}/books/get-books`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

### Get Library (Teachers only)
**GET** `{{BASE_URL}}/books/get-library`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

### Get My Library
**GET** `{{BASE_URL}}/books/my-library`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

### Add Book to Library
**POST** `{{BASE_URL}}/books/{book_id}/add-to-library`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

### Remove Book from Library
**DELETE** `{{BASE_URL}}/books/{book_id}/remove-from-library`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

### Update Book (Teachers only)
**PUT** `{{BASE_URL}}/books/{book_id}`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

**Body (JSON):**
```json
{
    "title": "Updated Title",
    "author": "Updated Author",
    "isbn": "978-3-16-148410-1",
    "description": "Updated description",
    "image": "http://image-url.com/updated.jpg"
}
```
*Note: All fields are optional in update*

### Delete Book (Teachers only)
**DELETE** `{{BASE_URL}}/books/{book_id}`

**Headers:**
```
Authorization: Bearer {{TOKEN}}
```

### Search Books
**GET** `{{BASE_URL}}/books/search?query=search_term`

**Headers:**
```
Authorization: Bearer {{TOKEN}}

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(64),
    role VARCHAR(20) NOT NULL DEFAULT 'teacher' CHECK (role IN ('teacher', 'student'))
);

CREATE TABLE books (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    description TEXT,
    isbn VARCHAR(20) UNIQUE NOT NULL,
    author VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    price DECIMAL(10,2)
);

CREATE TABLE user_books (
    user_id INTEGER REFERENCES users(id),
    book_id INTEGER REFERENCES books(id),
    PRIMARY KEY (user_id, book_id)
); 
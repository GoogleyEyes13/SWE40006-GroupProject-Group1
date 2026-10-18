-- User accounts
CREATE TABLE users (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Books selected from Open Library
CREATE TABLE books (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    openlibrary_key VARCHAR(100) UNIQUE NOT NULL,
    title TEXT NOT NULL,
    author TEXT,
    publication_year INTEGER,
    cover_id INTEGER
);

-- Individual user collections
CREATE TABLE user_books (
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    book_id BIGINT REFERENCES books(id) ON DELETE CASCADE,

    status VARCHAR(20) NOT NULL DEFAULT 'want_to_read'
        CHECK (status IN (
            'want_to_read',
            'owned',
            'reading',
            'read'
        )),

    quantity INTEGER DEFAULT 0 CHECK (quantity >= 0),
    date_added TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, book_id)
);
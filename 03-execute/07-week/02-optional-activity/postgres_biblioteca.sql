DROP DATABASE IF EXISTS biblioteca;
CREATE DATABASE biblioteca;


CREATE TABLE author (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    country VARCHAR(50),
    birth_year INT
);

CREATE TABLE category (
    id SERIAL PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(150)
);

CREATE TABLE reader (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20)
);

CREATE TABLE book (
    id SERIAL PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    published_year INT,
    stock INT,
    author_id INT,
    category_id INT,
    CONSTRAINT fk_book_author FOREIGN KEY (author_id) REFERENCES author(id),
    CONSTRAINT fk_book_category FOREIGN KEY (category_id) REFERENCES category(id)
);

CREATE TABLE loan (
    id SERIAL PRIMARY KEY,
    loan_date DATE NOT NULL,
    return_date DATE,
    status VARCHAR(20),
    reader_id INT,
    book_id INT,
    CONSTRAINT fk_loan_reader FOREIGN KEY (reader_id) REFERENCES reader(id),
    CONSTRAINT fk_loan_book FOREIGN KEY (book_id) REFERENCES book(id)
);

INSERT INTO author (full_name, country, birth_year) VALUES
('Gabriel Garcia Marquez', 'Colombia', 1927),
('Isabel Allende', 'Chile', 1942),
('Julio Verne', 'Francia', 1828),
('J.K. Rowling', 'Reino Unido', 1965),
('Mario Vargas Llosa', 'Peru', 1936),
('Autor Temporal Uno', 'Argentina', 1970),
('Autor Temporal Dos', 'Mexico', 1980);

INSERT INTO category (name, description) VALUES
('Novela', 'Libros de narrativa extensa'),
('Ciencia Ficcion', 'Historias basadas en imaginacion y ciencia'),
('Fantasia', 'Mundos ficticios y elementos magicos'),
('Historia', 'Libros sobre hechos historicos'),
('Tecnologia', 'Libros relacionados con informatica y sistemas'),
('Categoria Temporal Uno', 'Categoria auxiliar para eliminacion'),
('Categoria Temporal Dos', 'Categoria auxiliar para eliminacion');

INSERT INTO reader (full_name, email, phone) VALUES
('Roman Bolanos', 'roman@gmail.com', '3001112233'),
('Laura Martinez', 'laura@gmail.com', '3012223344'),
('Carlos Perez', 'carlos@gmail.com', '3023334455'),
('Sofia Ramirez', 'sofia@gmail.com', '3034445566'),
('Andres Gomez', 'andres@gmail.com', '3045556677'),
('Lector Temporal Uno', 'lector1.temp@gmail.com', '3110001111'),
('Lector Temporal Dos', 'lector2.temp@gmail.com', '3110002222');

INSERT INTO book (title, published_year, stock, author_id, category_id) VALUES
('Cien Anos de Soledad', 1967, 4, 1, 1),
('La Casa de los Espiritus', 1982, 3, 2, 1),
('Viaje al Centro de la Tierra', 1864, 5, 3, 2),
('Harry Potter y la Piedra Filosofal', 1997, 6, 4, 3),
('La Ciudad y los Perros', 1963, 2, 5, 1),
('Libro Temporal Uno', 2020, 1, 6, 6),
('Libro Temporal Dos', 2021, 1, 7, 7);

INSERT INTO loan (loan_date, return_date, status, reader_id, book_id) VALUES
('2026-04-01', '2026-04-10', 'Devuelto', 1, 1),
('2026-04-02', NULL, 'Prestado', 2, 2),
('2026-04-03', '2026-04-11', 'Devuelto', 3, 3),
('2026-04-04', NULL, 'Prestado', 4, 4),
('2026-04-05', NULL, 'Prestado', 5, 5),
('2026-04-06', NULL, 'Prestado', 6, 6),
('2026-04-07', NULL, 'Prestado', 7, 7);

UPDATE author
SET country = 'Colombia', birth_year = 1928
WHERE id = 1;

UPDATE author
SET country = 'Chile', birth_year = 1943
WHERE id = 2;

UPDATE category
SET description = 'Libros de fantasia, magia y mundos imaginarios'
WHERE id = 3;

UPDATE category
SET name = 'Historia Universal',
    description = 'Libros sobre procesos y hechos historicos del mundo'
WHERE id = 4;

UPDATE reader
SET phone = '3056667788', email = 'andres.gomez@gmail.com'
WHERE id = 5;

UPDATE reader
SET full_name = 'Laura Martinez Gomez',
    phone = '3102223344'
WHERE id = 2;

UPDATE book
SET stock = 7
WHERE id = 4;

UPDATE book
SET title = 'Harry Potter y la Piedra Filosofal - Edicion Especial',
    stock = 8
WHERE id = 4;

UPDATE loan
SET return_date = '2026-04-15', status = 'Devuelto'
WHERE id = 2;

UPDATE loan
SET status = 'Prestado', return_date = NULL
WHERE id = 5;

DELETE FROM loan WHERE id = 6;
DELETE FROM loan WHERE id = 7;

DELETE FROM book WHERE id = 6;
DELETE FROM book WHERE id = 7;

DELETE FROM reader WHERE id = 6;
DELETE FROM reader WHERE id = 7;

DELETE FROM author WHERE id = 6;
DELETE FROM author WHERE id = 7;

DELETE FROM category WHERE id = 6;
DELETE FROM category WHERE id = 7;

SELECT * FROM author;
SELECT * FROM author WHERE country = 'Colombia';

SELECT * FROM category;
SELECT * FROM category WHERE name LIKE 'Historia%';

SELECT * FROM reader;
SELECT * FROM reader WHERE email LIKE '%gmail.com';

SELECT * FROM book;
SELECT * FROM book WHERE stock >= 4;

SELECT * FROM loan;
SELECT * FROM loan WHERE status = 'Prestado';

SELECT 
    b.title,
    a.full_name AS author,
    c.name AS category
FROM book b
INNER JOIN author a ON b.author_id = a.id
INNER JOIN category c ON b.category_id = c.id;

SELECT
    l.id,
    r.full_name AS reader,
    b.title AS book,
    l.status
FROM loan l
INNER JOIN reader r ON l.reader_id = r.id
INNER JOIN book b ON l.book_id = b.id;
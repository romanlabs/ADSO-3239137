CREATE DATABASE vuelos;
GO

USE vuelos;
GO

CREATE TABLE aeropuerto (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100),
    ciudad VARCHAR(100),
    pais VARCHAR(100)
);

CREATE TABLE avion (
    id INT IDENTITY(1,1) PRIMARY KEY,
    modelo VARCHAR(100),
    capacidad INT
);

CREATE TABLE pasajero (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(100),
    telefono VARCHAR(20)
);

CREATE TABLE vuelo (
    id INT IDENTITY(1,1) PRIMARY KEY,
    origen_id INT,
    destino_id INT,
    avion_id INT,
    fecha DATE,
    FOREIGN KEY (origen_id) REFERENCES aeropuerto(id),
    FOREIGN KEY (destino_id) REFERENCES aeropuerto(id),
    FOREIGN KEY (avion_id) REFERENCES avion(id)
);

CREATE TABLE reserva (
    id INT IDENTITY(1,1) PRIMARY KEY,
    pasajero_id INT,
    vuelo_id INT,
    asiento VARCHAR(10),
    FOREIGN KEY (pasajero_id) REFERENCES pasajero(id),
    FOREIGN KEY (vuelo_id) REFERENCES vuelo(id)
);

INSERT INTO aeropuerto (nombre, ciudad, pais) VALUES
('El Dorado','Bogota','Colombia'),
('JFK','New York','USA'),
('Barajas','Madrid','España'),
('Heathrow','Londres','UK'),
('Narita','Tokyo','Japon');

INSERT INTO avion (modelo, capacidad) VALUES
('Boeing 737',180),
('Airbus A320',170),
('Boeing 777',300),
('Airbus A330',250),
('Boeing 787',280);

INSERT INTO pasajero (nombre,email,telefono) VALUES
('Juan Perez','juan@gmail.com','300111'),
('Maria Lopez','maria@gmail.com','300222'),
('Carlos Ruiz','carlos@gmail.com','300333'),
('Ana Torres','ana@gmail.com','300444'),
('Luis Gomez','luis@gmail.com','300555');

INSERT INTO vuelo (origen_id,destino_id,avion_id,fecha) VALUES
(1,2,1,'2026-05-01'),
(2,3,2,'2026-05-02'),
(3,4,3,'2026-05-03'),
(4,5,4,'2026-05-04'),
(5,1,5,'2026-05-05');

INSERT INTO reserva (pasajero_id,vuelo_id,asiento) VALUES
(1,1,'12A'),
(2,2,'14B'),
(3,3,'10C'),
(4,4,'9D'),
(5,5,'8F');

SELECT * FROM pasajero;

SELECT * FROM avion WHERE capacidad > 200;

SELECT 
p.nombre AS pasajero,
r.asiento
FROM reserva r
INNER JOIN pasajero p ON r.pasajero_id = p.id;

SELECT
v.id,
a1.nombre AS origen,
a2.nombre AS destino
FROM vuelo v
INNER JOIN aeropuerto a1 ON v.origen_id = a1.id
INNER JOIN aeropuerto a2 ON v.destino_id = a2.id;


INSERT INTO aeropuerto (nombre, ciudad, pais) VALUES
('Temporal Uno','Ciudad Temp 1','Pais Temp 1'),
('Temporal Dos','Ciudad Temp 2','Pais Temp 2');

INSERT INTO avion (modelo, capacidad) VALUES
('Temporal 737',150),
('Temporal 320',160);

INSERT INTO pasajero (nombre,email,telefono) VALUES
('Pasajero Temporal Uno','temp1@gmail.com','311111'),
('Pasajero Temporal Dos','temp2@gmail.com','322222');

INSERT INTO vuelo (origen_id,destino_id,avion_id,fecha) VALUES
(6,7,6,'2026-05-06'),
(7,6,7,'2026-05-07');

INSERT INTO reserva (pasajero_id,vuelo_id,asiento) VALUES
(6,6,'1A'),
(7,7,'2B');


UPDATE aeropuerto
SET ciudad = 'Bogota D.C.'
WHERE id = 1;

UPDATE aeropuerto
SET pais = 'Estados Unidos'
WHERE id = 2;

UPDATE avion
SET capacidad = 190
WHERE id = 1;

UPDATE avion
SET modelo = 'Airbus A320 Neo'
WHERE id = 2;

UPDATE pasajero
SET telefono = '300999'
WHERE id = 1;

UPDATE pasajero
SET email = 'maria.lopez@gmail.com'
WHERE id = 2;

UPDATE vuelo
SET fecha = '2026-05-10'
WHERE id = 1;

UPDATE vuelo
SET avion_id = 1
WHERE id = 2;

UPDATE reserva
SET asiento = '15A'
WHERE id = 1;

UPDATE reserva
SET asiento = '16B'
WHERE id = 2;


SELECT * FROM aeropuerto;
SELECT * FROM aeropuerto WHERE pais = 'Colombia';

SELECT * FROM avion;
SELECT * FROM avion WHERE capacidad > 200;


SELECT * FROM pasajero;
SELECT * FROM pasajero WHERE nombre LIKE 'Maria%';

SELECT * FROM vuelo;
SELECT * FROM vuelo WHERE fecha >= '2026-05-03';


SELECT * FROM reserva;
SELECT * FROM reserva WHERE asiento LIKE '1%';


DELETE FROM reserva WHERE id = 6;
DELETE FROM reserva WHERE id = 7;


DELETE FROM vuelo WHERE id = 6;
DELETE FROM vuelo WHERE id = 7;


DELETE FROM pasajero WHERE id = 6;
DELETE FROM pasajero WHERE id = 7;

DELETE FROM avion WHERE id = 6;
DELETE FROM avion WHERE id = 7;


DELETE FROM aeropuerto WHERE id = 6;
DELETE FROM aeropuerto WHERE id = 7;

SELECT
    p.nombre AS pasajero,
    r.asiento,
    v.fecha
FROM reserva r
INNER JOIN pasajero p ON r.pasajero_id = p.id
INNER JOIN vuelo v ON r.vuelo_id = v.id
WHERE v.fecha >= '2026-05-01';


SELECT
    v.id,
    a1.nombre AS origen,
    a2.nombre AS destino,
    av.modelo AS avion
FROM vuelo v
INNER JOIN aeropuerto a1 ON v.origen_id = a1.id
INNER JOIN aeropuerto a2 ON v.destino_id = a2.id
INNER JOIN avion av ON v.avion_id = av.id
WHERE av.capacidad >= 170;
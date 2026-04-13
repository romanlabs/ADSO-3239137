CREATE DATABASE IF NOT EXISTS tienda_online;
USE tienda_online;

CREATE TABLE cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20)
);

CREATE TABLE categoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(150)
);

CREATE TABLE producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    precio DECIMAL(10,2),
    stock INT,
    categoria_id INT,
    FOREIGN KEY (categoria_id) REFERENCES categoria(id)
);

CREATE TABLE pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE,
    cliente_id INT,
    FOREIGN KEY (cliente_id) REFERENCES cliente(id)
);

CREATE TABLE detalle_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    producto_id INT,
    cantidad INT,
    FOREIGN KEY (pedido_id) REFERENCES pedido(id),
    FOREIGN KEY (producto_id) REFERENCES producto(id)
);

INSERT INTO cliente (nombre,email,telefono) VALUES
('Juan Perez','juan@gmail.com','300111111'),
('Maria Lopez','maria@gmail.com','300222222'),
('Carlos Ruiz','carlos@gmail.com','300333333'),
('Ana Torres','ana@gmail.com','300444444'),
('Luis Gomez','luis@gmail.com','300555555');

INSERT INTO categoria (nombre,descripcion) VALUES
('Tecnologia','Productos tecnologicos'),
('Ropa','Ropa y accesorios'),
('Hogar','Productos para casa'),
('Deportes','Articulos deportivos'),
('Libros','Libros y revistas');

INSERT INTO producto (nombre,precio,stock,categoria_id) VALUES
('Laptop',2500,5,1),
('Camisa',40,20,2),
('Silla',120,10,3),
('Balon',30,15,4),
('Libro SQL',60,12,5);

INSERT INTO pedido (fecha,cliente_id) VALUES
('2026-04-01',1),
('2026-04-02',2),
('2026-04-03',3),
('2026-04-04',4),
('2026-04-05',5);

INSERT INTO detalle_pedido (pedido_id,producto_id,cantidad) VALUES
(1,1,1),
(2,2,2),
(3,3,1),
(4,4,3),
(5,5,2);

UPDATE cliente
SET telefono='311999999'
WHERE id=1;

UPDATE producto
SET stock=8
WHERE id=1;

UPDATE categoria
SET descripcion='Productos de tecnologia y accesorios'
WHERE id=1;

UPDATE pedido
SET fecha='2026-04-06'
WHERE id=4;

UPDATE detalle_pedido
SET cantidad=4
WHERE id=4;

DELETE FROM detalle_pedido
WHERE id=5;

DELETE FROM pedido
WHERE id=5;

SELECT * FROM cliente;
SELECT * FROM producto WHERE precio > 50;
SELECT * FROM pedido;
SELECT * FROM categoria WHERE nombre='Tecnologia';

SELECT 
    p.nombre AS producto,
    c.nombre AS categoria
FROM producto p
INNER JOIN categoria c ON p.categoria_id = c.id;

SELECT 
    cl.nombre AS cliente,
    pr.nombre AS producto,
    dp.cantidad
FROM detalle_pedido dp
INNER JOIN pedido pe ON dp.pedido_id = pe.id
INNER JOIN cliente cl ON pe.cliente_id = cl.id
INNER JOIN producto pr ON dp.producto_id = pr.id;
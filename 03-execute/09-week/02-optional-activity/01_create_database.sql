DROP DATABASE IF EXISTS DB_hotel;
CREATE DATABASE DB_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE DB_hotel;

-- =====================================================
-- SISTEMA HOTELERO - SCRIPT NORMALIZADO
-- Base de datos: DB_hotel
-- Motor: MySQL
-- =====================================================

-- =====================================================
-- 1. PERSONAS
-- =====================================================

CREATE TABLE persona (
    id_persona INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento VARCHAR(20) NOT NULL,
    numero_documento VARCHAR(30) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(30),
    correo VARCHAR(100),
    direccion VARCHAR(150),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_persona_documento UNIQUE (tipo_documento, numero_documento),
    CONSTRAINT uq_persona_correo UNIQUE (correo)
);

CREATE TABLE cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT NOT NULL,
    estado VARCHAR(20) DEFAULT 'activo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_cliente_persona UNIQUE (id_persona),

    CONSTRAINT fk_cliente_persona
        FOREIGN KEY (id_persona)
        REFERENCES persona(id_persona)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE empleado (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT NOT NULL,
    cargo VARCHAR(80) NOT NULL,
    salario DECIMAL(10,2),
    estado VARCHAR(20) DEFAULT 'activo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_empleado_persona UNIQUE (id_persona),
    CONSTRAINT chk_empleado_salario CHECK (salario IS NULL OR salario >= 0),

    CONSTRAINT fk_empleado_persona
        FOREIGN KEY (id_persona)
        REFERENCES persona(id_persona)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- =====================================================
-- 2. SEGURIDAD
-- =====================================================

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT NOT NULL,
    nombre_usuario VARCHAR(50) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    estado VARCHAR(20) DEFAULT 'activo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_usuario_persona UNIQUE (id_persona),
    CONSTRAINT uq_usuario_nombre UNIQUE (nombre_usuario),

    CONSTRAINT fk_usuario_persona
        FOREIGN KEY (id_persona)
        REFERENCES persona(id_persona)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL,
    descripcion VARCHAR(150),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_rol_nombre UNIQUE (nombre_rol)
);

CREATE TABLE permiso (
    id_permiso INT AUTO_INCREMENT PRIMARY KEY,
    nombre_permiso VARCHAR(80) NOT NULL,
    descripcion VARCHAR(150),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_permiso_nombre UNIQUE (nombre_permiso)
);

CREATE TABLE usuario_rol (
    id_usuario INT NOT NULL,
    id_rol INT NOT NULL,

    PRIMARY KEY (id_usuario, id_rol),

    CONSTRAINT fk_usuario_rol_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_usuario_rol_rol
        FOREIGN KEY (id_rol)
        REFERENCES rol(id_rol)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE rol_permiso (
    id_rol INT NOT NULL,
    id_permiso INT NOT NULL,

    PRIMARY KEY (id_rol, id_permiso),

    CONSTRAINT fk_rol_permiso_rol
        FOREIGN KEY (id_rol)
        REFERENCES rol(id_rol)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_rol_permiso_permiso
        FOREIGN KEY (id_permiso)
        REFERENCES permiso(id_permiso)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- =====================================================
-- 3. HOTEL, SEDES Y HABITACIONES
-- =====================================================

CREATE TABLE empresa (
    id_empresa INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nit VARCHAR(30) NOT NULL,
    telefono VARCHAR(30),
    correo VARCHAR(100),
    direccion VARCHAR(150),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_empresa_nit UNIQUE (nit)
);

CREATE TABLE sede (
    id_sede INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    ciudad VARCHAR(80),
    direccion VARCHAR(150),
    telefono VARCHAR(30),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_sede_empresa
        FOREIGN KEY (id_empresa)
        REFERENCES empresa(id_empresa)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE tipo_habitacion (
    id_tipo_habitacion INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(150),
    capacidad INT NOT NULL,
    precio_base DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_tipo_habitacion_nombre UNIQUE (nombre),
    CONSTRAINT chk_tipo_habitacion_capacidad CHECK (capacidad > 0),
    CONSTRAINT chk_tipo_habitacion_precio CHECK (precio_base >= 0)
);

CREATE TABLE estado_habitacion (
    id_estado_habitacion INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(150),

    CONSTRAINT uq_estado_habitacion_nombre UNIQUE (nombre)
);

CREATE TABLE habitacion (
    id_habitacion INT AUTO_INCREMENT PRIMARY KEY,
    id_sede INT NOT NULL,
    id_tipo_habitacion INT NOT NULL,
    id_estado_habitacion INT NOT NULL,
    numero VARCHAR(20) NOT NULL,
    piso INT,
    descripcion VARCHAR(150),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_habitacion_sede_numero UNIQUE (id_sede, numero),

    CONSTRAINT fk_habitacion_sede
        FOREIGN KEY (id_sede)
        REFERENCES sede(id_sede)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_habitacion_tipo
        FOREIGN KEY (id_tipo_habitacion)
        REFERENCES tipo_habitacion(id_tipo_habitacion)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_habitacion_estado
        FOREIGN KEY (id_estado_habitacion)
        REFERENCES estado_habitacion(id_estado_habitacion)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- =====================================================
-- 4. RESERVAS Y ESTADÍAS
-- =====================================================

CREATE TABLE reserva (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    fecha_reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado VARCHAR(30) DEFAULT 'pendiente',
    observacion VARCHAR(200),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_reserva_fechas CHECK (fecha_fin > fecha_inicio),

    CONSTRAINT fk_reserva_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES cliente(id_cliente)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE reserva_habitacion (
    id_reserva INT NOT NULL,
    id_habitacion INT NOT NULL,
    precio_noche DECIMAL(10,2) NOT NULL,

    PRIMARY KEY (id_reserva, id_habitacion),

    CONSTRAINT chk_reserva_habitacion_precio CHECK (precio_noche >= 0),

    CONSTRAINT fk_reserva_habitacion_reserva
        FOREIGN KEY (id_reserva)
        REFERENCES reserva(id_reserva)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_reserva_habitacion_habitacion
        FOREIGN KEY (id_habitacion)
        REFERENCES habitacion(id_habitacion)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE estadia (
    id_estadia INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    fecha_checkin DATETIME,
    fecha_checkout DATETIME,
    estado VARCHAR(30) DEFAULT 'activa',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_estadia_reserva UNIQUE (id_reserva),
    CONSTRAINT chk_estadia_fechas CHECK (
        fecha_checkout IS NULL OR fecha_checkin IS NULL OR fecha_checkout > fecha_checkin
    ),

    CONSTRAINT fk_estadia_reserva
        FOREIGN KEY (id_reserva)
        REFERENCES reserva(id_reserva)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE cancelacion_reserva (
    id_cancelacion INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    fecha_cancelacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    motivo VARCHAR(200),
    valor_penalizacion DECIMAL(10,2) DEFAULT 0,

    CONSTRAINT uq_cancelacion_reserva UNIQUE (id_reserva),
    CONSTRAINT chk_cancelacion_penalizacion CHECK (valor_penalizacion >= 0),

    CONSTRAINT fk_cancelacion_reserva
        FOREIGN KEY (id_reserva)
        REFERENCES reserva(id_reserva)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- =====================================================
-- 5. PRODUCTOS, SERVICIOS E INVENTARIO
-- =====================================================

CREATE TABLE proveedor (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nit VARCHAR(30),
    telefono VARCHAR(30),
    correo VARCHAR(100),
    direccion VARCHAR(150),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_proveedor_nit UNIQUE (nit)
);

CREATE TABLE producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(150),
    precio_unitario DECIMAL(10,2) NOT NULL,
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 0,
    estado VARCHAR(20) DEFAULT 'activo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_producto_precio CHECK (precio_unitario >= 0),
    CONSTRAINT chk_producto_stock_actual CHECK (stock_actual >= 0),
    CONSTRAINT chk_producto_stock_minimo CHECK (stock_minimo >= 0)
);

CREATE TABLE servicio (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(150),
    precio DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'activo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_servicio_nombre UNIQUE (nombre),
    CONSTRAINT chk_servicio_precio CHECK (precio >= 0)
);

CREATE TABLE compra (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    fecha_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_compra_total CHECK (total >= 0),

    CONSTRAINT fk_compra_proveedor
        FOREIGN KEY (id_proveedor)
        REFERENCES proveedor(id_proveedor)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE detalle_compra (
    id_detalle_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    CONSTRAINT chk_detalle_compra_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_detalle_compra_valor CHECK (valor_unitario >= 0),
    CONSTRAINT chk_detalle_compra_subtotal CHECK (subtotal >= 0),

    CONSTRAINT fk_detalle_compra_compra
        FOREIGN KEY (id_compra)
        REFERENCES compra(id_compra)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_detalle_compra_producto
        FOREIGN KEY (id_producto)
        REFERENCES producto(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE movimiento_producto (
    id_movimiento_producto INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    tipo_movimiento VARCHAR(30) NOT NULL,
    cantidad INT NOT NULL,
    fecha_movimiento DATETIME DEFAULT CURRENT_TIMESTAMP,
    descripcion VARCHAR(200),

    CONSTRAINT chk_movimiento_producto_cantidad CHECK (cantidad > 0),

    CONSTRAINT fk_movimiento_producto
        FOREIGN KEY (id_producto)
        REFERENCES producto(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- =====================================================
-- 6. FACTURACIÓN Y PAGOS
-- =====================================================

CREATE TABLE factura (
    id_factura INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_estadia INT,
    fecha_factura DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) DEFAULT 0,
    impuesto DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    estado VARCHAR(30) DEFAULT 'pendiente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_factura_subtotal CHECK (subtotal >= 0),
    CONSTRAINT chk_factura_impuesto CHECK (impuesto >= 0),
    CONSTRAINT chk_factura_total CHECK (total >= 0),

    CONSTRAINT fk_factura_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES cliente(id_cliente)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_factura_estadia
        FOREIGN KEY (id_estadia)
        REFERENCES estadia(id_estadia)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

CREATE TABLE detalle_factura (
    id_detalle_factura INT AUTO_INCREMENT PRIMARY KEY,
    id_factura INT NOT NULL,
    tipo_item VARCHAR(20) NOT NULL,
    id_producto INT,
    id_servicio INT,
    descripcion VARCHAR(150) NOT NULL,
    cantidad INT NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    CONSTRAINT chk_detalle_factura_tipo CHECK (tipo_item IN ('producto', 'servicio', 'habitacion')),
    CONSTRAINT chk_detalle_factura_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_detalle_factura_valor CHECK (valor_unitario >= 0),
    CONSTRAINT chk_detalle_factura_subtotal CHECK (subtotal >= 0),

    CONSTRAINT fk_detalle_factura_factura
        FOREIGN KEY (id_factura)
        REFERENCES factura(id_factura)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_detalle_factura_producto
        FOREIGN KEY (id_producto)
        REFERENCES producto(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_detalle_factura_servicio
        FOREIGN KEY (id_servicio)
        REFERENCES servicio(id_servicio)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE pago (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_factura INT NOT NULL,
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    metodo_pago VARCHAR(50) NOT NULL,
    valor_pagado DECIMAL(10,2) NOT NULL,
    referencia VARCHAR(100),

    CONSTRAINT chk_pago_valor CHECK (valor_pagado > 0),

    CONSTRAINT fk_pago_factura
        FOREIGN KEY (id_factura)
        REFERENCES factura(id_factura)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- =====================================================
-- 7. MANTENIMIENTO
-- =====================================================

CREATE TABLE mantenimiento_habitacion (
    id_mantenimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_habitacion INT NOT NULL,
    id_empleado INT,
    tipo_mantenimiento VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200),
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME,
    estado VARCHAR(30) DEFAULT 'pendiente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_mantenimiento_fechas CHECK (
        fecha_fin IS NULL OR fecha_fin > fecha_inicio
    ),

    CONSTRAINT fk_mantenimiento_habitacion
        FOREIGN KEY (id_habitacion)
        REFERENCES habitacion(id_habitacion)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_mantenimiento_empleado
        FOREIGN KEY (id_empleado)
        REFERENCES empleado(id_empleado)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- =====================================================
-- 8. PROMOCIONES, ALERTAS Y FIDELIZACIÓN
-- =====================================================

CREATE TABLE alerta (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200),
    tipo_alerta VARCHAR(50),
    fecha_alerta DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(30) DEFAULT 'pendiente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE promocion (
    id_promocion INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200),
    porcentaje_descuento DECIMAL(5,2),
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado VARCHAR(20) DEFAULT 'activo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_promocion_descuento CHECK (
        porcentaje_descuento IS NULL OR 
        (porcentaje_descuento >= 0 AND porcentaje_descuento <= 100)
    ),
    CONSTRAINT chk_promocion_fechas CHECK (fecha_fin >= fecha_inicio)
);

CREATE TABLE fidelizacion_cliente (
    id_fidelizacion INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    puntos INT DEFAULT 0,
    nivel VARCHAR(50) DEFAULT 'basico',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_fidelizacion_cliente UNIQUE (id_cliente),
    CONSTRAINT chk_fidelizacion_puntos CHECK (puntos >= 0),

    CONSTRAINT fk_fidelizacion_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES cliente(id_cliente)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- =====================================================
-- 9. DATOS INICIALES
-- =====================================================

INSERT INTO estado_habitacion (nombre, descripcion) VALUES
('disponible', 'Habitación disponible para reserva'),
('ocupada', 'Habitación actualmente ocupada'),
('mantenimiento', 'Habitación en mantenimiento'),
('limpieza', 'Habitación en proceso de limpieza'),
('inactiva', 'Habitación fuera de servicio');

INSERT INTO rol (nombre_rol, descripcion) VALUES
('administrador', 'Usuario con acceso total al sistema'),
('recepcionista', 'Usuario encargado de reservas, check-in y check-out'),
('mantenimiento', 'Usuario encargado del mantenimiento de habitaciones'),
('cliente', 'Usuario cliente del hotel');

INSERT INTO permiso (nombre_permiso, descripcion) VALUES
('gestionar_usuarios', 'Permite crear, editar y eliminar usuarios'),
('gestionar_reservas', 'Permite crear y modificar reservas'),
('gestionar_habitaciones', 'Permite administrar habitaciones'),
('gestionar_facturacion', 'Permite administrar facturas y pagos'),
('gestionar_inventario', 'Permite administrar productos e inventario'),
('gestionar_mantenimiento', 'Permite registrar y controlar mantenimiento');

-- =====================================================
-- 10. VISTAS
-- Estas vistas reemplazan tablas que no deberían guardarse
-- como datos fijos, por ejemplo catálogo, disponibilidad
-- y dashboard.
-- =====================================================

CREATE VIEW vista_catalogo_habitaciones AS
SELECT
    h.id_habitacion,
    s.nombre AS sede,
    h.numero,
    h.piso,
    th.nombre AS tipo_habitacion,
    th.capacidad,
    th.precio_base,
    eh.nombre AS estado_habitacion,
    h.descripcion
FROM habitacion h
INNER JOIN sede s 
    ON h.id_sede = s.id_sede
INNER JOIN tipo_habitacion th 
    ON h.id_tipo_habitacion = th.id_tipo_habitacion
INNER JOIN estado_habitacion eh 
    ON h.id_estado_habitacion = eh.id_estado_habitacion;

CREATE VIEW vista_reservas_detalladas AS
SELECT
    r.id_reserva,
    CONCAT(p.nombres, ' ', p.apellidos) AS cliente,
    p.numero_documento,
    r.fecha_reserva,
    r.fecha_inicio,
    r.fecha_fin,
    r.estado AS estado_reserva,
    h.numero AS numero_habitacion,
    th.nombre AS tipo_habitacion,
    rh.precio_noche
FROM reserva r
INNER JOIN cliente c 
    ON r.id_cliente = c.id_cliente
INNER JOIN persona p 
    ON c.id_persona = p.id_persona
INNER JOIN reserva_habitacion rh 
    ON r.id_reserva = rh.id_reserva
INNER JOIN habitacion h 
    ON rh.id_habitacion = h.id_habitacion
INNER JOIN tipo_habitacion th 
    ON h.id_tipo_habitacion = th.id_tipo_habitacion;

CREATE VIEW vista_dashboard_mantenimiento AS
SELECT
    mh.id_mantenimiento,
    h.numero AS numero_habitacion,
    mh.tipo_mantenimiento,
    mh.descripcion,
    mh.fecha_inicio,
    mh.fecha_fin,
    mh.estado,
    CONCAT(p.nombres, ' ', p.apellidos) AS empleado_responsable
FROM mantenimiento_habitacion mh
INNER JOIN habitacion h 
    ON mh.id_habitacion = h.id_habitacion
LEFT JOIN empleado e 
    ON mh.id_empleado = e.id_empleado
LEFT JOIN persona p 
    ON e.id_persona = p.id_persona;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
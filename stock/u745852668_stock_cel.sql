-- Esquema: u745852668_stock_cel
CREATE DATABASE IF NOT EXISTS u745852668_stock_cel;
USE u745852668_stock_cel;

-- Tabla de usuarios para login simple
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

-- Insertar usuario admin (usuario: admin, contraseña: admin123)
INSERT INTO usuarios (username, password_hash) VALUES ('admin', '$2b$12$ohA9.fyfDx7M7hkCV5ySDu/9hKd4h6Ycnx6PlVahJasMlRd72.jUG');

-- Tabla: marcas
CREATE TABLE marcas (
    id_marca INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Tabla: modelos
CREATE TABLE modelos (
    id_modelo INT AUTO_INCREMENT PRIMARY KEY,
    id_marca INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_marca) REFERENCES marcas(id_marca)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Tabla: variantes
CREATE TABLE variantes (
    id_variante INT AUTO_INCREMENT PRIMARY KEY,
    id_modelo INT NOT NULL,
    color VARCHAR(30) NOT NULL,
    almacenamiento INT NOT NULL,
    UNIQUE (id_modelo, color, almacenamiento),
    FOREIGN KEY (id_modelo) REFERENCES modelos(id_modelo)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- Tabla: stock
CREATE TABLE stock (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    id_variante INT NOT NULL,
    cantidad INT DEFAULT 0 CHECK (cantidad >= 0),
    FOREIGN KEY (id_variante) REFERENCES variantes(id_variante)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- Datos de ejemplo
INSERT INTO marcas (nombre) VALUES ('Apple'), ('Samsung');

INSERT INTO modelos (id_marca, nombre)
VALUES 
(1, 'iPhone 15 Pro'),
(1, 'iPhone 14'),
(2, 'Galaxy S24');

INSERT INTO variantes (id_modelo, color, almacenamiento)
VALUES 
(1, 'Negro espacial', 256),
(1, 'Plateado', 512),
(2, 'Azul', 128),
(3, 'Negro', 256);

INSERT INTO stock (id_variante, cantidad)
VALUES
(1, 5),
(2, 2),
(3, 4),
(4, 6);

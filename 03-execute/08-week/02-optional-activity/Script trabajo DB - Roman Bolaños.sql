-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema carrito_compra
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema carrito_compra
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `carrito_compra` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
USE `carrito_compra` ;

-- -----------------------------------------------------
-- Table `carrito_compra`.`continent`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`continent` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`country`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`country` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `continent_id` INT NULL DEFAULT NULL,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `iso_code` VARCHAR(10) NULL DEFAULT NULL,
  `phone_code` VARCHAR(10) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `continent_id` (`continent_id` ASC) VISIBLE,
  CONSTRAINT `country_ibfk_1`
    FOREIGN KEY (`continent_id`)
    REFERENCES `carrito_compra`.`continent` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`department`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`department` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `country_id` INT NULL DEFAULT NULL,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `country_id` (`country_id` ASC) VISIBLE,
  CONSTRAINT `department_ibfk_1`
    FOREIGN KEY (`country_id`)
    REFERENCES `carrito_compra`.`country` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`city`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`city` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `department_id` INT NULL DEFAULT NULL,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `postal_code` VARCHAR(20) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `department_id` (`department_id` ASC) VISIBLE,
  CONSTRAINT `city_ibfk_1`
    FOREIGN KEY (`department_id`)
    REFERENCES `carrito_compra`.`department` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`address`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`address` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `city_id` INT NULL DEFAULT NULL,
  `line_1` VARCHAR(150) NULL DEFAULT NULL,
  `line_2` VARCHAR(150) NULL DEFAULT NULL,
  `reference` VARCHAR(255) NULL DEFAULT NULL,
  `zip_code` VARCHAR(20) NULL DEFAULT NULL,
  `latitude` DECIMAL(10,7) NULL DEFAULT NULL,
  `longitude` DECIMAL(10,7) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `city_id` (`city_id` ASC) VISIBLE,
  CONSTRAINT `address_ibfk_1`
    FOREIGN KEY (`city_id`)
    REFERENCES `carrito_compra`.`city` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`module`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`module` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `route` VARCHAR(150) NULL DEFAULT NULL,
  `icon` VARCHAR(100) NULL DEFAULT NULL,
  `order_index` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`app_view`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`app_view` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `module_id` INT NULL DEFAULT NULL,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `route` VARCHAR(150) NULL DEFAULT NULL,
  `order_index` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `module_id` (`module_id` ASC) VISIBLE,
  CONSTRAINT `app_view_ibfk_1`
    FOREIGN KEY (`module_id`)
    REFERENCES `carrito_compra`.`module` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`brand`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`brand` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`type_document`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`type_document` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`person`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`person` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `type_document_id` INT NULL DEFAULT NULL,
  `document_number` VARCHAR(30) NULL DEFAULT NULL,
  `first_name` VARCHAR(100) NULL DEFAULT NULL,
  `middle_name` VARCHAR(100) NULL DEFAULT NULL,
  `last_name` VARCHAR(100) NULL DEFAULT NULL,
  `second_last_name` VARCHAR(100) NULL DEFAULT NULL,
  `email` VARCHAR(150) NULL DEFAULT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `birth_date` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `type_document_id` (`type_document_id` ASC) VISIBLE,
  CONSTRAINT `person_ibfk_1`
    FOREIGN KEY (`type_document_id`)
    REFERENCES `carrito_compra`.`type_document` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`customer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`customer` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `person_id` INT NULL DEFAULT NULL,
  `customer_code` VARCHAR(30) NULL DEFAULT NULL,
  `loyalty_points` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `person_id` (`person_id` ASC) VISIBLE,
  CONSTRAINT `customer_ibfk_1`
    FOREIGN KEY (`person_id`)
    REFERENCES `carrito_compra`.`person` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`cart`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`cart` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NULL DEFAULT NULL,
  `opened_at` DATETIME NULL DEFAULT NULL,
  `closed_at` DATETIME NULL DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `total_items` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `customer_id` (`customer_id` ASC) VISIBLE,
  CONSTRAINT `cart_ibfk_1`
    FOREIGN KEY (`customer_id`)
    REFERENCES `carrito_compra`.`customer` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`category`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`category` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `parent_category_id` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `parent_category_id` (`parent_category_id` ASC) VISIBLE,
  CONSTRAINT `category_ibfk_1`
    FOREIGN KEY (`parent_category_id`)
    REFERENCES `carrito_compra`.`category` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`product` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `category_id` INT NULL DEFAULT NULL,
  `brand_id` INT NULL DEFAULT NULL,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `sku` VARCHAR(50) NULL DEFAULT NULL,
  `barcode` VARCHAR(50) NULL DEFAULT NULL,
  `price` DECIMAL(10,2) NULL DEFAULT NULL,
  `cost` DECIMAL(10,2) NULL DEFAULT NULL,
  `weight` DECIMAL(10,2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `category_id` (`category_id` ASC) VISIBLE,
  INDEX `brand_id` (`brand_id` ASC) VISIBLE,
  CONSTRAINT `product_ibfk_1`
    FOREIGN KEY (`category_id`)
    REFERENCES `carrito_compra`.`category` (`id`),
  CONSTRAINT `product_ibfk_2`
    FOREIGN KEY (`brand_id`)
    REFERENCES `carrito_compra`.`brand` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`cart_item`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`cart_item` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `cart_id` INT NULL DEFAULT NULL,
  `product_id` INT NULL DEFAULT NULL,
  `quantity` INT NULL DEFAULT NULL,
  `unit_price` DECIMAL(10,2) NULL DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `cart_id` (`cart_id` ASC) VISIBLE,
  INDEX `product_id` (`product_id` ASC) VISIBLE,
  CONSTRAINT `cart_item_ibfk_1`
    FOREIGN KEY (`cart_id`)
    REFERENCES `carrito_compra`.`cart` (`id`),
  CONSTRAINT `cart_item_ibfk_2`
    FOREIGN KEY (`product_id`)
    REFERENCES `carrito_compra`.`product` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`customer_address`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`customer_address` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NULL DEFAULT NULL,
  `address_id` INT NULL DEFAULT NULL,
  `is_default` TINYINT(1) NULL DEFAULT NULL,
  `address_type` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `customer_id` (`customer_id` ASC) VISIBLE,
  INDEX `address_id` (`address_id` ASC) VISIBLE,
  CONSTRAINT `customer_address_ibfk_1`
    FOREIGN KEY (`customer_id`)
    REFERENCES `carrito_compra`.`customer` (`id`),
  CONSTRAINT `customer_address_ibfk_2`
    FOREIGN KEY (`address_id`)
    REFERENCES `carrito_compra`.`address` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`file`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`file` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `file_name` VARCHAR(150) NULL DEFAULT NULL,
  `file_path` VARCHAR(255) NULL DEFAULT NULL,
  `file_extension` VARCHAR(20) NULL DEFAULT NULL,
  `mime_type` VARCHAR(100) NULL DEFAULT NULL,
  `size` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`inventory_movement`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`inventory_movement` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `product_id` INT NULL DEFAULT NULL,
  `movement_type` VARCHAR(50) NULL DEFAULT NULL,
  `quantity` INT NULL DEFAULT NULL,
  `unit_cost` DECIMAL(10,2) NULL DEFAULT NULL,
  `reference_type` VARCHAR(50) NULL DEFAULT NULL,
  `reference_id` INT NULL DEFAULT NULL,
  `movement_date` DATETIME NULL DEFAULT NULL,
  `note` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `product_id` (`product_id` ASC) VISIBLE,
  CONSTRAINT `inventory_movement_ibfk_1`
    FOREIGN KEY (`product_id`)
    REFERENCES `carrito_compra`.`product` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`order_status`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`order_status` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`order`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`order` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NULL DEFAULT NULL,
  `order_status_id` INT NULL DEFAULT NULL,
  `billing_address_id` INT NULL DEFAULT NULL,
  `shipping_address_id` INT NULL DEFAULT NULL,
  `order_number` VARCHAR(50) NULL DEFAULT NULL,
  `order_date` DATETIME NULL DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NULL DEFAULT NULL,
  `tax_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `discount_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `shipping_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `note` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `customer_id` (`customer_id` ASC) VISIBLE,
  INDEX `order_status_id` (`order_status_id` ASC) VISIBLE,
  INDEX `billing_address_id` (`billing_address_id` ASC) VISIBLE,
  INDEX `shipping_address_id` (`shipping_address_id` ASC) VISIBLE,
  CONSTRAINT `order_ibfk_1`
    FOREIGN KEY (`customer_id`)
    REFERENCES `carrito_compra`.`customer` (`id`),
  CONSTRAINT `order_ibfk_2`
    FOREIGN KEY (`order_status_id`)
    REFERENCES `carrito_compra`.`order_status` (`id`),
  CONSTRAINT `order_ibfk_3`
    FOREIGN KEY (`billing_address_id`)
    REFERENCES `carrito_compra`.`address` (`id`),
  CONSTRAINT `order_ibfk_4`
    FOREIGN KEY (`shipping_address_id`)
    REFERENCES `carrito_compra`.`address` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`invoice`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`invoice` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NULL DEFAULT NULL,
  `invoice_number` VARCHAR(50) NULL DEFAULT NULL,
  `issue_date` DATETIME NULL DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NULL DEFAULT NULL,
  `tax_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `order_id` (`order_id` ASC) VISIBLE,
  CONSTRAINT `invoice_ibfk_1`
    FOREIGN KEY (`order_id`)
    REFERENCES `carrito_compra`.`order` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`module_view`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`module_view` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `module_id` INT NULL DEFAULT NULL,
  `app_view_id` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `module_id` (`module_id` ASC) VISIBLE,
  INDEX `app_view_id` (`app_view_id` ASC) VISIBLE,
  CONSTRAINT `module_view_ibfk_1`
    FOREIGN KEY (`module_id`)
    REFERENCES `carrito_compra`.`module` (`id`),
  CONSTRAINT `module_view_ibfk_2`
    FOREIGN KEY (`app_view_id`)
    REFERENCES `carrito_compra`.`app_view` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`order_item`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`order_item` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NULL DEFAULT NULL,
  `product_id` INT NULL DEFAULT NULL,
  `quantity` INT NULL DEFAULT NULL,
  `unit_price` DECIMAL(10,2) NULL DEFAULT NULL,
  `tax_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `discount_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NULL DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `order_id` (`order_id` ASC) VISIBLE,
  INDEX `product_id` (`product_id` ASC) VISIBLE,
  CONSTRAINT `order_item_ibfk_1`
    FOREIGN KEY (`order_id`)
    REFERENCES `carrito_compra`.`order` (`id`),
  CONSTRAINT `order_item_ibfk_2`
    FOREIGN KEY (`product_id`)
    REFERENCES `carrito_compra`.`product` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`payment_method`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`payment_method` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`payment_status`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`payment_status` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`payment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`payment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NULL DEFAULT NULL,
  `payment_method_id` INT NULL DEFAULT NULL,
  `payment_status_id` INT NULL DEFAULT NULL,
  `payment_date` DATETIME NULL DEFAULT NULL,
  `reference_number` VARCHAR(50) NULL DEFAULT NULL,
  `amount` DECIMAL(10,2) NULL DEFAULT NULL,
  `authorization_code` VARCHAR(50) NULL DEFAULT NULL,
  `transaction_number` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `order_id` (`order_id` ASC) VISIBLE,
  INDEX `payment_method_id` (`payment_method_id` ASC) VISIBLE,
  INDEX `payment_status_id` (`payment_status_id` ASC) VISIBLE,
  CONSTRAINT `payment_ibfk_1`
    FOREIGN KEY (`order_id`)
    REFERENCES `carrito_compra`.`order` (`id`),
  CONSTRAINT `payment_ibfk_2`
    FOREIGN KEY (`payment_method_id`)
    REFERENCES `carrito_compra`.`payment_method` (`id`),
  CONSTRAINT `payment_ibfk_3`
    FOREIGN KEY (`payment_status_id`)
    REFERENCES `carrito_compra`.`payment_status` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`product_image`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`product_image` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `product_id` INT NULL DEFAULT NULL,
  `file_id` INT NULL DEFAULT NULL,
  `url` VARCHAR(255) NULL DEFAULT NULL,
  `is_main` TINYINT(1) NULL DEFAULT NULL,
  `order_index` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `product_id` (`product_id` ASC) VISIBLE,
  INDEX `file_id` (`file_id` ASC) VISIBLE,
  CONSTRAINT `product_image_ibfk_1`
    FOREIGN KEY (`product_id`)
    REFERENCES `carrito_compra`.`product` (`id`),
  CONSTRAINT `product_image_ibfk_2`
    FOREIGN KEY (`file_id`)
    REFERENCES `carrito_compra`.`file` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`role`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`role` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`role_module`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`role_module` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `role_id` INT NULL DEFAULT NULL,
  `module_id` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `role_id` (`role_id` ASC) VISIBLE,
  INDEX `module_id` (`module_id` ASC) VISIBLE,
  CONSTRAINT `role_module_ibfk_1`
    FOREIGN KEY (`role_id`)
    REFERENCES `carrito_compra`.`role` (`id`),
  CONSTRAINT `role_module_ibfk_2`
    FOREIGN KEY (`module_id`)
    REFERENCES `carrito_compra`.`module` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`shipment_status`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`shipment_status` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`shipment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`shipment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NULL DEFAULT NULL,
  `shipment_status_id` INT NULL DEFAULT NULL,
  `address_id` INT NULL DEFAULT NULL,
  `shipment_date` DATETIME NULL DEFAULT NULL,
  `delivered_date` DATETIME NULL DEFAULT NULL,
  `tracking_number` VARCHAR(50) NULL DEFAULT NULL,
  `carrier_name` VARCHAR(100) NULL DEFAULT NULL,
  `shipping_cost` DECIMAL(10,2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `order_id` (`order_id` ASC) VISIBLE,
  INDEX `shipment_status_id` (`shipment_status_id` ASC) VISIBLE,
  INDEX `address_id` (`address_id` ASC) VISIBLE,
  CONSTRAINT `shipment_ibfk_1`
    FOREIGN KEY (`order_id`)
    REFERENCES `carrito_compra`.`order` (`id`),
  CONSTRAINT `shipment_ibfk_2`
    FOREIGN KEY (`shipment_status_id`)
    REFERENCES `carrito_compra`.`shipment_status` (`id`),
  CONSTRAINT `shipment_ibfk_3`
    FOREIGN KEY (`address_id`)
    REFERENCES `carrito_compra`.`address` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`status`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`status` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `domain_name` VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`stock`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`stock` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `product_id` INT NULL DEFAULT NULL,
  `quantity_available` INT NULL DEFAULT NULL,
  `quantity_reserved` INT NULL DEFAULT NULL,
  `reorder_level` INT NULL DEFAULT NULL,
  `max_stock` INT NULL DEFAULT NULL,
  `min_stock` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `product_id` (`product_id` ASC) VISIBLE,
  CONSTRAINT `stock_ibfk_1`
    FOREIGN KEY (`product_id`)
    REFERENCES `carrito_compra`.`product` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`supplier`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`supplier` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `person_id` INT NULL DEFAULT NULL,
  `supplier_code` VARCHAR(30) NULL DEFAULT NULL,
  `contact_name` VARCHAR(100) NULL DEFAULT NULL,
  `contact_email` VARCHAR(150) NULL DEFAULT NULL,
  `contact_phone` VARCHAR(30) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `person_id` (`person_id` ASC) VISIBLE,
  CONSTRAINT `supplier_ibfk_1`
    FOREIGN KEY (`person_id`)
    REFERENCES `carrito_compra`.`person` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`supplier_address`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`supplier_address` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `supplier_id` INT NULL DEFAULT NULL,
  `address_id` INT NULL DEFAULT NULL,
  `is_default` TINYINT(1) NULL DEFAULT NULL,
  `address_type` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `supplier_id` (`supplier_id` ASC) VISIBLE,
  INDEX `address_id` (`address_id` ASC) VISIBLE,
  CONSTRAINT `supplier_address_ibfk_1`
    FOREIGN KEY (`supplier_id`)
    REFERENCES `carrito_compra`.`supplier` (`id`),
  CONSTRAINT `supplier_address_ibfk_2`
    FOREIGN KEY (`address_id`)
    REFERENCES `carrito_compra`.`address` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`supplier_product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`supplier_product` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `supplier_id` INT NULL DEFAULT NULL,
  `product_id` INT NULL DEFAULT NULL,
  `supplier_product_code` VARCHAR(50) NULL DEFAULT NULL,
  `purchase_price` DECIMAL(10,2) NULL DEFAULT NULL,
  `lead_time_days` INT NULL DEFAULT NULL,
  `is_primary` TINYINT(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `supplier_id` (`supplier_id` ASC) VISIBLE,
  INDEX `product_id` (`product_id` ASC) VISIBLE,
  CONSTRAINT `supplier_product_ibfk_1`
    FOREIGN KEY (`supplier_id`)
    REFERENCES `carrito_compra`.`supplier` (`id`),
  CONSTRAINT `supplier_product_ibfk_2`
    FOREIGN KEY (`product_id`)
    REFERENCES `carrito_compra`.`product` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`user_account`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`user_account` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `person_id` INT NULL DEFAULT NULL,
  `username` VARCHAR(50) NULL DEFAULT NULL,
  `password_hash` VARCHAR(255) NULL DEFAULT NULL,
  `last_login_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `person_id` (`person_id` ASC) VISIBLE,
  CONSTRAINT `user_account_ibfk_1`
    FOREIGN KEY (`person_id`)
    REFERENCES `carrito_compra`.`person` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `carrito_compra`.`user_role`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito_compra`.`user_role` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_account_id` INT NULL DEFAULT NULL,
  `role_id` INT NULL DEFAULT NULL,
  `start_date` DATE NULL DEFAULT NULL,
  `end_date` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `user_account_id` (`user_account_id` ASC) VISIBLE,
  INDEX `role_id` (`role_id` ASC) VISIBLE,
  CONSTRAINT `user_role_ibfk_1`
    FOREIGN KEY (`user_account_id`)
    REFERENCES `carrito_compra`.`user_account` (`id`),
  CONSTRAINT `user_role_ibfk_2`
    FOREIGN KEY (`role_id`)
    REFERENCES `carrito_compra`.`role` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

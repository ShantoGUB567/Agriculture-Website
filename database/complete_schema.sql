-- FarmKnowledge Hub Database Schema
-- This file contains all SQL statements needed to create the database structure
-- according to the ER diagram

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `farmknowledge_db`;
USE `farmknowledge_db`;

-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `products`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `seller_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `image_url` VARCHAR(255) NULL,
  `status` ENUM('available', 'sold', 'inactive') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_products_users_idx` (`seller_id` ASC),
  INDEX `idx_product_category` (`category` ASC),
  INDEX `idx_product_status` (`status` ASC),
  CONSTRAINT `fk_products_users`
    FOREIGN KEY (`seller_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `wishlist`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_product_unique` (`user_id` ASC, `product_id` ASC),
  INDEX `fk_wishlist_products_idx` (`product_id` ASC),
  CONSTRAINT `fk_wishlist_users`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_wishlist_products`
    FOREIGN KEY (`product_id`)
    REFERENCES `products` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `messages`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sender` (`sender_id` ASC),
  INDEX `idx_receiver` (`receiver_id` ASC),
  CONSTRAINT `fk_messages_users_sender`
    FOREIGN KEY (`sender_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_messages_users_receiver`
    FOREIGN KEY (`receiver_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `learning_categories`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learning_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `image_url` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `slug_UNIQUE` (`slug` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `learning_articles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learning_articles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `category_id` INT NOT NULL,
  `author_id` INT NOT NULL,
  `featured_image` VARCHAR(255) NULL,
  `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `slug_UNIQUE` (`slug` ASC),
  INDEX `idx_category` (`category_id` ASC),
  INDEX `idx_author` (`author_id` ASC),
  INDEX `idx_status` (`status` ASC),
  CONSTRAINT `fk_articles_categories`
    FOREIGN KEY (`category_id`)
    REFERENCES `learning_categories` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_articles_users`
    FOREIGN KEY (`author_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Insert default admin user
-- -----------------------------------------------------
INSERT INTO `users` (`username`, `email`, `password`, `phone_number`, `address`, `created_at`)
VALUES ('admin', 'admin@farmknowledge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123-456-7890', 'FarmKnowledge Central Office', NOW());
-- Password is 'password'

-- -----------------------------------------------------
-- Insert sample learning categories
-- -----------------------------------------------------
INSERT INTO `learning_categories` (`name`, `slug`, `description`, `image_url`) VALUES
('Soil Management', 'soil-management', 'Learn about soil health, nutrients, and sustainable management practices.', 'Image/Soil Management.jpg'),
('Organic Farming', 'organic-farming', 'Techniques and principles of organic farming methods.', 'Image/Organic Farming.jpg'),
('Water Management', 'water-management', 'Efficient water use strategies for agriculture.', 'Image/Water Management.jpg'),
('Pest Control', 'pest-control', 'Natural and integrated pest management approaches.', 'Image/Pest Control.jpg'),
('Hydroponics', 'hydroponics', 'Soilless growing techniques and systems.', 'Image/Hydroponics.jpg');

-- -----------------------------------------------------
-- Insert sample articles
-- -----------------------------------------------------
INSERT INTO `learning_articles` (`title`, `slug`, `content`, `category_id`, `author_id`, `featured_image`, `status`, `created_at`, `updated_at`) VALUES
('Understanding Soil Testing', 'understanding-soil-testing', 'This article explains the importance of regular soil testing and how to interpret the results for better crop management...', 1, 1, 'Image/article/Understanding Soil Testing.jpg', 'published', NOW(), NOW()),
('Water Conservation in Agriculture', 'water-conservation-in-agriculture', 'Learn effective techniques to reduce water usage while maintaining healthy crops...', 3, 1, 'Image/article/Water Conservation in Agriculture.jpg', 'published', NOW(), NOW()),
('Natural Pest Control Methods', 'natural-pest-control-methods', 'Discover organic ways to manage pests without harmful chemicals...', 4, 1, 'Image/article/Natural Pest Control Methods.webp', 'published', NOW(), NOW()),
('Hydroponic Growing Guide', 'hydroponic-growing-guide', 'A comprehensive guide to setting up and maintaining hydroponic systems...', 5, 1, 'Image/article/Hydroponic Growing Guide.jpg', 'published', NOW(), NOW()),
('Beneficial Insects to Control Pests', 'beneficial-insects-to-control-pests', 'How to attract and use beneficial insects as a natural pest management strategy...', 4, 1, 'Image/article/beneficial_pest_to_keep_pest_away.webp', 'published', NOW(), NOW()),
('Smart Water Management with IoT', 'smart-water-management-with-iot', 'Using IoT technologies to optimize irrigation and water management on farms...', 3, 1, 'Image/article/Smart Water Management with IoT.jpg', 'draft', NOW(), NOW());

-- Sample products would usually be created by users through the interface,
-- but here's an example of how you might insert a product:
-- INSERT INTO `products` (seller_id, name, description, price, category, image_url, status)
-- VALUES (1, 'Organic Tomato Seeds', 'Heirloom variety, non-GMO seeds for growing organic tomatoes', 4.99, 'Seeds', 'Image/market-product-IMG/Organic Tomato Seeds.jpg', 'available');

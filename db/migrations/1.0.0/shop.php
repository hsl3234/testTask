<?php

use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Схема (categories → products → api_tokens) и сиды. Одна миграция, чтобы
 * порядок наката не зависел от перебора файлов в каталоге.
 */
class ShopMigration_100 extends Migration
{
    public function up(): void
    {
        $c = $this->getConnection();

        $c->execute(
            <<<SQL
            CREATE TABLE `categories` (
                `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `parent_id`  BIGINT UNSIGNED NULL,
                `name`       VARCHAR(191)    NOT NULL,
                `path`       VARCHAR(512)    NOT NULL DEFAULT '/',
                `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_categories_parent` (`parent_id`),
                KEY `idx_categories_path`   (`path`),
                CONSTRAINT `fk_categories_parent`
                    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $c->execute(
            <<<SQL
            CREATE TABLE `products` (
                `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `category_id` BIGINT UNSIGNED NOT NULL,
                `name`        VARCHAR(255)    NOT NULL,
                `content`     TEXT            NULL,
                `price`       DECIMAL(12, 2)  NOT NULL,
                `in_stock`    TINYINT(1)      NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_products_cat_stock_id` (`category_id`, `in_stock`, `id`),
                KEY `idx_products_stock_id`     (`in_stock`, `id`),
                CONSTRAINT `fk_products_category`
                    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $c->execute(
            <<<SQL
            CREATE TABLE `api_tokens` (
                `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `token`      VARCHAR(128)    NOT NULL,
                `name`       VARCHAR(191)    NOT NULL DEFAULT 'default',
                `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_api_tokens_token` (`token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $c->execute("INSERT INTO `api_tokens` (`token`, `name`) VALUES ('demo-token-please-change', 'demo')");
        $c->execute(<<<SQL
            INSERT INTO `categories` (`id`, `parent_id`, `name`, `path`) VALUES
                (1, NULL, 'Electronics',       '/1/'),
                (2, 1,    'Computers',         '/1/2/'),
                (3, 2,    'Laptops',           '/1/2/3/'),
                (4, 2,    'Desktops',            '/1/2/4/'),
                (5, 1,    'Phones',            '/1/5/'),
                (6, NULL, 'Home',              '/6/'),
                (7, 6,    'Kitchen',           '/6/7/')
            SQL);
        $c->execute(<<<SQL
            INSERT INTO `products` (`category_id`, `name`, `content`, `price`, `in_stock`) VALUES
                (3, 'ThinkPad X1',       '14" business laptop',     1899.00, 1),
                (3, 'MacBook Air 13',    'M-series lightweight',    1299.99, 1),
                (3, 'Budget Laptop',     'Entry-level notebook',     499.50, 0),
                (4, 'Office Desktop',    'Pre-built office PC',      899.00, 1),
                (5, 'Phone Pro',         'Flagship smartphone',      999.00, 1),
                (5, 'Phone Lite',        'Compact phone',            299.00, 0),
                (7, 'Blender 500W',      'Kitchen blender',           59.90, 1),
                (7, 'Coffee Maker',      'Drip coffee machine',      129.00, 1)
            SQL);
    }

    public function down(): void
    {
        $c = $this->getConnection();
        $c->execute('SET FOREIGN_KEY_CHECKS=0');
        $c->execute('DROP TABLE IF EXISTS `products`');
        $c->execute('DROP TABLE IF EXISTS `categories`');
        $c->execute('DROP TABLE IF EXISTS `api_tokens`');
        $c->execute('SET FOREIGN_KEY_CHECKS=1');
    }
}

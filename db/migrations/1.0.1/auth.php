<?php

use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Авторизация: пользователи + refresh-токены + расширение api_tokens.
 *
 * Схема: users → refresh_tokens (CASCADE), users → api_tokens (SET NULL,
 * чтобы статические демо-токены продолжали работать). access-токен —
 * запись в api_tokens с user_id и expires_at; refresh — отдельная таблица
 * для одноразовой ротации.
 */
class AuthMigration_101 extends Migration
{
    public function up(): void
    {
        $c = $this->getConnection();

        $c->execute(
            <<<SQL
            CREATE TABLE `users` (
                `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `login`         VARCHAR(64)     NOT NULL,
                `password_hash` VARCHAR(255)    NOT NULL,
                `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_users_login` (`login`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $c->execute(
            <<<SQL
            ALTER TABLE `api_tokens`
                ADD COLUMN `user_id`    BIGINT UNSIGNED NULL AFTER `id`,
                ADD COLUMN `expires_at` TIMESTAMP       NULL DEFAULT NULL AFTER `name`,
                ADD KEY `idx_api_tokens_user` (`user_id`),
                ADD KEY `idx_api_tokens_expires` (`expires_at`),
                ADD CONSTRAINT `fk_api_tokens_user`
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            SQL,
        );

        $c->execute(
            <<<SQL
            CREATE TABLE `refresh_tokens` (
                `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`    BIGINT UNSIGNED NOT NULL,
                `token`      VARCHAR(128)    NOT NULL,
                `expires_at` TIMESTAMP       NOT NULL,
                `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_refresh_tokens_token` (`token`),
                KEY `idx_refresh_tokens_user` (`user_id`),
                KEY `idx_refresh_tokens_expires` (`expires_at`),
                CONSTRAINT `fk_refresh_tokens_user`
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $login = (string) (getenv('ADMIN_LOGIN') ?: 'admin');
        $password = (string) (getenv('ADMIN_PASSWORD') ?: 'admin');
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $c->execute(
            'INSERT INTO `users` (`login`, `password_hash`) VALUES (?, ?)',
            [$login, $hash],
        );
    }

    public function down(): void
    {
        $c = $this->getConnection();
        $c->execute('SET FOREIGN_KEY_CHECKS=0');
        $c->execute('DROP TABLE IF EXISTS `refresh_tokens`');
        $c->execute(
            <<<SQL
            ALTER TABLE `api_tokens`
                DROP FOREIGN KEY `fk_api_tokens_user`,
                DROP KEY `idx_api_tokens_user`,
                DROP KEY `idx_api_tokens_expires`,
                DROP COLUMN `user_id`,
                DROP COLUMN `expires_at`
            SQL,
        );
        $c->execute('DROP TABLE IF EXISTS `users`');
        $c->execute('SET FOREIGN_KEY_CHECKS=1');
    }
}

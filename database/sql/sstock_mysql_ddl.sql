-- Stoku (sstock) - skema MySQL 8 (DDL lengkap: tabel, index, foreign key), dibuat 2026-10-29.
-- Dihasilkan dengan menjalankan SEMUA migrasi di database MySQL sementara lalu SHOW CREATE TABLE (tanpa data, tanpa AUTO_INCREMENT=n); urutan tabel mengikuti urutan migrasi sehingga foreign key aman dimuat berurutan.
-- Sumber kebenaran skema tetap migrasi (database/migrations): berkas ini ketinggalan lagi setiap ada migrasi baru. Tabel migrations dibuat oleh php artisan migrate.
-- Isi geo_* TIDAK disertakan: php artisan sstock:import-geo menyalinnya dari tmerp.

CREATE DATABASE IF NOT EXISTS sstock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sstock;

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `constants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `constants_key_unique` (`key`),
  KEY `constants_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geo_city` (
  `id_city` bigint unsigned NOT NULL,
  `region_id` bigint unsigned NOT NULL,
  `city_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activate` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_city`),
  KEY `geo_city_region_id_index` (`region_id`),
  KEY `geo_city_city_name_index` (`city_name`),
  KEY `geo_city_post_code_index` (`post_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geo_country` (
  `id_country` bigint unsigned NOT NULL,
  `country_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `call_country_no` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activate` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geo_district` (
  `id_district` bigint unsigned NOT NULL,
  `city_id` bigint unsigned NOT NULL,
  `district_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activate` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_district`),
  KEY `geo_district_city_id_index` (`city_id`),
  KEY `geo_district_district_name_index` (`district_name`),
  KEY `geo_district_post_code_index` (`post_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geo_region` (
  `id_region` bigint unsigned NOT NULL,
  `country_id` bigint unsigned NOT NULL,
  `region_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_region`),
  KEY `geo_region_country_id_index` (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geo_village` (
  `id_village` bigint unsigned NOT NULL,
  `district_id` bigint unsigned NOT NULL,
  `village_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `village_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activate` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_village`),
  KEY `geo_village_district_id_index` (`district_id`),
  KEY `geo_village_village_name_index` (`village_name`),
  KEY `geo_village_post_code_index` (`post_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reorder_point` decimal(15,3) NOT NULL DEFAULT '0.000',
  `reorder_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  `lead_time_days` smallint unsigned NOT NULL DEFAULT '0',
  `tracks_expiry` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_code_unique` (`code`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_name_index` (`name`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recipients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipients_department_id_foreign` (`department_id`),
  CONSTRAINT `recipients_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_mobile` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_level` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_id` bigint unsigned DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'count',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_groups_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'data_entry',
  `group_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_group_id_foreign` (`group_id`),
  CONSTRAINT `users_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reorder_point` decimal(15,3) NOT NULL DEFAULT '0.000',
  `reorder_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  `lead_time_days` smallint unsigned NOT NULL DEFAULT '0',
  `stock_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  `avg_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `items_code_unique` (`code`),
  UNIQUE KEY `items_product_id_expiry_date_unique` (`product_id`,`expiry_date`),
  KEY `items_name_index` (`name`),
  KEY `items_category_id_index` (`category_id`),
  KEY `items_unit_foreign` (`unit`),
  KEY `items_expiry_date_index` (`expiry_date`),
  CONSTRAINT `items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `items_unit_foreign` FOREIGN KEY (`unit`) REFERENCES `units` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `items_stock_qty_nonneg` CHECK ((`stock_qty` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factor` decimal(18,6) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_units_product_id_unit_unique` (`product_id`,`unit`),
  KEY `product_units_created_by_foreign` (`created_by`),
  KEY `product_units_updated_by_foreign` (`updated_by`),
  KEY `product_units_unit_foreign` (`unit`),
  CONSTRAINT `product_units_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_units_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `product_units_unit_foreign` FOREIGN KEY (`unit`) REFERENCES `units` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `product_units_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_units_factor_positive` CHECK ((`factor` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doc_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doc_at` datetime NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `ref_no` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_id` bigint unsigned DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancel_requested_by` bigint unsigned DEFAULT NULL,
  `cancel_requested_at` datetime DEFAULT NULL,
  `reversal_of_document_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_documents_doc_no_unique` (`doc_no`),
  KEY `stock_documents_supplier_id_foreign` (`supplier_id`),
  KEY `stock_documents_created_by_foreign` (`created_by`),
  KEY `stock_documents_approved_by_foreign` (`approved_by`),
  KEY `stock_documents_type_doc_at_index` (`type`,`doc_at`),
  KEY `stock_documents_status_index` (`status`),
  KEY `stock_documents_department_id_foreign` (`department_id`),
  KEY `stock_documents_recipient_id_foreign` (`recipient_id`),
  KEY `stock_documents_confirmed_by_foreign` (`confirmed_by`),
  KEY `stock_documents_cancelled_by_foreign` (`cancelled_by`),
  KEY `stock_documents_cancel_requested_by_foreign` (`cancel_requested_by`),
  KEY `stock_documents_reversal_of_document_id_foreign` (`reversal_of_document_id`),
  CONSTRAINT `stock_documents_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_documents_cancel_requested_by_foreign` FOREIGN KEY (`cancel_requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_documents_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_documents_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_documents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_documents_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_documents_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `recipients` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_documents_reversal_of_document_id_foreign` FOREIGN KEY (`reversal_of_document_id`) REFERENCES `stock_documents` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_documents_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `stock_document_id` bigint unsigned NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moved_at` datetime NOT NULL,
  `qty` decimal(15,3) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL,
  `balance_qty` decimal(15,3) NOT NULL,
  `balance_avg_cost` decimal(15,4) NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reversal_of` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_stock_document_id_foreign` (`stock_document_id`),
  KEY `stock_movements_user_id_foreign` (`user_id`),
  KEY `stock_movements_item_id_moved_at_id_index` (`item_id`,`moved_at`,`id`),
  KEY `stock_movements_type_moved_at_index` (`type`,`moved_at`),
  KEY `stock_movements_reversal_of_foreign` (`reversal_of`),
  CONSTRAINT `stock_movements_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_movements_reversal_of_foreign` FOREIGN KEY (`reversal_of`) REFERENCES `stock_movements` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_movements_stock_document_id_foreign` FOREIGN KEY (`stock_document_id`) REFERENCES `stock_documents` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `unit_conversions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `from_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factor` decimal(18,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_conversions_from_unit_to_unit_unique` (`from_unit`,`to_unit`),
  KEY `unit_conversions_to_unit_foreign` (`to_unit`),
  KEY `unit_conversions_created_by_foreign` (`created_by`),
  KEY `unit_conversions_updated_by_foreign` (`updated_by`),
  CONSTRAINT `unit_conversions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `unit_conversions_from_unit_foreign` FOREIGN KEY (`from_unit`) REFERENCES `units` (`code`) ON DELETE CASCADE,
  CONSTRAINT `unit_conversions_to_unit_foreign` FOREIGN KEY (`to_unit`) REFERENCES `units` (`code`) ON DELETE CASCADE,
  CONSTRAINT `unit_conversions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_group_modules` (
  `group_id` bigint unsigned NOT NULL,
  `module` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rw',
  PRIMARY KEY (`group_id`,`module`),
  CONSTRAINT `user_group_modules_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reorder_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL,
  `ordered_at` date NOT NULL,
  `ref_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `receiving_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `qty` decimal(15,3) DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  `first_received_at` timestamp NULL DEFAULT NULL,
  `close_reason` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reorder_orders_created_by_foreign` (`created_by`),
  KEY `reorder_orders_receiving_id_foreign` (`receiving_id`),
  KEY `reorder_orders_product_id_closed_at_index` (`product_id`,`closed_at`),
  KEY `reorder_orders_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `reorder_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reorder_orders_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `reorder_orders_receiving_id_foreign` FOREIGN KEY (`receiving_id`) REFERENCES `stock_documents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reorder_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_counts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doc_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `count_date` date NOT NULL,
  `scope_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `scope_value` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'counting',
  `blind` tinyint(1) NOT NULL DEFAULT '1',
  `lock_transactions` tinyint(1) NOT NULL DEFAULT '1',
  `threshold_pct` decimal(5,2) NOT NULL DEFAULT '5.00',
  `cutoff_at` datetime NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `submitted_by` bigint unsigned DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `adjustment_document_id` bigint unsigned DEFAULT NULL,
  `unlocked_by` bigint unsigned DEFAULT NULL,
  `unlocked_at` datetime DEFAULT NULL,
  `locked_by` bigint unsigned DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `review_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_counts_doc_no_unique` (`doc_no`),
  KEY `stock_counts_created_by_foreign` (`created_by`),
  KEY `stock_counts_submitted_by_foreign` (`submitted_by`),
  KEY `stock_counts_approved_by_foreign` (`approved_by`),
  KEY `stock_counts_adjustment_document_id_foreign` (`adjustment_document_id`),
  KEY `stock_counts_unlocked_by_foreign` (`unlocked_by`),
  KEY `stock_counts_cancelled_by_foreign` (`cancelled_by`),
  KEY `stock_counts_status_index` (`status`),
  KEY `stock_counts_count_date_index` (`count_date`),
  KEY `stock_counts_locked_by_foreign` (`locked_by`),
  CONSTRAINT `stock_counts_adjustment_document_id_foreign` FOREIGN KEY (`adjustment_document_id`) REFERENCES `stock_documents` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_counts_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_counts_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_counts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_counts_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_counts_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_counts_unlocked_by_foreign` FOREIGN KEY (`unlocked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_document_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_document_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `qty` decimal(15,3) NOT NULL,
  `unit_cost` decimal(15,4) DEFAULT NULL,
  `system_qty` decimal(15,3) DEFAULT NULL,
  `counted_qty` decimal(15,3) DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entered_qty` decimal(15,3) DEFAULT NULL,
  `entered_unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entered_unit_cost` decimal(15,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_document_lines_stock_document_id_foreign` (`stock_document_id`),
  KEY `stock_document_lines_item_id_index` (`item_id`),
  KEY `stock_document_lines_entered_unit_foreign` (`entered_unit`),
  CONSTRAINT `stock_document_lines_entered_unit_foreign` FOREIGN KEY (`entered_unit`) REFERENCES `units` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `stock_document_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_document_lines_stock_document_id_foreign` FOREIGN KEY (`stock_document_id`) REFERENCES `stock_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_count_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_count_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `system_qty` decimal(15,3) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `counted_qty` decimal(15,3) DEFAULT NULL,
  `recount_qty` decimal(15,3) DEFAULT NULL,
  `counted_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entered_by` bigint unsigned DEFAULT NULL,
  `entered_at` datetime DEFAULT NULL,
  `rev` int unsigned NOT NULL DEFAULT '0',
  `skipped` tinyint(1) NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `added_manually` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_count_lines_stock_count_id_item_id_unique` (`stock_count_id`,`item_id`),
  KEY `stock_count_lines_item_id_index` (`item_id`),
  KEY `stock_count_lines_entered_by_foreign` (`entered_by`),
  CONSTRAINT `stock_count_lines_entered_by_foreign` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_count_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_count_lines_stock_count_id_foreign` FOREIGN KEY (`stock_count_id`) REFERENCES `stock_counts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

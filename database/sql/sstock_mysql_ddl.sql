-- Simple Stock (sstock) - skema MySQL 8 (DDL lengkap: tabel, index, foreign key).
-- Dihasilkan dari migrasi Laravel (users + 2026_10_19_100000_create_stock_tables) dalam mode pretend; sumber kebenaran tetap migrasi.
-- Tabel framework (cache, jobs) tidak disertakan: dibuat oleh php artisan migrate.

CREATE DATABASE IF NOT EXISTS sstock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sstock;

create table `users` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(255) not null, `username` varchar(50) not null, `email` varchar(255) not null, `role` varchar(20) not null default 'data_entry', `is_active` tinyint(1) not null default '1', `email_verified_at` timestamp null, `password` varchar(255) not null, `remember_token` varchar(100) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `users` add unique `users_username_unique`(`username`);

alter table `users` add unique `users_email_unique`(`email`);

create table `password_reset_tokens` (`email` varchar(255) not null, `token` varchar(255) not null, `created_at` timestamp null, primary key (`email`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

create table `sessions` (`id` varchar(255) not null, `user_id` bigint unsigned null, `ip_address` varchar(45) null, `user_agent` text null, `payload` longtext not null, `last_activity` int not null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `sessions` add index `sessions_user_id_index`(`user_id`);

alter table `sessions` add index `sessions_last_activity_index`(`last_activity`);

create table `categories` (`id` bigint unsigned not null auto_increment primary key, `code` varchar(20) not null, `name` varchar(100) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `categories` add unique `categories_code_unique`(`code`);

create table `suppliers` (`id` bigint unsigned not null auto_increment primary key, `code` varchar(20) not null, `name` varchar(150) not null, `phone` varchar(40) null, `address` varchar(255) null, `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `suppliers` add unique `suppliers_code_unique`(`code`);

create table `items` (`id` bigint unsigned not null auto_increment primary key, `code` varchar(40) not null, `name` varchar(150) not null, `category_id` bigint unsigned not null, `unit` varchar(20) not null, `location` varchar(50) null, `unit_price` decimal(15, 2) not null default '0', `reorder_point` decimal(15, 3) not null default '0', `reorder_qty` decimal(15, 3) not null default '0', `lead_time_days` smallint unsigned not null default '0', `stock_qty` decimal(15, 3) not null default '0', `avg_cost` decimal(15, 4) not null default '0', `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `items` add constraint `items_category_id_foreign` foreign key (`category_id`) references `categories` (`id`) on delete restrict;

alter table `items` add index `items_name_index`(`name`);

alter table `items` add index `items_category_id_index`(`category_id`);

alter table `items` add unique `items_code_unique`(`code`);

create table `stock_documents` (`id` bigint unsigned not null auto_increment primary key, `doc_no` varchar(30) not null, `type` varchar(10) not null, `doc_at` datetime not null, `status` varchar(12) not null, `supplier_id` bigint unsigned null, `ref_no` varchar(60) null, `recipient` varchar(100) null, `department` varchar(100) null, `reason` varchar(255) null, `note` text null, `created_by` bigint unsigned not null, `approved_by` bigint unsigned null, `approved_at` datetime null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `stock_documents` add constraint `stock_documents_supplier_id_foreign` foreign key (`supplier_id`) references `suppliers` (`id`) on delete restrict;

alter table `stock_documents` add constraint `stock_documents_created_by_foreign` foreign key (`created_by`) references `users` (`id`) on delete restrict;

alter table `stock_documents` add constraint `stock_documents_approved_by_foreign` foreign key (`approved_by`) references `users` (`id`) on delete restrict;

alter table `stock_documents` add index `stock_documents_type_doc_at_index`(`type`, `doc_at`);

alter table `stock_documents` add index `stock_documents_status_index`(`status`);

alter table `stock_documents` add unique `stock_documents_doc_no_unique`(`doc_no`);

create table `stock_document_lines` (`id` bigint unsigned not null auto_increment primary key, `stock_document_id` bigint unsigned not null, `item_id` bigint unsigned not null, `qty` decimal(15, 3) not null, `unit_cost` decimal(15, 4) null, `system_qty` decimal(15, 3) null, `counted_qty` decimal(15, 3) null, `note` varchar(255) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `stock_document_lines` add constraint `stock_document_lines_stock_document_id_foreign` foreign key (`stock_document_id`) references `stock_documents` (`id`) on delete cascade;

alter table `stock_document_lines` add constraint `stock_document_lines_item_id_foreign` foreign key (`item_id`) references `items` (`id`) on delete restrict;

alter table `stock_document_lines` add index `stock_document_lines_item_id_index`(`item_id`);

create table `stock_movements` (`id` bigint unsigned not null auto_increment primary key, `item_id` bigint unsigned not null, `stock_document_id` bigint unsigned not null, `type` varchar(10) not null, `moved_at` datetime not null, `qty` decimal(15, 3) not null, `unit_cost` decimal(15, 4) not null, `balance_qty` decimal(15, 3) not null, `balance_avg_cost` decimal(15, 4) not null, `user_id` bigint unsigned not null, `created_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `stock_movements` add constraint `stock_movements_item_id_foreign` foreign key (`item_id`) references `items` (`id`) on delete restrict;

alter table `stock_movements` add constraint `stock_movements_stock_document_id_foreign` foreign key (`stock_document_id`) references `stock_documents` (`id`) on delete restrict;

alter table `stock_movements` add constraint `stock_movements_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete restrict;

alter table `stock_movements` add index `stock_movements_item_id_moved_at_id_index`(`item_id`, `moved_at`, `id`);

alter table `stock_movements` add index `stock_movements_type_moved_at_index`(`type`, `moved_at`);


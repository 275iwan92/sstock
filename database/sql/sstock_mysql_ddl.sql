-- Simple Stock (sstock) - skema MySQL 8 (DDL lengkap: tabel, index, foreign key).
-- Dihasilkan dari migrasi Laravel (users, stock tables, geo tables + kontak supplier, satuan + konversi, satuan input penerimaan) dalam mode pretend; sumber kebenaran tetap migrasi.
-- Tabel framework (cache, jobs) tidak disertakan: dibuat oleh php artisan migrate. Isi geo_* disalin dari tmerp: php artisan sstock:import-geo.

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

create table `geo_country` (`id_country` bigint unsigned not null, `country_code` varchar(20) null, `iso_code` varchar(20) null, `country_name` varchar(255) not null, `call_country_no` varchar(30) null, `activate` tinyint(1) not null default '1', primary key (`id_country`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

create table `geo_region` (`id_region` bigint unsigned not null, `country_id` bigint unsigned not null, `region_code` varchar(50) null, `region_name` varchar(255) not null, `is_active` tinyint(1) not null default '1', primary key (`id_region`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `geo_region` add index `geo_region_country_id_index`(`country_id`);

create table `geo_city` (`id_city` bigint unsigned not null, `region_id` bigint unsigned not null, `city_code` varchar(50) null, `city_type` varchar(50) null, `city_name` varchar(255) not null, `post_code` varchar(20) null, `activate` tinyint(1) not null default '1', primary key (`id_city`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `geo_city` add index `geo_city_region_id_index`(`region_id`);

alter table `geo_city` add index `geo_city_city_name_index`(`city_name`);

alter table `geo_city` add index `geo_city_post_code_index`(`post_code`);

create table `geo_district` (`id_district` bigint unsigned not null, `city_id` bigint unsigned not null, `district_code` varchar(50) null, `district_name` varchar(255) not null, `post_code` varchar(20) null, `activate` tinyint(1) not null default '1', primary key (`id_district`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `geo_district` add index `geo_district_city_id_index`(`city_id`);

alter table `geo_district` add index `geo_district_district_name_index`(`district_name`);

alter table `geo_district` add index `geo_district_post_code_index`(`post_code`);

create table `geo_village` (`id_village` bigint unsigned not null, `district_id` bigint unsigned not null, `village_code` varchar(50) null, `village_name` varchar(255) not null, `post_code` varchar(20) null, `activate` tinyint(1) not null default '1', primary key (`id_village`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `geo_village` add index `geo_village_district_id_index`(`district_id`);

alter table `geo_village` add index `geo_village_village_name_index`(`village_name`);

alter table `geo_village` add index `geo_village_post_code_index`(`post_code`);

alter table `suppliers` add `contact_name` varchar(100) null after `phone`;

alter table `suppliers` add `contact_mobile` varchar(40) null after `contact_name`;

alter table `suppliers` add `area_level` varchar(10) null after `address`;

alter table `suppliers` add `area_id` bigint unsigned null after `area_level`;

alter table `suppliers` add `postal_code` varchar(20) null after `area_id`;

create table `units` (`id` bigint unsigned not null auto_increment primary key, `code` varchar(20) not null, `name` varchar(100) not null, `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `units` add unique `units_code_unique`(`code`);

select distinct `unit` from `items`;

insert into `units` (`code`, `created_at`, `is_active`, `name`, `updated_at`) values ('pcs', '2026-10-19 12:46:30', 1, 'Pieces', '2026-10-19 12:46:30'), ('box', '2026-10-19 12:46:30', 1, 'Box', '2026-10-19 12:46:30'), ('set', '2026-10-19 12:46:30', 1, 'Set', '2026-10-19 12:46:30'), ('pack', '2026-10-19 12:46:30', 1, 'Pack', '2026-10-19 12:46:30'), ('roll', '2026-10-19 12:46:30', 1, 'Roll', '2026-10-19 12:46:30'), ('pair', '2026-10-19 12:46:30', 1, 'Pair', '2026-10-19 12:46:30'), ('unit', '2026-10-19 12:46:30', 1, 'Unit', '2026-10-19 12:46:30'), ('sheet', '2026-10-19 12:46:30', 1, 'Sheet', '2026-10-19 12:46:30'), ('kg', '2026-10-19 12:46:30', 1, 'Kilogram', '2026-10-19 12:46:30'), ('gram', '2026-10-19 12:46:30', 1, 'Gram', '2026-10-19 12:46:30'), ('meter', '2026-10-19 12:46:30', 1, 'Meter', '2026-10-19 12:46:30'), ('liter', '2026-10-19 12:46:30', 1, 'Liter', '2026-10-19 12:46:30'), ('can', '2026-10-19 12:46:30', 1, 'Can', '2026-10-19 12:46:30'), ('bottle', '2026-10-19 12:46:30', 1, 'Bottle', '2026-10-19 12:46:30');

alter table `items` add constraint `items_unit_foreign` foreign key (`unit`) references `units` (`code`) on delete restrict;

create table `unit_conversions` (`id` bigint unsigned not null auto_increment primary key, `from_unit` varchar(20) not null, `to_unit` varchar(20) not null, `factor` decimal(18, 6) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

alter table `unit_conversions` add unique `unit_conversions_from_unit_to_unit_unique`(`from_unit`, `to_unit`);

alter table `unit_conversions` add constraint `unit_conversions_from_unit_foreign` foreign key (`from_unit`) references `units` (`code`) on delete cascade;

alter table `unit_conversions` add constraint `unit_conversions_to_unit_foreign` foreign key (`to_unit`) references `units` (`code`) on delete cascade;

alter table `stock_document_lines` add `entered_qty` decimal(15, 3) null after `note`;

alter table `stock_document_lines` add `entered_unit` varchar(20) null after `entered_qty`;

alter table `stock_document_lines` add `entered_unit_cost` decimal(15, 4) null after `entered_unit`;

alter table `stock_document_lines` add constraint `stock_document_lines_entered_unit_foreign` foreign key (`entered_unit`) references `units` (`code`) on delete restrict;

alter table `units` add `kind` varchar(12) not null default 'count' after `name`;

update `units` set `kind` = 'weight' where `code` in ('kg', 'gram');

update `units` set `kind` = 'length' where `code` in ('meter');

update `units` set `kind` = 'volume' where `code` in ('liter');

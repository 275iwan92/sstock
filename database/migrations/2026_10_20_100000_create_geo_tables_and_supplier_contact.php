<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* REMARK
 * Tujuan       : Master wilayah (negara > provinsi > kota > kecamatan > kelurahan) dengan nama tabel dan kolom sama seperti tmerp (tm2mst.geo_*), supaya id bisa disalin apa adanya;
 *                serta kolom kontak + wilayah di suppliers (nama kontak, HP kontak, area_level + area_id seperti default_area_* di tmerp, kode pos).
 * Sumber hulu  : data diisi oleh php artisan sstock:import-geo (salinan dari tm2mst); tanpa foreign key agar impor massal tidak bergantung urutan.
 * Output       : geo_country, geo_region, geo_city, geo_district, geo_village; kolom baru suppliers.
 * Dipakai oleh : App\Support\GeoArea, SupplierController.
 * Status       : Aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_country', function (Blueprint $table) {
            $table->unsignedBigInteger('id_country')->primary();
            $table->string('country_code', 20)->nullable();
            $table->string('iso_code', 20)->nullable();
            $table->string('country_name', 255);
            $table->string('call_country_no', 30)->nullable();
            $table->boolean('activate')->default(true);
        });

        Schema::create('geo_region', function (Blueprint $table) {
            $table->unsignedBigInteger('id_region')->primary();
            $table->unsignedBigInteger('country_id')->index();
            $table->string('region_code', 50)->nullable();
            $table->string('region_name', 255);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('geo_city', function (Blueprint $table) {
            $table->unsignedBigInteger('id_city')->primary();
            $table->unsignedBigInteger('region_id')->index();
            $table->string('city_code', 50)->nullable();
            $table->string('city_type', 50)->nullable();
            $table->string('city_name', 255)->index();
            $table->string('post_code', 20)->nullable()->index();
            $table->boolean('activate')->default(true);
        });

        Schema::create('geo_district', function (Blueprint $table) {
            $table->unsignedBigInteger('id_district')->primary();
            $table->unsignedBigInteger('city_id')->index();
            $table->string('district_code', 50)->nullable();
            $table->string('district_name', 255)->index();
            $table->string('post_code', 20)->nullable()->index();
            $table->boolean('activate')->default(true);
        });

        Schema::create('geo_village', function (Blueprint $table) {
            $table->unsignedBigInteger('id_village')->primary();
            $table->unsignedBigInteger('district_id')->index();
            $table->string('village_code', 50)->nullable();
            $table->string('village_name', 255)->index();
            $table->string('post_code', 20)->nullable()->index();
            $table->boolean('activate')->default(true);
        });

        // suppliers.phone = telepon kantor (PSTN); address = nama jalan; area_level + area_id = VILLAGE | DISTRICT | CITY + id-nya.
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('contact_name', 100)->nullable()->after('phone');
            $table->string('contact_mobile', 40)->nullable()->after('contact_name');
            $table->string('area_level', 10)->nullable()->after('address');
            $table->unsignedBigInteger('area_id')->nullable()->after('area_level');
            $table->string('postal_code', 20)->nullable()->after('area_id');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'contact_mobile', 'area_level', 'area_id', 'postal_code']);
        });
        foreach (['geo_village', 'geo_district', 'geo_city', 'geo_region', 'geo_country'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

<?php

namespace App\Exceptions;

use RuntimeException;

/* REMARK
 * Tujuan       : Penjaga lunak input Stok Opname: baris sudah diubah orang lain sejak layar dimuat. Pesannya memuat siapa dan jam berapa; layar meminta alasan untuk MENIMPA atau membuang isian.
 * Dipakai oleh : App\Services\StockCounts::saveLine, StockCountController@saveLines.
 * Status       : Aktif.
 */
class StockCountRowChanged extends RuntimeException {}

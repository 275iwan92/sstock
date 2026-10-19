<?php

namespace App\Enums;

/* REMARK
 * Tujuan       : Jenis dokumen stok (saldo awal, masuk, keluar, penyesuaian) beserta slug URL, awalan nomor, judul layar, dan siapa yang boleh memakainya.
 * Output       : label(), slug(), prefix(), supervisorOnly(), signed().
 * Dipakai oleh : StockDocumentController, StockLedger, ReportController, menu.
 * Status       : Aktif.
 */
enum StockDocumentType: string
{
    case Opening = 'OPENING';
    case Receiving = 'IN';
    case Issuance = 'OUT';
    case Adjustment = 'ADJ';

    public static function fromSlug(string $slug): self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }
        abort(404);
    }

    public function slug(): string
    {
        return match ($this) {
            self::Opening => 'opening',
            self::Receiving => 'receiving',
            self::Issuance => 'issuance',
            self::Adjustment => 'adjustment',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening Balance',
            self::Receiving => 'Receiving',
            self::Issuance => 'Issuance',
            self::Adjustment => 'Stock Adjustment',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Opening => 'OPN',
            self::Receiving => 'RCV',
            self::Issuance => 'ISS',
            self::Adjustment => 'ADJ',
        };
    }

    public function supervisorOnly(): bool
    {
        return $this === self::Opening || $this === self::Adjustment;
    }

    // Efek ke saldo untuk jenis yang qty-nya selalu positif; penyesuaian membawa tandanya sendiri.
    public function signed(float $qty): float
    {
        return $this === self::Issuance ? -$qty : $qty;
    }
}

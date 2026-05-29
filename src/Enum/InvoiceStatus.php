<?php

namespace App\Enum;

enum InvoiceStatus: string
{
    case Draft     = 'draft';
    case Sent      = 'sent';
    case Paid      = 'paid';
    case Refunded  = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Brouillon',
            self::Sent      => 'Envoyée',
            self::Paid      => 'Payée',
            self::Refunded  => 'Remboursée',
            self::Cancelled => 'Annulée',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft     => 'secondary',
            self::Sent      => 'warning',
            self::Paid      => 'success',
            self::Refunded  => 'info',
            self::Cancelled => 'danger',
        };
    }
}

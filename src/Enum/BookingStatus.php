<?php

namespace App\Enum;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Refused = 'refused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmé',
            self::Refused => 'Refusé',
            self::Completed => 'Terminé',
            self::Cancelled => 'Annulé',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending => 'badge-pending',
            self::Confirmed => 'badge-confirmed',
            self::Refused => 'badge-refused',
            self::Completed => 'badge-completed',
            self::Cancelled => 'badge-cancelled',
        };
    }

    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case->value;
        }
        return $choices;
    }
}

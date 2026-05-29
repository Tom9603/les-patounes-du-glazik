<?php

namespace App\Enum;

enum ServiceType: string
{
    case Petsitting30 = 'petsitting_30';
    case Petsitting45 = 'petsitting_45';
    case Petsitting60 = 'petsitting_60';
    case NaturoPresentiel = 'naturo_presentiel';
    case NaturoOnline = 'naturo_online';

    public function label(): string
    {
        return match($this) {
            self::Petsitting30 => 'Petsitting - Visite 30 min',
            self::Petsitting45 => 'Petsitting - Visite 45 min',
            self::Petsitting60 => 'Petsitting - Visite 60 min',
            self::NaturoPresentiel => 'Naturopathie animalière - Présentiel',
            self::NaturoOnline => 'Naturopathie animalière - En ligne',
        };
    }

    public function basePrice(): float
    {
        return match($this) {
            self::Petsitting30 => 12.00,
            self::Petsitting45 => 15.00,
            self::Petsitting60 => 18.00,
            self::NaturoPresentiel => 70.00,
            self::NaturoOnline => 60.00,
        };
    }

    public function description(): string
    {
        return match($this) {
            self::Petsitting30 => 'Visite à domicile de 30 minutes (chiens, chats, NAC)',
            self::Petsitting45 => 'Visite à domicile de 45 minutes (chiens, chats, NAC)',
            self::Petsitting60 => 'Visite à domicile de 60 minutes (chiens, chats, NAC)',
            self::NaturoPresentiel => 'Consultation naturopathie en présentiel (70 €)',
            self::NaturoOnline => 'Consultation naturopathie en ligne (60 €)',
        };
    }

    public function durationMinutes(): int
    {
        return match($this) {
            self::Petsitting30 => 30,
            self::Petsitting45 => 45,
            self::Petsitting60 => 60,
            self::NaturoPresentiel => 60,
            self::NaturoOnline => 60,
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

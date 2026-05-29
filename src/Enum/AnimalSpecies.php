<?php

namespace App\Enum;

enum AnimalSpecies: string
{
    case Dog = 'dog';
    case Cat = 'cat';
    case Horse = 'horse';
    case Nac = 'nac';
    case Poultry = 'poultry';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Dog => 'Chien',
            self::Cat => 'Chat',
            self::Horse => 'Equidé',
            self::Nac => 'NAC',
            self::Poultry => 'Basse-cour',
            self::Other => 'Autre',
        };
    }

    public function emoji(): string
    {
        return match($this) {
            self::Dog     => '🐶',
            self::Cat     => '🐱',
            self::Horse   => '🐴',
            self::Nac     => '🐰',
            self::Poultry => '🐔',
            self::Other   => '🐾',
        };
    }

    public function faIcon(): string
    {
        return match($this) {
            self::Dog     => 'fa-dog',
            self::Cat     => 'fa-cat',
            self::Horse   => 'fa-horse',
            self::Nac     => 'fa-paw',
            self::Poultry => 'fa-dove',
            self::Other   => 'fa-paw',
        };
    }

    public function pricingCategory(): string
    {
        return match($this) {
            self::Dog, self::Cat, self::Nac, self::Other => 'compagnie',
            self::Poultry => 'minifarm',
            self::Horse   => 'equide',
        };
    }

    public function pricingHint(): string
    {
        return match($this) {
            self::Dog, self::Cat, self::Nac, self::Other => '30 min : 12€ · 45 min : 15€ · 60 min : 18€',
            self::Poultry => '30 min : 15€ · 45 min : 20€ · 60 min : 25€',
            self::Horse   => '30 min : 15€ · 60 min : 25€',
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

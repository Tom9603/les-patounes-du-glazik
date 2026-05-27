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

    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case->value;
        }
        return $choices;
    }
}

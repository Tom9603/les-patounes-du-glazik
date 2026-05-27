<?php

namespace App\Enum;

enum AnimalSex: string
{
    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return match($this) {
            self::Male => 'Mâle',
            self::Female => 'Femelle',
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

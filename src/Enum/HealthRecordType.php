<?php

namespace App\Enum;

enum HealthRecordType: string
{
    case Vaccination  = 'vaccination';
    case Parasite     = 'parasite';
    case Consultation = 'consultation';
    case Surgery      = 'surgery';
    case Note         = 'note';

    public function label(): string
    {
        return match($this) {
            self::Vaccination  => 'Vaccination',
            self::Parasite     => 'Antiparasitaire',
            self::Consultation => 'Consultation',
            self::Surgery      => 'Chirurgie',
            self::Note         => 'Note',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Vaccination  => 'fa-syringe',
            self::Parasite     => 'fa-bug',
            self::Consultation => 'fa-stethoscope',
            self::Surgery      => 'fa-kit-medical',
            self::Note         => 'fa-note-sticky',
        };
    }
}

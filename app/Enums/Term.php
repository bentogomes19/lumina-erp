<?php

namespace App\Enums;
enum Term: string {
    case B1 = 'b1'; 
    case B2 = 'b2'; 
    case B3 = 'b3'; 
    case B4 = 'b4';
    
    public static function options(): array {
        return ['b1'=>'1º Bim.', 'b2'=>'2º Bim.', 'b3'=>'3º Bim.', 'b4'=>'4º Bim.'];
    }

    public function label(): string
    {
        return match($this) {
            self::B1 => '1º Bimestre',
            self::B2 => '2º Bimestre',
            self::B3 => '3º Bimestre',
            self::B4 => '4º Bimestre',
        };
    }
}

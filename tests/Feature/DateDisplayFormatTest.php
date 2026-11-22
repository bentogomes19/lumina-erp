<?php

namespace Tests\Feature;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Tests\TestCase;

class DateDisplayFormatTest extends TestCase
{
    public function test_date_pickers_use_brazilian_format_by_default(): void
    {
        $picker = DatePicker::make('birth_date');

        $this->assertFalse($picker->isNative());
        $this->assertSame('d/m/Y', $picker->getDisplayFormat());
    }

    public function test_date_time_pickers_use_brazilian_format_by_default(): void
    {
        $this->assertFalse(DateTimePicker::make('scheduled_at')->isNative());
        $this->assertSame('d/m/Y H:i:s', DateTimePicker::make('scheduled_at')->getDisplayFormat());
        $this->assertSame('d/m/Y H:i', DateTimePicker::make('scheduled_at')->seconds(false)->getDisplayFormat());
    }
}

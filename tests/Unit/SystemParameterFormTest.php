<?php

namespace Tests\Unit;

use App\Filament\Resources\SystemParameters\Schemas\SystemParameterForm;
use PHPUnit\Framework\TestCase;

class SystemParameterFormTest extends TestCase
{
    public function test_it_normalizes_text_values_when_filament_does_not_dehydrate_immutable_type(): void
    {
        $data = SystemParameterForm::normalizeData([
            'value' => 'Colégio Horizonte',
            'is_active' => true,
        ]);

        $this->assertSame('Colégio Horizonte', $data['value']);
        $this->assertTrue($data['is_active']);
    }

    public function test_it_normalizes_typed_editable_values_without_requiring_type_in_payload(): void
    {
        $this->assertSame('1', SystemParameterForm::normalizeData(['boolean_value' => true])['value']);
        $this->assertSame('bimestre', SystemParameterForm::normalizeData(['select_value' => 'bimestre'])['value']);
        $this->assertSame('system/logo.png', SystemParameterForm::normalizeData(['file_value' => 'system/logo.png'])['value']);
    }
}

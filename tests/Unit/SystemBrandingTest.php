<?php

namespace Tests\Unit;

use App\Enums\SystemParameterType;
use App\Models\SystemParameter;
use App\Support\SystemBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_portal_lumina_when_the_institution_name_is_unavailable(): void
    {
        $this->assertSame('Portal Lumina', app(SystemBranding::class)->institutionName());
    }

    public function test_it_reads_the_configured_institution_name_and_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('system/logo.png', 'fake-image');

        SystemParameter::create([
            'key' => 'institution.name',
            'name' => 'Nome da instituição',
            'category' => 'Identidade e personalização',
            'type' => SystemParameterType::TEXT,
            'value' => 'Colégio Horizonte',
            'default_value' => 'Portal Lumina',
            'description' => 'Nome institucional.',
            'is_active' => true,
            'is_system' => true,
        ]);
        SystemParameter::create([
            'key' => 'institution.logo',
            'name' => 'Logo principal',
            'category' => 'Identidade e personalização',
            'type' => SystemParameterType::FILE,
            'value' => 'system/logo.png',
            'default_value' => null,
            'description' => 'Logo institucional.',
            'is_active' => true,
            'is_system' => true,
        ]);

        $branding = app(SystemBranding::class);

        $this->assertSame('Colégio Horizonte', $branding->institutionName());
        $this->assertStringEndsWith('/storage/system/logo.png', $branding->logoUrl());
        $this->assertStringStartsWith('data:image/png;base64,', $branding->documentLogoDataUri());
    }

    public function test_it_does_not_apply_inactive_branding_parameters(): void
    {
        SystemParameter::create([
            'key' => 'institution.name',
            'name' => 'Nome da instituição',
            'category' => 'Identidade e personalização',
            'type' => SystemParameterType::TEXT,
            'value' => 'Colégio Inativo',
            'default_value' => 'Portal Lumina',
            'description' => 'Nome institucional.',
            'is_active' => false,
            'is_system' => true,
        ]);

        $this->assertSame('Portal Lumina', app(SystemBranding::class)->institutionName());
    }

    public function test_it_uses_default_system_name_and_color_when_parameters_are_unavailable(): void
    {
        $branding = app(SystemBranding::class);

        $this->assertSame('Lumina ERP', $branding->systemName());
        $this->assertSame('#3D5A80', $branding->primaryColor());
        $this->assertFalse($branding->hasCustomPrimaryColor());
    }

    public function test_it_reads_the_configured_system_name_and_color(): void
    {
        $this->textParameter('system.display_name', 'Gestão Horizonte');
        $this->textParameter('institution.primary_color', '#1f7a4d');

        $branding = app(SystemBranding::class);

        $this->assertSame('Gestão Horizonte', $branding->systemName());
        $this->assertSame('#1F7A4D', $branding->primaryColor());
        $this->assertTrue($branding->hasCustomPrimaryColor());
    }

    public function test_it_normalizes_short_colors_and_rejects_invalid_ones(): void
    {
        $this->textParameter('institution.primary_color', 'abc');
        $this->assertSame('#AABBCC', app(SystemBranding::class)->primaryColor());

        SystemParameter::where('key', 'institution.primary_color')->update(['value' => 'red; background:url(x)']);
        $this->assertSame('#3D5A80', app(SystemBranding::class)->primaryColor());
    }

    private function textParameter(string $key, string $value): void
    {
        SystemParameter::create([
            'key' => $key,
            'name' => $key,
            'category' => 'Identidade e personalização',
            'type' => SystemParameterType::TEXT,
            'value' => $value,
            'default_value' => null,
            'description' => 'Parâmetro de identidade.',
            'is_active' => true,
            'is_system' => true,
        ]);
    }
}

<?php

namespace App\Support;

use App\Models\SystemParameter;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Centraliza a identidade visual configurável do sistema.
 *
 * Os painéis e documentos devem consultar este objeto em vez de manter
 * nomes ou caminhos de logo fixos espalhados pela aplicação.
 */
class SystemBranding
{
    public const DEFAULT_NAME = 'Portal Lumina';

    public const DEFAULT_SYSTEM_NAME = 'Lumina ERP';

    public const DEFAULT_PRIMARY_COLOR = '#3D5A80';

    public function institutionName(): string
    {
        $name = SystemParameter::read('institution.name', self::DEFAULT_NAME);

        return filled($name) ? trim((string) $name) : self::DEFAULT_NAME;
    }

    /**
     * Nome do sistema exibido em mensagens e documentos (parâmetro "system.display_name").
     */
    public function systemName(): string
    {
        $name = SystemParameter::read('system.display_name', self::DEFAULT_SYSTEM_NAME);

        return filled($name) ? trim((string) $name) : self::DEFAULT_SYSTEM_NAME;
    }

    /**
     * Cor institucional em hexadecimal (parâmetro "institution.primary_color").
     *
     * Aceita "#RGB" ou "#RRGGBB"; qualquer outro valor volta para a cor padrão.
     */
    public function primaryColor(): string
    {
        $color = trim((string) SystemParameter::read('institution.primary_color', self::DEFAULT_PRIMARY_COLOR));

        if (! str_starts_with($color, '#')) {
            $color = '#'.$color;
        }

        if (preg_match('/^#([0-9a-f]{3})$/i', $color, $short)) {
            $color = '#'.implode('', array_map(fn (string $c): string => $c.$c, str_split($short[1])));
        }

        return preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtoupper($color) : self::DEFAULT_PRIMARY_COLOR;
    }

    /**
     * Indica se a cor institucional foi alterada em relação à paleta padrão do tema.
     */
    public function hasCustomPrimaryColor(): bool
    {
        return $this->primaryColor() !== self::DEFAULT_PRIMARY_COLOR;
    }

    public function logoUrl(): ?string
    {
        return $this->fileUrl('institution.logo');
    }

    public function documentLogoUrl(): ?string
    {
        return $this->fileUrl('institution.document_logo');
    }

    /**
     * Retorna o arquivo como data URI para renderizadores PDF que não acessam
     * URLs HTTP da aplicação durante a geração do documento.
     */
    public function documentLogoDataUri(): ?string
    {
        $path = $this->filePath('institution.document_logo') ?? $this->filePath('institution.logo');

        if (blank($path)) {
            return null;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($path)) {
                return null;
            }

            $mimeType = $disk->mimeType($path) ?: 'image/png';

            return 'data:'.$mimeType.';base64,'.base64_encode($disk->get($path));
        } catch (Throwable) {
            return null;
        }
    }

    private function fileUrl(string $key): ?string
    {
        $path = $this->filePath($key);

        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) || str_starts_with($path, 'data:')) {
            return $path;
        }

        try {
            return Storage::disk('public')->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    private function filePath(string $key): ?string
    {
        $value = SystemParameter::read($key);

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return filled($value) ? (string) $value : null;
    }
}

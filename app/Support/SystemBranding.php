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

    public function institutionName(): string
    {
        $name = SystemParameter::read('institution.name', self::DEFAULT_NAME);

        return filled($name) ? trim((string) $name) : self::DEFAULT_NAME;
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

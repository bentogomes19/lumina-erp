<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IbgeLocalidadesService
{
    protected const ESTADOS_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome';
    protected const CACHE_ESTADOS_KEY = 'ibge_estados';
    protected const CACHE_MUNICIPIOS_PREFIX = 'ibge_municipios_';
    protected const CACHE_TTL_SECONDS = 86400; // 24 horas

    /**
     * Retorna lista de estados: sigla => "Nome (UF)"
     * Ex.: "SP" => "São Paulo (SP)"
     */
    public static function getEstadosOptions(): array
    {
        return Cache::remember(self::CACHE_ESTADOS_KEY, self::CACHE_TTL_SECONDS, function () {
            $response = Http::timeout(10)->get(self::ESTADOS_URL);
            if ($response->failed()) {
                return self::estadosFallback();
            }
            $estados = $response->json();
            if (! is_array($estados)) {
                return self::estadosFallback();
            }
            $options = [];
            foreach ($estados as $e) {
                $sigla = $e['sigla'] ?? '';
                $nome = $e['nome'] ?? $sigla;
                $options[$sigla] = "{$nome} ({$sigla})";
            }
            return $options;
        });
    }

    /**
     * Retorna lista de municípios de um estado (por sigla UF): nome => nome
     * Ex.: "São Paulo" => "São Paulo"
     */
    public static function getMunicipiosOptions(string $uf): array
    {
        $uf = strtoupper(trim($uf));
        if (strlen($uf) !== 2) {
            return [];
        }

        $key = self::CACHE_MUNICIPIOS_PREFIX . $uf;

        return Cache::remember($key, self::CACHE_TTL_SECONDS, function () use ($uf) {
            $url = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios?orderBy=nome";
            $response = Http::timeout(15)->get($url);
            if ($response->failed()) {
                return [];
            }
            $municipios = $response->json();
            if (! is_array($municipios)) {
                return [];
            }
            $options = [];
            foreach ($municipios as $m) {
                $nome = $m['nome'] ?? '';
                if ($nome !== '') {
                    $options[$nome] = $nome;
                }
            }
            return $options;
        });
    }

    /**
     * Fallback estático caso a API do IBGE esteja indisponível.
     */
    protected static function estadosFallback(): array
    {
        return [
            'AC' => 'Acre (AC)',
            'AL' => 'Alagoas (AL)',
            'AP' => 'Amapá (AP)',
            'AM' => 'Amazonas (AM)',
            'BA' => 'Bahia (BA)',
            'CE' => 'Ceará (CE)',
            'DF' => 'Distrito Federal (DF)',
            'ES' => 'Espírito Santo (ES)',
            'GO' => 'Goiás (GO)',
            'MA' => 'Maranhão (MA)',
            'MT' => 'Mato Grosso (MT)',
            'MS' => 'Mato Grosso do Sul (MS)',
            'MG' => 'Minas Gerais (MG)',
            'PA' => 'Pará (PA)',
            'PB' => 'Paraíba (PB)',
            'PR' => 'Paraná (PR)',
            'PE' => 'Pernambuco (PE)',
            'PI' => 'Piauí (PI)',
            'RJ' => 'Rio de Janeiro (RJ)',
            'RN' => 'Rio Grande do Norte (RN)',
            'RS' => 'Rio Grande do Sul (RS)',
            'RO' => 'Rondônia (RO)',
            'RR' => 'Roraima (RR)',
            'SC' => 'Santa Catarina (SC)',
            'SP' => 'São Paulo (SP)',
            'SE' => 'Sergipe (SE)',
            'TO' => 'Tocantins (TO)',
        ];
    }
}

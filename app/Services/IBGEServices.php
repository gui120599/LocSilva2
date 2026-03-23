<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\ConnectionException;

class IBGEServices
{
    /**
     * Método base para padronizar todas as chamadas HTTP.
     * Aqui centralizamos:
     * - Headers
     * - Timeout
     * - Retry (tentativas automáticas)
     * - Forçar IPv4 (evita erro cURL 52)
     */
    protected static function http()
    {
        return Http::withHeaders([
                'Accept' => 'application/json', // Espera resposta JSON
            ])
            ->timeout(20) // Tempo máximo de espera (segundos)
            ->retry(3, 2000) // Tenta 3 vezes com intervalo de 2 segundos
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Força uso de IPv4 (resolve muitos problemas de conexão)
                ],
            ]);
    }

    /**
     * Busca informações de endereço pelo CEP usando ViaCEP
     */
    public static function buscaCep(string $search): array
    {
        // Validação básica
        if (empty($search)) {
            return ['error' => 'CEP obrigatório'];
        }

        try {
            // Faz a requisição para API do ViaCEP
            $response = self::http()
                ->get("https://viacep.com.br/ws/{$search}/json/");

            // Verifica se a requisição falhou (status != 200)
            if ($response->failed()) {
                return [];
            }

            // Retorna JSON convertido para array
            return $response->json() ?? [];

        } catch (ConnectionException $e) {
            // Captura erros de conexão (timeout, DNS, API fora, etc.)
            return [];
        }
    }

    /**
     * Retorna lista de estados (UFs)
     * Formato: ['GO' => 'Goiás', 'SP' => 'São Paulo']
     */
    public static function ufs(): array
    {
        // Cache por 24h para evitar chamadas constantes na API
        return Cache::remember('ibge_ufs', 86400, function () {

            try {
                $response = self::http()
                    ->get('https://servicodados.ibge.gov.br/api/v1/localidades/estados');

                if ($response->failed()) {
                    return [];
                }

                $estados = $response->json();
                $opcoes = [];

                if (is_array($estados)) {
                    foreach ($estados as $estado) {

                        /**
                         * Monta array no padrão do Filament:
                         * chave = valor salvo
                         * valor = label exibido
                         */
                        $opcoes[$estado['sigla']] = $estado['nome'];
                    }

                    // Ordena alfabeticamente pelo nome (melhora UX no select)
                    asort($opcoes);
                }

                return $opcoes;

            } catch (ConnectionException $e) {
                // Caso API do IBGE esteja fora ou erro de rede
                return [];
            }
        });
    }

    /**
     * Retorna cidades de uma UF específica
     * Exemplo: GO -> ['Goiânia', 'Anápolis', ...]
     */
    public static function cidadesPorUf(string $uf): array
    {
        // Evita chamada desnecessária
        if (empty($uf)) {
            return [];
        }

        // Cache por UF (melhora MUITO performance)
        return Cache::remember("ibge_cidades_{$uf}", 86400, function () use ($uf) {

            try {
                $response = self::http()
                    ->get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios?orderBy=nome");

                if ($response->failed()) {
                    return [];
                }

                $cidades = $response->json();
                $opcoes = [];

                if (is_array($cidades)) {
                    foreach ($cidades as $cidade) {

                        /**
                         * Aqui você decidiu usar:
                         * chave = nome
                         * valor = nome
                         *
                         * (Se quiser melhorar depois, pode usar ID do IBGE)
                         */
                        $opcoes[$cidade['nome']] = $cidade['nome'];
                    }
                }

                return $opcoes;

            } catch (ConnectionException $e) {
                return [];
            }
        });
    }
}
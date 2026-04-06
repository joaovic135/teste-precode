<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

class ApiClient
{
    public function __construct(
        private string $baseUrl,
        private string $authToken,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function get(string $endpoint): array
    {
        return $this->request('GET', $endpoint);
    }

    public function post(string $endpoint, array $payload): array
    {
        return $this->request('POST', $endpoint, $payload);
    }

    public function put(string $endpoint, array $payload): array
    {
        return $this->request('PUT', $endpoint, $payload);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    private function request(string $method, string $endpoint, array $payload = []): array
    {
        $url      = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $endpoint = ltrim($endpoint, '/');

        $headers = [
            'Authorization: ' . $this->authToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $handle = curl_init();

        curl_setopt_array($handle, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if (!empty($payload)) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $responseBody = curl_exec($handle);
        $httpCode     = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($handle);

        curl_close($handle);

        if ($curlError) {
            throw new RuntimeException(
                "Falha na requisição {$method} /{$endpoint}: {$curlError}"
            );
        }

        if ($httpCode >= 500) {
            throw new RuntimeException(
                "A API do marketplace retornou erro de servidor {$httpCode} em {$method} /{$endpoint}"
            );
        }

        $decoded = json_decode($responseBody ?: '{}', true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // A API Precode às vezes retorna JSON malformado com valores ausentes (ex: "sku": ,).
            // Tentativa de correção: substituir padrão inválido por null antes de decodificar.
            $fixed   = preg_replace('/:\s*,/', ': null,', $responseBody ?? '');
            $decoded = json_decode($fixed ?? '{}', true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($httpCode >= 400) {
                throw new RuntimeException(
                    "A API retornou erro {$httpCode} em {$method} /{$endpoint} com corpo não-JSON"
                );
            }

            return [];
        }

        if ($httpCode >= 400) {
            $message = $decoded['mensagem']
                ?? $decoded['message']
                ?? $decoded['error']
                ?? "(sem mensagem)";

            throw new RuntimeException(
                "A API retornou erro {$httpCode} em {$method} /{$endpoint}: {$message}"
            );
        }

        return $decoded ?? [];
    }
}

<?php

namespace GlpiPlugin\Medicaoeletronica\Client;

use Exception;
use GlpiPlugin\Medicaoeletronica\Repository\ConfigRepository;
use GuzzleHttp\Client as Guzzle;
use Toolbox;

class MedicaoSoapClient
{
    private ConfigRepository $configRepository;

    public function __construct(?ConfigRepository $configRepository = null)
    {
        $this->configRepository = $configRepository ?? new ConfigRepository();
    }

    public function send(array $payload): array
    {
        $config = $this->configRepository->getConfig();
        $url = $config['url'];
        $maxRetries = (int) ($config['retries'] ?? 3);
        $http = new Guzzle();
        $requestPayload = [
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction'   => 'http://oakmontgroup.com.br/GravarChamado'
            ],
            'body' => $this->payloadToXml($payload)
        ];

        $lastResponse = null;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $http->request('POST', $url, $requestPayload);
                $lastResponse = [
                    'status'   => 0,
                    'response' => $response
                ];

                if ((int) $lastResponse['status'] === 1) {
                    break;
                }
            } catch (Exception $e) {
                $this->logFailedRequest($e, $requestPayload);
                $lastResponse = [
                    'status'   => 1,
                    'response' => $e->getMessage()
                ];
                if ($attempt >= $maxRetries) {
                    break;
                }

                sleep((int) pow(2, $attempt - 1));
            }
        }

        return $this->normalizeResponse($lastResponse);
    }

    public function payloadToXml(array $payload): string
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
        $xml .= '<soap:Body>';
        $xml .= '<GravarChamado xmlns="http://oakmontgroup.com.br/">';

        foreach ($payload as $key => $value) {
            $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
        }

        $xml .= '</GravarChamado>';
        $xml .= '</soap:Body>';
        $xml .= '</soap:Envelope>';

        return $xml;
    }

    private function normalizeResponse(?array $response): array
    {
        if (!is_array($response)) {
            return [
                'status'   => 1,
                'response' => 'Resposta vazia da integração'
            ];
        }

        if ((int) ($response['status'] ?? 1) === 1) {
            return $response;
        }

        $normalized = [
            'status'   => $response['status'],
            'response' => $response['response']->getBody()->getContents()
        ];

        if (!preg_match('/Chamado integrado com sucesso/', $normalized['response'])) {
            $normalized['status'] = 1;
        }

        return $normalized;
    }

    private function logFailedRequest(Exception $error, array $payload = []): void
    {
        Toolbox::logInFile(
            'medicao_eletronica',
            '[MedicaoEletronica] SOAP request failed: ' . $error->getMessage() . ' | payload: ' . json_encode($payload) . "\n"
        );
    }
}

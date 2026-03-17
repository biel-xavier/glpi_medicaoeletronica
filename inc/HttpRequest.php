<?php

namespace GlpiPlugin\Medicaoeletronica;

use Exception;
use Toolbox;
use GuzzleHttp\Client as Guzzle;

const RETRY = 3;

const URL_WEBHOOK = 'https://webhook.site/65fcb50c-4209-4be3-b9d5-64962e091b6f';
//const URL_WEBHOOK = 'https://oakmo.webhook.office.com/webhookb2/6e5c4b43-1365-4ffb-8951-346798ac8e7b@3d8a2982-4564-4f04-8bdf-f0a6f9cf0405/IncomingWebhook/f59690ccd74c487b8892ce4ce27c67af/b0360e23-be4f-4459-a49a-6cdf5d76c128/V2shhTbVNkMw4ptz0-XAeLvQ1iFVsRChMyXvgMHr6GNEc1';


class HttpRequest
{

    public $http;

    public function __construct(Guzzle $http)
    {
        $this->http = $http;
    }

    public function request(string $method, string $uri, array $options = [])
    {
        $response = [];

        $retry_count = 0;

        do {
            try {
                if (isset($options['token'])) {
                    $options['headers']['Authorization'] = $options['token'];
                }

                $response = $this->http->request($method, $uri, $options);

                return ['status' => 0, 'response' => $response];
            } catch (Exception $e) {
                $this->logRequest($e, $options);

                return ['status' => 1, 'response' => $e->getMessage()];
            }

            ++$retry_count;
            if ($retry_count == RETRY) {
                break;
            }
        } while (count($response) == 0);
    }

    private function logRequest($errorMessage, $payload = [])
    {

        $arrPayload = json_decode($payload['body'], true);


        $payloadTeams = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'text' => $errorMessage->getMessage(),
                'title' => 'Erro Medição Eletrônica',
                'sections' => [[
                    'activityTitle' => 'Necessário Reprocessamento',
                    'activitySubtitle' => "Executar endpoint /sendDataMedicao",
                    'facts' => [
                        [
                            'name' => 'Payload',
                            'value' => json_encode($payload)
                        ]
                    ]
                ]]
            ])
        ];

        $this->request('POST', URL_WEBHOOK, $payloadTeams);
    }
}

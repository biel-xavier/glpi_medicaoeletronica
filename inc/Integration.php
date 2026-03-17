<?php

namespace GlpiPlugin\Medicaoeletronica;

use GuzzleHttp\Client as Guzzle;
use GlpiPlugin\Medicaoeletronica\HttpRequest;
use DateTime;
use Exception;
use Toolbox;

const URL_MEDICAO = 'https://medicao.advanta.com.br/Api/IntegracaoCervello.asmx';

/**
 * Integration Class
 **/
class Integration
{
    /**
     * Get configuration from database
     */
    private function getConfig()
    {
        global $DB;

        $configs = $DB->request([
            'FROM' => 'glpi_plugin_medicaoeletronica_configs',
            'LIMIT' => 1
        ]);

        foreach ($configs as $config) {
            return $config;
        }

        // Default fallback using the constant value or a sensible default
        return ['url' => 'https://medicao.advanta.com.br/Api/IntegracaoCervello.asmx', 'retries' => 3];
    }

    public function getDataTicket($ticketId)
    {

        try {
            $requestTicket = $this->requestDBTicket($ticketId);

            if (empty($requestTicket) || !isset($requestTicket[0])) {
                return null;
            }

            $payload = $this->preparePayload($ticketId, $requestTicket[0]);

            $destinationCity = $this->requestDBLocations($payload['cidade_destino']);
            $originCity = $this->requestDBLocations($payload['cidade_origem']);
            $costsTreated = $this->treatCosts($ticketId);
            $categories = $this->treatCategories($requestTicket[0]['CATEGORIA']);
            $lastFollowup = $this->treatFollowup($ticketId);
            $idPartner = $this->requestDBPartnerId($ticketId);

            $payload['cidade_destino'] = $this->verifyIfContentData($destinationCity, 0) ? $destinationCity[0]['town'] : '';

            $payload['cidade_origem'] =  $this->verifyIfContentData($originCity, 0) ? $originCity[0]['town'] : '';

            $payload['estado_destino'] = $this->verifyIfContentData($destinationCity, 0) ? $destinationCity[0]['state'] : '';

            $payload['estado_origem'] =  $this->verifyIfContentData($originCity, 0) ? $originCity[0]['state'] : '';

            $payload['descricao_localidade'] =  $this->verifyIfContentData($destinationCity, 0) ? $destinationCity[0]['name'] : 'descricao_localidade';

            $payload['custos_extras'] = $this->verifyIfContentData($costsTreated, 'sumCosts') ? number_format($costsTreated['sumCosts'], 2, '.', '') : '';

            $payload['des_material_utilizado'] = $this->verifyIfContentData($costsTreated, 'materialDescriptions') ? $costsTreated['materialDescriptions'] : '';

            $payload['des_ata_chamado_acao'] = $this->verifyIfContentData($categories, 'categorie') ? $categories['categorie'] : '';

            $payload['des_ata_equipto'] = $this->verifyIfContentData($categories, 'subCategorie') ? $categories['subCategorie'] : '';

            $payload['tipo_fechamento'] = 'Produtiva'; //$this->treatClosing($requestTicket[0]['tipo_fechamento']);

            $payload['historico_fechamento'] = (strlen($lastFollowup) > 0) ? $lastFollowup : 'historico_fechamento';

            $payload['cod_adm_usuario_parceiro'] = $this->verifyIfContentData($idPartner, 0) ? $idPartner[0]['ID'] : '';

            $payload['nro_oc'] = $ticketId;

            return $payload;
        } catch (Exception $e) {
            Toolbox::logInFile(
                'php-errors',
                sprintf(
                    __('%1$s: %2$s'),
                    "Medição Eletrônica: Catch Error",
                    sprintf(
                        __('Error during %s execution. Check in "%s" for more details.') . "\n",
                        print_r($e->getMessage(), true),
                        \GLPI_LOG_DIR . '/php-errors.log'
                    )
                )
            );
            return null;
        }
    }

    private function verifyIfContentData($data, $key)
    {
        if (is_array($data) && array_key_exists($key, $data)) {
            return true;
        } else {
            return false;
        }
    }

    public function requestDBTicket($ticketId)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_plugin_fields_groupdadosparceiros.locations_id_endereofield AS cidade_origem',
                'glpi_tickets.locations_id AS cidade_destino',
                'glpi_tickets.id AS cod_ata_chamado',
                'glpi_plugin_fields_contractdadoscentrodecustos.idcentrodecustofield AS cod_fin_ccusto',
                'glpi_contracts.num AS cod_int_fin_ccusto',
                'glpi_tickets.date AS data_abertura',
                'glpi_tickets.solvedate AS data_termino',
                'glpi_tickets.closedate AS data_fechamento',
                'glpi_tickets.solve_delay_stat AS Sla',
                'glpi_tickets.itilcategories_id AS CATEGORIA',
                'glpi_contracts.name AS des_fin_ccusto',
                'glpi_entities.name AS nom_adm_cliente',
                'glpi_entities.town AS nome_localidade',
                'glpi_plugin_fields_groupdadosparceiros.razosocialfield AS razao_social_parceiro',
                'glpi_plugin_costs_tickets.billable AS tipo_fechamento'
            ],
            'FROM' => 'glpi_tickets',
            'LEFT JOIN' => [
                'glpi_tickets_users' => [
                    'ON' => [
                        'glpi_tickets_users' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ],
                'glpi_groups_users' => [
                    'ON' => [
                        'glpi_groups_users' => 'users_id',
                        'glpi_tickets_users' => 'users_id'
                    ]
                ],
                'glpi_groups' => [
                    'ON' => [
                        'glpi_groups' => 'id',
                        'glpi_groups_users' => 'groups_id'
                    ]
                ],
                'glpi_plugin_fields_groupdadosparceiros' => [
                    'ON' => [
                        'glpi_plugin_fields_groupdadosparceiros' => 'items_id',
                        'glpi_groups' => 'id'
                    ]
                ],
                'glpi_tickets_contracts' => [
                    'ON' => [
                        'glpi_tickets_contracts' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ],
                'glpi_contracts' => [
                    'ON' => [
                        'glpi_contracts' => 'id',
                        'glpi_tickets_contracts' => 'contracts_id'
                    ]
                ],
                'glpi_plugin_fields_contractdadoscentrodecustos' => [
                    'ON' => [
                        'glpi_plugin_fields_contractdadoscentrodecustos' => 'items_id',
                        'glpi_contracts' => 'id'
                    ]
                ],
                'glpi_entities' => [
                    'ON' => [
                        'glpi_entities' => 'id',
                        'glpi_tickets' => 'entities_id'
                    ]
                ],
                'glpi_plugin_costs_tickets' => [
                    'ON' => [
                        'glpi_plugin_costs_tickets' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_tickets.id' => $ticketId,
                'glpi_tickets_users.type' => 2
            ]
        ];

        $dataRequestTicket = $DB->request($criteria);

        return iterator_to_array($dataRequestTicket);
    }

    public function preparePayload($ticketId, $dataRequestTicket)
    {

        $data_abertura = (new DateTime($dataRequestTicket['data_abertura']));
        $data_chegada = (new DateTime($dataRequestTicket['data_abertura']));
        $data_termino = (new DateTime($dataRequestTicket['data_termino']));
        $data_fechamento = (new DateTime($dataRequestTicket['data_fechamento']));

        $dataTicket = [
            'CHAVE_ACESSO' => "6efb3785-8aef-4350-acfa-9156a180e92b",
            'atach_garantia' => "",
            'chamado_unificado' => "NÃO",
            'cidade_destino' => $dataRequestTicket['cidade_destino'],
            'cidade_origem' => $dataRequestTicket['cidade_origem'],
            'cod_adm_usuario_parceiro' => "",
            'cod_ata_chamado' => $dataRequestTicket['cod_ata_chamado'],
            'cod_fin_ccusto' => $dataRequestTicket['cod_fin_ccusto'],
            'cod_int_fin_ccusto' => $dataRequestTicket['cod_int_fin_ccusto'],
            'cod_local_ata_localidade' => "cod_local_ata_localidade",
            'custos_extras' => 0,
            'data_abertura' => $data_abertura->format('Y-m-d') . 'T' . $data_abertura->format('H:i:s'),
            'data_chegada' => $data_abertura->format('Y-m-d') . 'T' . $data_abertura->format('H:i:s'),
            'data_termino' => $data_termino->format('Y-m-d') . 'T' . $data_termino->format('H:i:s'),
            'data_fechamento' => $data_fechamento->format('Y-m-d') . 'T' . $data_fechamento->format('H:i:s'),
            'Sla' => intVal(($dataRequestTicket['Sla'] / 60)),
            'des_ata_chamado_acao' => '',
            'des_ata_equipto' => '',
            'des_fin_ccusto' => $dataRequestTicket['des_fin_ccusto'],
            'des_material_utilizado' => "",
            'descricao_localidade' => "",
            'descricao_revisitas' => "",
            'estado_destino' => "",
            'estado_origem' => "",
            'garantia_preventiva' => "NÃO",
            'historico_fechamento' => '',
            'nom_adm_cliente' => $dataRequestTicket['des_fin_ccusto'], //$dataRequestTicket['nom_adm_cliente'],
            'nome_localidade' => strlen($dataRequestTicket['nome_localidade']) > 0 ? $dataRequestTicket['nome_localidade'] : 'nome_localidade',
            'nro_adv' => $ticketId,
            'nro_oc' => "",
            'parceiro_garantia' => "",
            'pendencia_documentacao_parceiro' => 0,
            'razao_social_parceiro' => $dataRequestTicket['razao_social_parceiro'],
            'servicos_prestados' => "",
            'tipo_fechamento' => 'tipo_fechamento',
            'sub_tipo_fechamento' => "sub_tipo_fechamento",
            'total_equipamentos' => 0,
            'total_revisitas' => 0,
            'total_revisitas_improdutivas' => 0,
            'total_revisitas_produtivas' => 0

        ];

        return $dataTicket;
    }

    public function treatFollowup($ticketId)
    {
        $dataFollowup = $this->requestDBFollowup($ticketId);

        if (count($dataFollowup) < 1) {
            $dataFollowup = "";
            return $dataFollowup;
        }

        $keyOfArr = max(array_keys($dataFollowup));

        $content = strip_tags($dataFollowup[$keyOfArr]['content']);

        $stringWithoutTags = html_entity_decode($content);

        $stringFinal = strip_tags($stringWithoutTags);

        return $stringFinal;
    }

    public function requestDBPartnerId($ticketId)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield as ID',
            ],
            'FROM' => 'glpi_tickets',
            'LEFT JOIN' => [
                'glpi_tickets_users' => [
                    'ON' => [
                        'glpi_tickets_users' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ],
                'glpi_groups_users' => [
                    'ON' => [
                        'glpi_groups_users' => 'users_id',
                        'glpi_tickets_users' => 'users_id'
                    ]
                ],
                'glpi_groups' => [
                    'ON' => [
                        'glpi_groups' => 'id',
                        'glpi_groups_users' => 'groups_id'
                    ]
                ],
                'glpi_plugin_fields_groupdadosparceiros' => [
                    'ON' => [
                        'glpi_plugin_fields_groupdadosparceiros' => 'items_id',
                        'glpi_groups' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_tickets.id' => intval($ticketId),
                'glpi_tickets_users.type' => 2
            ]
        ];

        $partnerId = $DB->request($criteria);

        return iterator_to_array($partnerId);
    }

    public function requestDBFollowup($ticketId)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_itilfollowups.id',
                'content'
            ],
            'FROM' => 'glpi_itilfollowups',
            'WHERE' => [
                'itemtype' => 'Ticket',
                'items_id' => $ticketId
            ]
        ];

        $followups = $DB->request($criteria);

        return iterator_to_array($followups);
    }

    public function treatCategories($categorieId)
    {
        $dataCategorie = $this->requestDBCategories($categorieId);

        if (empty($dataCategorie)) {
            return ['categorie' => '', 'subCategorie' => ''];
        }

        if ($dataCategorie[0]['itilcategories_id'] == 0) {
            return ['categorie' => $dataCategorie[0]['name'], 'subCategorie' => $dataCategorie[0]['name']];
        }

        $categoriePrincipal = $this->requestDBCategories($dataCategorie[0]['itilcategories_id']);

        return ['categorie' => $categoriePrincipal[0]['name'], 'subCategorie' => $dataCategorie[0]['name']];
    }

    public function requestDBCategories($categorieId)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_itilcategories.name',
                'glpi_itilcategories.itilcategories_id'
            ],
            'FROM' => 'glpi_itilcategories',
            'WHERE' => ['glpi_itilcategories.id' => intval($categorieId)]
        ];

        $categorie = $DB->request($criteria);

        return iterator_to_array($categorie);
    }

    public function treatCosts($ticketId)
    {
        $listCostTicket = $this->requestDBCosts($ticketId);

        $costs = [];
        $descriptionMaterial = [];


        foreach ($listCostTicket as $value) {
            $nameMaterial = $value['name'];
            $actionTime = $value['actiontime'];
            $costTime = $value['cost_time'];
            $costFixed = $value['cost_fixed'];
            $costMaterial = $value['cost_material'];

            if ($actionTime > 0) {
                $durationTime = $actionTime / 60;
                $costPerMinute = $costTime / 60;
                $valueCost = $durationTime * $costPerMinute;

                array_push($costs, $valueCost);
            }

            if ($costFixed > 0) {
                array_push($costs, $costFixed);
            }

            if ($costMaterial > 0) {
                array_push($costs, $costMaterial);

                $formatCostMaterial = number_format($costMaterial, 2, ',');
                array_push($descriptionMaterial, "Material: $nameMaterial - Qtde: 1 - Preço Unit $formatCostMaterial");
            }
        }
        $costsSum = array_sum($costs);

        return ['sumCosts' => $costsSum, 'materialDescriptions' => implode(' | ', $descriptionMaterial)];
    }

    public function requestDBLocations($locationId)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_locations.name',
                'glpi_locations.town',
                'glpi_locations.state'
            ],
            'FROM' => 'glpi_locations',
            'WHERE' => ['glpi_locations.id' => $locationId]
        ];

        $locations = $DB->request($criteria);

        return iterator_to_array($locations, false);
    }

    public function requestDBCosts($ticketId)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_ticketcosts.cost_time',
                'glpi_ticketcosts.cost_fixed',
                'glpi_ticketcosts.cost_material',
                'glpi_ticketcosts.actiontime',
                'glpi_ticketcosts.name'
            ],
            'FROM' => 'glpi_ticketcosts',
            'WHERE' => ['glpi_ticketcosts.tickets_id' => intval($ticketId)]
        ];


        $costs = $DB->request($criteria);

        return iterator_to_array($costs);
    }


    public function postDataMedicao($payload)
    {
        $config = $this->getConfig();
        $url = $config['url'];
        $maxRetries = $config['retries'];

        $http = new HttpRequest(new Guzzle());

        $headers = ['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => 'http://oakmontgroup.com.br/GravarChamado'];

        $xmlData = $this->arrayToXml($payload);

        $payloadRequest = [
            'headers' => $headers,
            'body' => $xmlData
        ];

        $lastResponse = null;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $http->request("POST", $url, $payloadRequest);
                $lastResponse = $response;

                if (isset($response['status']) && $response['status'] == 1) {
                    break;
                }
            } catch (Exception $e) {
                $lastResponse = ['status' => 0, 'error' => $e->getMessage()];
                if ($attempt >= $maxRetries) {
                    break;
                }
                sleep(pow(2, $attempt - 1));
            }
        }


        $validateResponse = $this->validateResponse($lastResponse);

        return $validateResponse;
    }

    private function validateResponse($data)
    {
        $response = $data;

        if ($response['status'] == 1) {
            $responseModel = $response;
        } else {

            $responseModel = [
                'status' => $response['status'],
                'response' => $response['response']->getBody()->getContents()
            ];

            $phrase = '/Chamado integrado com sucesso/';

            if (!$this->verifyIfPhraseInText($phrase, $responseModel['response'])) {
                $responseModel['status'] = 1;
            }
        };

        return $responseModel;
    }

    private function verifyIfPhraseInText($phrase, $text)
    {
        $data = preg_match($phrase, $text);
        if ($data != 1) {
            return false;
        }
        return true;
    }

    public function validateBeforeSendMedicao($payload)
    {
        $errors_alert = [];
        $fields_validating = [
            'cidade_destino',
            'cidade_origem',
            'cod_adm_usuario_parceiro',
            'cod_ata_chamado',
            'cod_fin_ccusto',
            'cod_int_fin_ccusto',
            'data_abertura',
            'data_chegada',
            'data_termino',
            'data_fechamento',
            'historico_fechamento',
            'nome_localidade',
            'nro_adv',
            'nro_oc',
            'razao_social_parceiro'
        ];

        foreach ($fields_validating as $validating) {
            if (!isset($payload[$validating])) {
                array_push($errors_alert, $validating);
            }
        }

        return $errors_alert;
    }

    public function arrayToXml($arr)
    {


        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
        $xml .= '<soap:Body>';
        $xml .= '<GravarChamado xmlns="http://oakmontgroup.com.br/">';

        foreach (array_keys($arr) as $data) {
            $key = $data;
            $value = $arr[$data];
            $xml .= "<" . $key . ">" . $value . "</" . $key . ">";
        }

        $xml .= '</GravarChamado>';
        $xml .= '</soap:Body>';
        $xml .= '</soap:Envelope>';

        return $xml;
    }
}

<?php

namespace GlpiPlugin\Medicaoeletronica;

use Toolbox;
use Session;

class PluginApi
{
    public static function getPartners()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            "SELECT" => [
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield as CODIGO_PARCEIRO',
                'glpi_plugin_fields_groupdadosparceiros.cnpjfield AS CNPJ',
                "glpi_plugin_fields_groupdadosparceiros.razosocialfield AS RAZAO_SOCIAL",
                "glpi_plugin_fields_groupdadosparceiros.nomefantasiafield AS NOME_FANTASIA",
                'glpi_plugin_fields_groupdadosparceiros.inscrioestadualfield AS INSCR_ESTADUAL',
                'glpi_locations.address AS ENDERECO',
                'glpi_locations.building AS NUMERO',
                'glpi_locations.comment AS COMPLEMENTO',
                'glpi_locations.town AS BAIRRO',
                'glpi_locations.town AS CIDADE',
                'glpi_locations.state AS ESTADO',
                'glpi_locations.postcode AS CEP'
            ],
            'FROM'         => 'glpi_groups',
            'LEFT JOIN'   => [
                'glpi_plugin_fields_groupdadosparceiros' => [
                    'ON' => [
                        'glpi_plugin_fields_groupdadosparceiros' => 'items_id',
                        'glpi_groups' => 'id'
                    ]
                ],
                'glpi_locations' => [
                    'ON' => [
                        'glpi_locations' => 'id',
                        'glpi_plugin_fields_groupdadosparceiros' => 'locations_id_endereofield'
                    ]
                ]
            ]
        ];
        $iterator = $DB->request($criteria);
        $data = [];
        foreach ($iterator as $result) {
            $result["ATIVO"] = 1;
            $data[] = $result;
        }


        return $data;
    }

    public static function getContactsPartner($id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            "SELECT" => [
                "glpi_plugin_fields_groupdadosparceiros.idparceirofield AS CODIGO_PARCEIRO",
                "glpi_plugin_fields_userdadoscontatos.idcontatofield AS CODIGO_PARCEIRO_CONTATO",
                "glpi_users.name AS NOME_CONTATO",
                "glpi_users.phone AS FONE_CONTATO",
                "glpi_users.phone AS FAX_CONTATO",
                "glpi_users.mobile AS CELULAR_CONTATO",
                "glpi_useremails.email AS EMAIL_CONTATO",
                "glpi_users.is_active AS ATIVO",
            ],
            'FROM' => 'glpi_groups_users',
            'LEFT JOIN'   => [
                'glpi_users' => [
                    'ON' => [
                        'glpi_users' => 'id',
                        'glpi_groups_users' => 'users_id'
                    ]
                ],
                'glpi_useremails' => [
                    'ON' => [
                        'glpi_useremails' => 'users_id',
                        'glpi_users' => 'id'
                    ]
                ],
                'glpi_plugin_fields_groupdadosparceiros' => [
                    'ON' => [
                        'glpi_plugin_fields_groupdadosparceiros' => 'items_id',
                        'glpi_groups_users' => 'groups_id'
                    ]
                ],
                'glpi_plugin_fields_userdadoscontatos' => [
                    'ON' => [
                        'glpi_plugin_fields_userdadoscontatos' => 'items_id',
                        'glpi_groups_users' => 'users_id'
                    ]
                ]
            ],
            'WHERE' => ['glpi_plugin_fields_groupdadosparceiros.idparceirofield' => $id]
        ];

        $iterator = $DB->request($criteria);
        $contacts = iterator_to_array($iterator);

        $validateContacts = [];
        $contactProcessed = [];

        foreach ($contacts as $contact) {
            if ($contact['CODIGO_PARCEIRO_CONTATO'] != null) {
                if (!in_array($contact['CODIGO_PARCEIRO_CONTATO'], $contactProcessed)) {
                    $contactProcessed[] = $contact['CODIGO_PARCEIRO_CONTATO'];
                    $validateContacts[] = $contact;
                }
            }
        }

        return $validateContacts;
    }

    public static function getContactsExecutivePartner($id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            "SELECT" => [
                "glpi_plugin_fields_groupdadosparceiros.idparceirofield AS CODIGO_PARCEIRO",
                "glpi_plugin_fields_userdadoscontatos.idcontatofield AS CODIGO_PARCEIRO_CONTATO_EXECUTIVO",
                "glpi_users.name AS NOME_CONTATO_EXECUTIVO",
                "glpi_usertitles.name AS CARGO_CONTATO_EXECUTIVO",
                "glpi_useremails.email AS EMAIL_CONTATO_EXECUTIVO",
                "glpi_users.is_active AS ATIVO",
            ],
            'FROM'         => 'glpi_groups_users',
            'LEFT JOIN'   => [
                'glpi_users' => [
                    'ON' => [
                        'glpi_users' => 'id',
                        'glpi_groups_users' => 'users_id'
                    ]
                ],
                'glpi_useremails' => [
                    'ON' => [
                        'glpi_useremails' => 'users_id',
                        'glpi_users' => 'id'
                    ]
                ],
                'glpi_usertitles' => [
                    'ON' => [
                        'glpi_usertitles' => 'id',
                        'glpi_users' => 'usertitles_id'
                    ]
                ],
                'glpi_plugin_fields_groupdadosparceiros' => [
                    'ON' => [
                        'glpi_plugin_fields_groupdadosparceiros' => 'items_id',
                        'glpi_groups_users' => 'groups_id'
                    ]
                ],
                'glpi_plugin_fields_userdadoscontatos' => [
                    'ON' => [
                        'glpi_plugin_fields_userdadoscontatos' => 'items_id',
                        'glpi_groups_users' => 'users_id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield' => $id,
                'glpi_users.usercategories_id' => 2
            ]
        ];

        $iterator = $DB->request($criteria);
        $contacts = iterator_to_array($iterator);

        $validateContacts = [];
        $contactProcessed = [];

        foreach ($contacts as $contact) {
            if ($contact['CODIGO_PARCEIRO_CONTATO_EXECUTIVO'] != null) {
                if (!in_array($contact['CODIGO_PARCEIRO_CONTATO_EXECUTIVO'], $contactProcessed)) {
                    $contactProcessed[] = $contact['CODIGO_PARCEIRO_CONTATO_EXECUTIVO'];
                    $validateContacts[] = $contact;
                }
            }
        }

        return $validateContacts;
    }

    public static function getPartnersCapillarity($id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            "SELECT" => [
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield as CODIGO_PARCEIRO',
                "glpi_groups.id AS CODIGO_PARCEIRO_CAPILARIDADE",
                'glpi_locations.town AS CIDADE_ORIGEM',
                'glpi_locations.state AS ESTADO_ORIGEM',
                'glpi_plugin_fields_groupdadosparceiros.plugin_fields_destinofielddropdowns_id AS LOCALIDADES_QUE_ATENDE'
            ],
            'FROM'         => 'glpi_groups',
            'LEFT JOIN'   => [
                'glpi_plugin_fields_groupdadosparceiros' => [
                    'ON' => [
                        'glpi_plugin_fields_groupdadosparceiros' => 'items_id',
                        'glpi_groups' => 'id'
                    ]
                ],
                'glpi_locations' => [
                    'ON' => [
                        'glpi_locations' => 'id',
                        'glpi_plugin_fields_groupdadosparceiros' => 'locations_id_endereofield'
                    ]
                ]
            ],
            'WHERE' => ['glpi_plugin_fields_groupdadosparceiros.idparceirofield' => $id]
        ];

        $dataPartners = iterator_to_array($DB->request($criteria));
        $data = [];

        foreach ($dataPartners as $result) {
            if (empty($result['LOCALIDADES_QUE_ATENDE'])) continue;
            $locale = json_decode($result['LOCALIDADES_QUE_ATENDE']);
            if (!is_array($locale)) continue;

            foreach ($locale as $idLocation) {
                $locCriteria = [
                    'SELECT' => ['name AS locale', 'plugin_fields_destinofielddropdowns_id AS id_ancestor'],
                    'FROM' => 'glpi_plugin_fields_destinofielddropdowns',
                    'WHERE' => ['id' => intval($idLocation)]
                ];
                $destinationLocaleCity = iterator_to_array($DB->request($locCriteria));
                if (empty($destinationLocaleCity)) continue;

                $ancCriteria = [
                    'SELECT' => ['name AS locale', 'plugin_fields_destinofielddropdowns_id AS id_ancestor'],
                    'FROM' => 'glpi_plugin_fields_destinofielddropdowns',
                    'WHERE' => ['id' => intval($destinationLocaleCity[0]['id_ancestor'])]
                ];
                $destinationLocaleState = iterator_to_array($DB->request($ancCriteria));
                if (empty($destinationLocaleState)) continue;

                $idOrigCriteria = [
                    'SELECT' => ['id as ID'],
                    'FROM' => 'glpi_states_towns',
                    'WHERE' => [
                        'state' => $result["ESTADO_ORIGEM"],
                        'town' => $result["CIDADE_ORIGEM"]
                    ]
                ];
                $idOriginArr = iterator_to_array($DB->request($idOrigCriteria));
                $idOrigin = $idOriginArr[0]['ID'] ?? '';

                $idDestCriteria = [
                    'SELECT' => ['id as ID'],
                    'FROM' => 'glpi_states_towns',
                    'WHERE' => [
                        'state' => $destinationLocaleState[0]["locale"],
                        'town' => $destinationLocaleCity[0]["locale"]
                    ]
                ];
                $idDestArr = iterator_to_array($DB->request($idDestCriteria));
                $idDestination = $idDestArr[0]['ID'] ?? '';

                $dataCapilarity = [
                    "CODIGO_PARCEIRO"               => $result["CODIGO_PARCEIRO"],
                    "CODIGO_PARCEIRO_CAPILARIDADE"  => intval($idOrigin . $idDestination),
                    "CIDADE_ORIGEM"                 => $result["CIDADE_ORIGEM"],
                    "ESTADO_ORIGEM"                 => $result["ESTADO_ORIGEM"],
                    "CIDADE_DESTINO"                => $destinationLocaleCity[0]["locale"],
                    "ESTADO_DESTINO"                => $destinationLocaleState[0]["locale"],
                    "ATIVO"                         => 1
                ];
                $data[] = $dataCapilarity;
            }
        }

        return $data;
    }

    public static function getCostCenter()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_plugin_fields_contractdadoscentrodecustos.idcentrodecustofield AS CODIGO_CENTRO_CUSTO',
                'glpi_contracts.num AS CODIGO_INTERNO',
                'glpi_contracts.name AS DESCRICAO_GLPI',
                'glpi_plugin_fields_contractdadoscentrodecustos.descricaofieldtwo AS DESCRICAO_MEDICAO',
                'glpi_plugin_fields_contractdadoscentrodecustos.eventofield AS EVENTO',
                'glpi_contracts.states_id AS ATIVO'
            ],
            'LEFT JOIN' => [
                'glpi_plugin_fields_contractdadoscentrodecustos' => [
                    'ON' => [
                        'glpi_plugin_fields_contractdadoscentrodecustos' => 'items_id',
                        'glpi_contracts' => 'id'
                    ]
                ]
            ],
            'FROM' => 'glpi_contracts',
        ];

        $dataContracts = iterator_to_array($DB->request($criteria));
        $stateActive = 3;
        $arrCostCenter = [];

        foreach ($dataContracts as $costCenter) {
            $arrCostCenter[] = [
                "CODIGO_CENTRO_CUSTO" => $costCenter['CODIGO_CENTRO_CUSTO'],
                "CODIGO_INTERNO"      => $costCenter['CODIGO_INTERNO'],
                "DESCRICAO"           => (empty($costCenter['DESCRICAO_MEDICAO'])) ? $costCenter['DESCRICAO_GLPI'] : $costCenter['DESCRICAO_MEDICAO'],
                "EVENTO"              => $costCenter['EVENTO'],
                "ATIVO"               => ($costCenter['ATIVO'] == $stateActive) ? 1 : 0
            ];
        }

        return $arrCostCenter;
    }

    public static function getDataTicket($id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_tickets.id AS CODIGO_ATACH',
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield AS CODIGO_PARCEIRO',
                'glpi_plugin_fields_groupdadosparceiros.razosocialfield AS RAZAO_SOCIAL_PARCEIRO',
                'glpi_entities.name AS RAZAO_SOCIAL_CLIENTE',
                'glpi_tickets.id AS NUMERO_OC_CLIENTE',
                'glpi_plugin_fields_contractdadoscentrodecustos.idcentrodecustofield AS CODIGO_CENTRO_CUSTO',
                'glpi_contracts.num AS CODIGO_INTERNO_CENTRO_CUSTO',
                'glpi_contracts.name AS DESCRICAO_CENTRO_CUSTO'
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
                'glpi_entities' => [
                    'ON' => [
                        'glpi_entities' => 'id',
                        'glpi_tickets' => 'entities_id'
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
            ],
            'WHERE' => [
                'glpi_tickets.id' => $id,
                'glpi_tickets_users.type' => 2
            ]
        ];

        $dataArr = iterator_to_array($DB->request($criteria));
        if (empty($dataArr)) {
            return [];
        }

        $data = $dataArr[0];
        $data['CODIGO_ATACH'] = (string)$data['CODIGO_ATACH'];
        $data['NUMERO_OC_CLIENTE'] = (string)$data['NUMERO_OC_CLIENTE'];

        return $data;
    }

    public static function forceSendMedicao($ticketId)
    {
        $integration = new Integration();
        $history = new History();

        $payload = $integration->getDataTicket($ticketId);
        $dataResponse = $integration->postDataMedicao($payload);

        $history->register(
            $ticketId,
            $integration->arrayToXml($payload),
            is_string($dataResponse['response']) ? $dataResponse['response'] : json_encode($dataResponse['response']),
            $dataResponse['status']
        );

        return $dataResponse;
    }
}

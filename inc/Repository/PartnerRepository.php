<?php

namespace GlpiPlugin\Medicaoeletronica\Repository;

class PartnerRepository
{
    public function findPartners(): array
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield as CODIGO_PARCEIRO',
                'glpi_plugin_fields_groupdadosparceiros.cnpjfield AS CNPJ',
                'glpi_plugin_fields_groupdadosparceiros.razosocialfield AS RAZAO_SOCIAL',
                'glpi_plugin_fields_groupdadosparceiros.nomefantasiafield AS NOME_FANTASIA',
                'glpi_plugin_fields_groupdadosparceiros.inscrioestadualfield AS INSCR_ESTADUAL',
                'glpi_locations.address AS ENDERECO',
                'glpi_locations.building AS NUMERO',
                'glpi_locations.comment AS COMPLEMENTO',
                'glpi_locations.town AS BAIRRO',
                'glpi_locations.town AS CIDADE',
                'glpi_locations.state AS ESTADO',
                'glpi_locations.postcode AS CEP'
            ],
            'FROM' => 'glpi_groups',
            'LEFT JOIN' => [
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

        $data = [];
        foreach ($DB->request($criteria) as $result) {
            $result['ATIVO'] = 1;
            $data[] = $result;
        }

        return $data;
    }

    public function findPartnerContacts(int $partnerId): array
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield AS CODIGO_PARCEIRO',
                'glpi_plugin_fields_userdadoscontatos.idcontatofield AS CODIGO_PARCEIRO_CONTATO',
                'glpi_users.name AS NOME_CONTATO',
                'glpi_users.phone AS FONE_CONTATO',
                'glpi_users.phone AS FAX_CONTATO',
                'glpi_users.mobile AS CELULAR_CONTATO',
                'glpi_useremails.email AS EMAIL_CONTATO',
                'glpi_users.is_active AS ATIVO',
            ],
            'FROM' => 'glpi_groups_users',
            'LEFT JOIN' => [
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
            'WHERE' => ['glpi_plugin_fields_groupdadosparceiros.idparceirofield' => $partnerId]
        ];

        return $this->deduplicateContacts(
            iterator_to_array($DB->request($criteria)),
            'CODIGO_PARCEIRO_CONTATO'
        );
    }

    public function findExecutiveContacts(int $partnerId): array
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield AS CODIGO_PARCEIRO',
                'glpi_plugin_fields_userdadoscontatos.idcontatofield AS CODIGO_PARCEIRO_CONTATO_EXECUTIVO',
                'glpi_users.name AS NOME_CONTATO_EXECUTIVO',
                'glpi_usertitles.name AS CARGO_CONTATO_EXECUTIVO',
                'glpi_useremails.email AS EMAIL_CONTATO_EXECUTIVO',
                'glpi_users.is_active AS ATIVO',
            ],
            'FROM' => 'glpi_groups_users',
            'LEFT JOIN' => [
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
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield' => $partnerId,
                'glpi_users.usercategories_id' => 2
            ]
        ];

        return $this->deduplicateContacts(
            iterator_to_array($DB->request($criteria)),
            'CODIGO_PARCEIRO_CONTATO_EXECUTIVO'
        );
    }

    public function findPartnersCapillarity(int $partnerId): array
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_plugin_fields_groupdadosparceiros.idparceirofield as CODIGO_PARCEIRO',
                'glpi_groups.id AS CODIGO_PARCEIRO_CAPILARIDADE',
                'glpi_locations.town AS CIDADE_ORIGEM',
                'glpi_locations.state AS ESTADO_ORIGEM',
                'glpi_plugin_fields_groupdadosparceiros.plugin_fields_destinofielddropdowns_id AS LOCALIDADES_QUE_ATENDE'
            ],
            'FROM' => 'glpi_groups',
            'LEFT JOIN' => [
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
            'WHERE' => ['glpi_plugin_fields_groupdadosparceiros.idparceirofield' => $partnerId]
        ];

        $data = [];
        foreach (iterator_to_array($DB->request($criteria)) as $result) {
            if (empty($result['LOCALIDADES_QUE_ATENDE'])) {
                continue;
            }

            $locations = json_decode($result['LOCALIDADES_QUE_ATENDE']);
            if (!is_array($locations)) {
                continue;
            }

            foreach ($locations as $locationId) {
                $city = iterator_to_array($DB->request([
                    'SELECT' => ['name AS locale', 'plugin_fields_destinofielddropdowns_id AS id_ancestor'],
                    'FROM'   => 'glpi_plugin_fields_destinofielddropdowns',
                    'WHERE'  => ['id' => (int) $locationId]
                ]));

                if (empty($city)) {
                    continue;
                }

                $state = iterator_to_array($DB->request([
                    'SELECT' => ['name AS locale', 'plugin_fields_destinofielddropdowns_id AS id_ancestor'],
                    'FROM'   => 'glpi_plugin_fields_destinofielddropdowns',
                    'WHERE'  => ['id' => (int) $city[0]['id_ancestor']]
                ]));

                if (empty($state)) {
                    continue;
                }

                $originRows = iterator_to_array($DB->request([
                    'SELECT' => ['id as ID'],
                    'FROM'   => 'glpi_plugin_medicaoeletronica_locations',
                    'WHERE'  => [
                        'state' => $result['ESTADO_ORIGEM'],
                        'town'  => $result['CIDADE_ORIGEM']
                    ]
                ]));

                $destinationRows = iterator_to_array($DB->request([
                    'SELECT' => ['id as ID'],
                    'FROM'   => 'glpi_plugin_medicaoeletronica_locations',
                    'WHERE'  => [
                        'state' => $state[0]['locale'],
                        'town'  => $city[0]['locale']
                    ]
                ]));

                $originId = $originRows[0]['ID'] ?? '';
                $destinationId = $destinationRows[0]['ID'] ?? '';

                $data[] = [
                    'CODIGO_PARCEIRO'              => $result['CODIGO_PARCEIRO'],
                    'CODIGO_PARCEIRO_CAPILARIDADE' => (int) ($originId . $destinationId),
                    'CIDADE_ORIGEM'                => $result['CIDADE_ORIGEM'],
                    'ESTADO_ORIGEM'                => $result['ESTADO_ORIGEM'],
                    'CIDADE_DESTINO'               => $city[0]['locale'],
                    'ESTADO_DESTINO'               => $state[0]['locale'],
                    'ATIVO'                        => 1,
                ];
            }
        }

        return $data;
    }

    private function deduplicateContacts(array $contacts, string $idField): array
    {
        $unique = [];
        $processed = [];

        foreach ($contacts as $contact) {
            $id = $contact[$idField] ?? null;
            if ($id === null || in_array($id, $processed, true)) {
                continue;
            }

            $processed[] = $id;
            $unique[] = $contact;
        }

        return $unique;
    }
}

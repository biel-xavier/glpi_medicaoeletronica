<?php

namespace GlpiPlugin\Medicaoeletronica\Repository;

class TicketRepository
{
    public function findIntegrationTicketData(int $ticketId): array
    {
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
                'glpi_tickets.id'        => $ticketId,
                'glpi_tickets_users.type' => 2
            ]
        ];

        return iterator_to_array($DB->request($criteria));
    }

    public function findPartnerId(int $ticketId): array
    {
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
                'glpi_tickets.id'        => $ticketId,
                'glpi_tickets_users.type' => 2
            ]
        ];

        return iterator_to_array($DB->request($criteria));
    }

    public function findFollowups(int $ticketId): array
    {
        global $DB;

        $criteria = [
            'SELECT' => ['glpi_itilfollowups.id', 'content'],
            'FROM'   => 'glpi_itilfollowups',
            'WHERE'  => [
                'itemtype' => 'Ticket',
                'items_id' => $ticketId
            ]
        ];

        return iterator_to_array($DB->request($criteria));
    }

    public function findCategory(int $categoryId): array
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_itilcategories.name',
                'glpi_itilcategories.itilcategories_id'
            ],
            'FROM'  => 'glpi_itilcategories',
            'WHERE' => ['glpi_itilcategories.id' => $categoryId]
        ];

        return iterator_to_array($DB->request($criteria));
    }

    public function findLocation(int $locationId): array
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_locations.name',
                'glpi_locations.town',
                'glpi_locations.state'
            ],
            'FROM'  => 'glpi_locations',
            'WHERE' => ['glpi_locations.id' => $locationId]
        ];

        return iterator_to_array($DB->request($criteria), false);
    }

    public function findTicketCosts(int $ticketId): array
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_ticketcosts.cost_time',
                'glpi_ticketcosts.cost_fixed',
                'glpi_ticketcosts.cost_material',
                'glpi_ticketcosts.actiontime',
                'glpi_ticketcosts.name'
            ],
            'FROM'  => 'glpi_ticketcosts',
            'WHERE' => ['glpi_ticketcosts.tickets_id' => $ticketId]
        ];

        return iterator_to_array($DB->request($criteria));
    }

    public function findApiTicketData(int $ticketId): array
    {
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
                'glpi_tickets.id'        => $ticketId,
                'glpi_tickets_users.type' => 2
            ]
        ];

        $rows = iterator_to_array($DB->request($criteria));
        if (empty($rows)) {
            return [];
        }

        $data = $rows[0];
        $data['CODIGO_ATACH'] = (string) $data['CODIGO_ATACH'];
        $data['NUMERO_OC_CLIENTE'] = (string) $data['NUMERO_OC_CLIENTE'];

        return $data;
    }
}

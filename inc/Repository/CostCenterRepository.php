<?php

namespace GlpiPlugin\Medicaoeletronica\Repository;

class CostCenterRepository
{
    public function findCostCenters(): array
    {
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

        $rows = iterator_to_array($DB->request($criteria));
        $activeState = 3;
        $result = [];

        foreach ($rows as $costCenter) {
            $result[] = [
                'CODIGO_CENTRO_CUSTO' => $costCenter['CODIGO_CENTRO_CUSTO'],
                'CODIGO_INTERNO'      => $costCenter['CODIGO_INTERNO'],
                'DESCRICAO'           => empty($costCenter['DESCRICAO_MEDICAO'])
                    ? $costCenter['DESCRICAO_GLPI']
                    : $costCenter['DESCRICAO_MEDICAO'],
                'EVENTO'              => $costCenter['EVENTO'],
                'ATIVO'               => ((int) $costCenter['ATIVO'] === $activeState) ? 1 : 0,
            ];
        }

        return $result;
    }
}

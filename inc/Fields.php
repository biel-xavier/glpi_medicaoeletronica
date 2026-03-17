<?php

namespace GlpiPlugin\Medicaoeletronica;

const TABLES_MEDICAO = [
    'user' => [
        'tableName' => 'glpi_plugin_fields_userdadoscontatos',
        'field' => 'idcontatofield'
    ],
    'contract' => [
        'tableName' => 'glpi_plugin_fields_contractdadoscentrodecustos',
        'field' => 'idmedicaofield'
    ],
    'group' => [
        'tableName' => 'glpi_plugin_fields_groupdadosparceiros',
        'field' => 'idparceirofield'
    ]
];


/**
 * Fields Class
 **/
class Fields
{


    public $tableName;
    private $field;

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setField($field)
    {
        $this->field = $field;
    }



    public function treatAfterCreated($id, $nameClass)
    {
        $this->setTableName(TABLES_MEDICAO[$nameClass]['tableName']);

        $this->setField(TABLES_MEDICAO[$nameClass]['field']);

        $lastIdMedicao = $this->verifyLastIdContact();

        $lastIdMedicao += 1;

        $responseInsertId = $this->insertDBField($id, $lastIdMedicao);

        return $responseInsertId;
    }

    public function verifyLastIdContact()
    {
        $datasField = $this->requestDBDataField();

        if (empty($datasField)) {
            return 0;
        }

        $majorId = max(array_values($datasField));

        return intval($majorId['ID']);
    }

    public function requestDBDataField()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $table = $this->getTableName();
        $field = $this->getField();

        $criteria = [
            'SELECT' => [
                "$table.$field AS ID"
            ],
            'FROM' => "$table"
        ];

        $data = $DB->request($criteria);

        return iterator_to_array($data);
    }

    public function insertDBField($id, $idMedicao)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tableName = $this->getTableName();

        $field = $this->getField();

        $params = [
            "$tableName.$field" => $idMedicao,
            "$tableName.items_id" => $id
        ];
        $data = $DB->insert($tableName, $params);

        return $data;
    }
}

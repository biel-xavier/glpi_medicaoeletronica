<?php

namespace GlpiPlugin\Medicaoeletronica;

use CommonDBTM;

class History extends CommonDBTM
{
    static $rightname = 'medicaoeletronica';

    public static function getTypeName($nb = 0)
    {
        return __('Histórico de Medição', 'medicaoeletronica');
    }

    public function post_addItem()
    {
        // Custom logic after adding history if needed
    }

    /**
     * Register a new attempt
     * 
     * @param int $tickets_id
     * @param mixed $payload
     * @param mixed $response
     * @param int $is_success (1 or 0)
     */
    public function register($tickets_id, $payload, $response, $is_success)
    {
        return $this->add([
            'tickets_id'    => $tickets_id,
            'payload'       => is_array($payload) ? json_encode($payload) : $payload,
            'response'      => is_array($response) ? json_encode($response) : $response,
            'is_success'    => $is_success,
            'date_creation' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ]);
    }
}

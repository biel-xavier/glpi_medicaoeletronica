<?php

namespace GlpiPlugin\Medicaoeletronica;

use CommonDBTM;

class Right extends CommonDBTM
{
    /**
     * Get rights for the plugin
     * 
     * @return array
     */
    public static function getRights()
    {
        return [
            'medicaoeletronica' => [
                'name'   => __('Medição Eletrônica', 'medicaoeletronica'),
                'rights' => [
                    READ   => __('Read'),
                    UPDATE => __('Update')
                ]
            ]
        ];
    }
}

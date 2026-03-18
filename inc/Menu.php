<?php

namespace GlpiPlugin\Medicaoeletronica;

use CommonGLPI;
use Session;

class Menu extends CommonGLPI
{
    public static $rightname = 'medicaoeletronica';

    public static function getTypeName($nb = 0)
    {
        return __('Medição Eletrônica', 'medicaoeletronica');
    }

    public static function getMenuName()
    {
        return self::getTypeName();
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight(static::$rightname, READ)) {
            return false;
        }

        return [
            'title'       => self::getMenuName(),
            'page'        => \Plugin::getWebDir('medicaoeletronica') . '/front/config.php',
            'links'       => [
                'search' => \Plugin::getWebDir('medicaoeletronica') . '/front/config.php',
                'config' => \Plugin::getWebDir('medicaoeletronica') . '/front/config.php',
            ],
            'config_page' => \Plugin::getWebDir('medicaoeletronica') . '/front/config.php',
            'icon'        => 'ti ti-plug-connected',
        ];
    }
}

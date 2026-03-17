<?php

/**
 * ---------------------------------------------------------------------
 * Medição Eletrônica plugin for GLPI
 * ---------------------------------------------------------------------
 */

use Glpi\Plugin\Hooks;

function plugin_init_medicaoeletronica()
{
    global $PLUGIN_HOOKS;
    error_log("Medicaoeletronica setup.php loaded");

    // Register autoloader FIRST before using any plugin classes
    spl_autoload_register(function ($class) {
        $prefix = 'GlpiPlugin\\Medicaoeletronica\\';
        $base_dir = __DIR__ . '/inc/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });

    $PLUGIN_HOOKS['csrf_compliant']['medicaoeletronica'] = true;

    $PLUGIN_HOOKS['api_controllers']['medicaoeletronica'] = [
        \GlpiPlugin\Medicaoeletronica\ApiController::class
    ];

    $PLUGIN_HOOKS[Hooks::API_MIDDLEWARE]['medicaoeletronica'] = [
        [
            'middleware' => \GlpiPlugin\Medicaoeletronica\SessionAuthMiddleware::class,
            'priority'   => 10,
            'condition'  => static fn(\Glpi\Api\HL\RoutePath $route_path) => false,
        ]
    ];

    $PLUGIN_HOOKS['config_page']['medicaoeletronica'] = 'front/config.php';

    // Hook automático: dispara integração ao fechar chamado com categoria configurada
    $PLUGIN_HOOKS['pre_item_update']['medicaoeletronica'] = [
        'Ticket' => ['GlpiPlugin\\Medicaoeletronica\\TicketHook', 'handlePreItemUpdate']
    ];
    $PLUGIN_HOOKS['item_update']['medicaoeletronica'] = [
        'Ticket' => ['GlpiPlugin\\Medicaoeletronica\\TicketHook', 'handleItemUpdate']
    ];

    // Show menu entry only if user has rights
    if (Session::haveRight('medicaoeletronica', READ)) {
        $PLUGIN_HOOKS['menu_entry']['medicaoeletronica'] = true;
    }

    // Register Profile tab using Plugin::registerClass (like Tag plugin)
    Plugin::registerClass('GlpiPlugin\\Medicaoeletronica\\ProfileClass', ['addtabon' => ['Profile']]);
}

function plugin_version_medicaoeletronica()
{
    return [
        'name'           => 'Medição Eletrônica',
        'version'        => '1.0.0',
        'author'         => 'Gabriel Xavier',
        'license'        => 'GPLv2+',
        'homepage'       => 'https://github.com/gabrielxavier',
        'minGlpiVersion' => '11.0.0'
    ];
}

function plugin_medicaoeletronica_check_prerequisites()
{
    if (version_compare(GLPI_VERSION, '11.0.0', '<')) {
        echo "Este plugin requer GLPI 11.0.0 ou superior";
        return false;
    }
    return true;
}

function plugin_medicaoeletronica_check_config($verbose = false)
{
    return true;
}

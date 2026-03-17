<?php

/**
 * Front-end configuration page for Medição Eletrônica plugin
 */

include('../../../inc/includes.php');

use GlpiPlugin\Medicaoeletronica\Config;

// Check if user has UPDATE rights
Session::checkRight('medicaoeletronica', UPDATE);

$config = new Config();

if (isset($_POST['update'])) {
    Session::checkRight('medicaoeletronica', UPDATE);

    global $DB;

    // Categorias: receber array e serializar como JSON
    $itilcategories = isset($_POST['itilcategories']) && is_array($_POST['itilcategories'])
        ? json_encode(array_map('intval', $_POST['itilcategories']))
        : json_encode([]);

    $DB->update('glpi_plugin_medicaoeletronica_configs', [
        'url'            => $_POST['url'],
        'retries'        => intval($_POST['retries']),
        'itilcategories' => $itilcategories,
        'date_mod'       => $_SESSION['glpi_currenttime']
    ], ['id' => intval($_POST['id'])]);

    Html::back();
} elseif (isset($_POST['add'])) {
    global $DB;

    $itilcategories = isset($_POST['itilcategories']) && is_array($_POST['itilcategories'])
        ? json_encode(array_map('intval', $_POST['itilcategories']))
        : json_encode([]);

    $DB->insert('glpi_plugin_medicaoeletronica_configs', [
        'url'            => $_POST['url'],
        'retries'        => intval($_POST['retries']),
        'itilcategories' => $itilcategories,
        'date_mod'       => $_SESSION['glpi_currenttime']
    ]);

    Html::back();
}

Html::header(__('Medição Eletrônica', 'medicaoeletronica'), $_SERVER['PHP_SELF'], "config", "plugins");

$config->showConfigForm();

Html::footer();

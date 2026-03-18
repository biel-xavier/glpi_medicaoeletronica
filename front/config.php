<?php

/**
 * Front-end configuration page for Medição Eletrônica plugin
 */

include('../../../inc/includes.php');

use GlpiPlugin\Medicaoeletronica\Config;
use GlpiPlugin\Medicaoeletronica\Repository\ConfigRepository;

// Check if user has UPDATE rights
Session::checkRight('medicaoeletronica', UPDATE);

$config = new Config();
$configRepository = new ConfigRepository();

if (isset($_POST['update'])) {
    Session::checkRight('medicaoeletronica', UPDATE);

    // Categorias: receber array e serializar como JSON
    $itilcategories = isset($_POST['itilcategories']) && is_array($_POST['itilcategories'])
        ? json_encode(array_map('intval', $_POST['itilcategories']))
        : json_encode([]);

    $configRepository->save([
        'url'            => $_POST['url'],
        'retries'        => intval($_POST['retries']),
        'itilcategories' => $itilcategories,
    ], intval($_POST['id']));

    Html::back();
} elseif (isset($_POST['add'])) {
    $itilcategories = isset($_POST['itilcategories']) && is_array($_POST['itilcategories'])
        ? json_encode(array_map('intval', $_POST['itilcategories']))
        : json_encode([]);

    $configRepository->save([
        'url'            => $_POST['url'],
        'retries'        => intval($_POST['retries']),
        'itilcategories' => $itilcategories,
    ]);

    Html::back();
}

Html::header(__('Medição Eletrônica', 'medicaoeletronica'), $_SERVER['PHP_SELF'], "config", "plugins");

$config->showConfigForm();

Html::footer();

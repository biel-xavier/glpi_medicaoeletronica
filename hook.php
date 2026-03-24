<?php

/**
 * ---------------------------------------------------------------------
 * Medição Eletrônica plugin for GLPI
 * ---------------------------------------------------------------------
 */

function plugin_medicaoeletronica_install()
{
    global $DB;
    $migration = new Migration(100);

    // Config Table
    if (!$DB->tableExists("glpi_plugin_medicaoeletronica_configs")) {
        $query = "CREATE TABLE `glpi_plugin_medicaoeletronica_configs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `url` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `retries` INT(11) DEFAULT '3',
            `itilcategories` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `date_mod` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    } else {
        // Migration: add itilcategories column if not exists
        $columns = $DB->listFields("glpi_plugin_medicaoeletronica_configs");
        if (!isset($columns['itilcategories'])) {
            $migration->addPostQuery(
                "ALTER TABLE `glpi_plugin_medicaoeletronica_configs`
                ADD COLUMN `itilcategories` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL"
            );
        }
    }

    // History Table
    if (!$DB->tableExists("glpi_plugin_medicaoeletronica_histories")) {
        $query = "CREATE TABLE `glpi_plugin_medicaoeletronica_histories` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `tickets_id` INT(11) NOT NULL,
            `payload` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `response` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `is_success` TINYINT(1) DEFAULT '0',
            `date_creation` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tickets_id` (`tickets_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    }

    if (!$DB->tableExists("glpi_plugin_medicaoeletronica_locations")) {
        $query = "CREATE TABLE `glpi_plugin_medicaoeletronica_locations` (
            `id` INT NOT NULL,
            `state` VARCHAR(2) NOT NULL,
            `town` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_state` (`state`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $migration->addPostQuery($query);
    }

    // Importar dados de locations (somente se tabela estiver vazia)
    $count = $DB->request([
        'COUNT' => 'cpt',
        'FROM'  => 'glpi_plugin_medicaoeletronica_locations'
    ])->current();

    if ($count['cpt'] == 0) {
        $sql_file = GLPI_ROOT . "/marketplace/medicaoeletronica/sql/glpi_plugin_medicaoeletronica_locations.sql";
        Toolbox::logInFile('php-errors', print_r($sql_file, true));
        plugin_medicaoeletronica_execute_sql_file($migration, $sql_file);
    }



    // Execute all migrations
    $migration->executeMigration();

    // Insert initial config if table is empty
    $count = $DB->request([
        'COUNT' => 'cpt',
        'FROM' => 'glpi_plugin_medicaoeletronica_configs'
    ])->current();

    if ($count['cpt'] == 0) {
        $DB->insert('glpi_plugin_medicaoeletronica_configs', [
            'url'            => 'https://webhook.site/65fcb50c-4209-4be3-b9d5-64962e091b6f',
            'retries'        => 3,
            'itilcategories' => null
        ]);
    }

    // Register rights only if not already registered
    $existing = $DB->request([
        'COUNT' => 'cpt',
        'FROM' => 'glpi_profilerights',
        'WHERE' => ['name' => 'medicaoeletronica']
    ])->current();

    if ($existing['cpt'] == 0) {
        \ProfileRight::addProfileRights(['medicaoeletronica']);
    }



    return true;
}

function plugin_medicaoeletronica_uninstall()
{
    global $DB;

    // $tables = [
    //     "glpi_plugin_medicaoeletronica_configs",
    //     "glpi_plugin_medicaoeletronica_histories",
    //     "glpi_plugin_medicaoeletronica_states"
    // ];

    // foreach ($tables as $table) {
    //     if ($DB->tableExists($table)) {
    //         $DB->dropTable($table);
    //     }
    // }

    return true;
}

function plugin_medicaoeletronica_execute_sql_file(Migration $migration, $file)
{
    if (!file_exists($file)) {
        return;
    }

    $sql = file_get_contents($file);

    foreach (explode(";", $sql) as $query) {
        $query = trim($query);

        if (!empty($query)) {
            $migration->addPostQuery($query);
        }
    }
}
<?php

namespace GlpiPlugin\Medicaoeletronica\Repository;

class ConfigRepository
{
    public function getConfig(): array
    {
        global $DB;

        $configs = $DB->request([
            'FROM'  => 'glpi_plugin_medicaoeletronica_configs',
            'LIMIT' => 1
        ]);

        foreach ($configs as $config) {
            return $config;
        }

        return [
            'id'             => 0,
            'url'            => 'https://medicao.advanta.com.br/Api/IntegracaoCervello.asmx',
            'retries'        => 3,
            'itilcategories' => null,
        ];
    }

    public function save(array $data, int $id = 0): void
    {
        global $DB;

        $payload = [
            'url'            => $data['url'],
            'retries'        => (int) $data['retries'],
            'itilcategories' => $data['itilcategories'],
            'date_mod'       => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            $DB->update('glpi_plugin_medicaoeletronica_configs', $payload, ['id' => $id]);
            return;
        }

        $DB->insert('glpi_plugin_medicaoeletronica_configs', $payload);
    }

    public function getConfiguredCategories(): array
    {
        $config = $this->getConfig();
        $categories = json_decode($config['itilcategories'] ?? '[]', true);

        if (!is_array($categories)) {
            return [];
        }

        return array_map('intval', $categories);
    }
}

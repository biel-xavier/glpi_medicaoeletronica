<?php

namespace GlpiPlugin\Medicaoeletronica;

use CommonDBTM;
use GlpiPlugin\Medicaoeletronica\Repository\ConfigRepository;
use Html;
use Session;

class Config extends CommonDBTM
{
    static $rightname = 'medicaoeletronica';

    public static function getTypeName($nb = 0)
    {
        return __('Medição Eletrônica - Configurações', 'medicaoeletronica');
    }

    /**
     * Retorna todas as categorias GLPI em hierarquia legível "Pai > Filho"
     */
    private function getAllCategories(): array
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'itilcategories_id'],
            'FROM'   => 'glpi_itilcategories',
            'ORDER'  => 'name ASC'
        ]);

        $categories = iterator_to_array($iterator, false);

        // Indexar por ID para busca rápida de pais
        $indexed = [];
        foreach ($categories as $cat) {
            $indexed[$cat['id']] = $cat;
        }

        // Compor label "Pai > Filho"
        $result = [];
        foreach ($indexed as $id => $cat) {
            $label = $cat['name'];
            if ($cat['itilcategories_id'] > 0 && isset($indexed[$cat['itilcategories_id']])) {
                $label = $indexed[$cat['itilcategories_id']]['name'] . ' > ' . $label;
            }
            $result[$id] = $label;
        }

        asort($result);
        return $result;
    }

    public function showConfigForm()
    {
        // Check if user has UPDATE rights
        Session::checkRight(static::$rightname, UPDATE);

        $config = (new ConfigRepository())->getConfig();

        // Categorias salvas na config
        $savedCategories = [];
        if (!empty($config['itilcategories'])) {
            $decoded = json_decode($config['itilcategories'], true);
            if (is_array($decoded)) {
                $savedCategories = array_map('intval', $decoded);
            }
        }

        $allCategories = $this->getAllCategories();

        $plugin_web_dir = \Plugin::getWebDir('medicaoeletronica');
        echo "<form action='" . $plugin_web_dir . "/front/config.php' method='post'>";
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='headerRow'><th colspan='2'>" . __('Configurações de Integração', 'medicaoeletronica') . "</th></tr>";

        // URL
        echo "<tr class='tab_bg_1'>";
        echo "<td>URL de Integração</td>";
        echo "<td><input type='text' name='url' value='" . htmlspecialchars($config['url']) . "' style='width: 80%'></td>";
        echo "</tr>";

        // Retries
        echo "<tr class='tab_bg_1'>";
        echo "<td>Quantidade de Retries</td>";
        echo "<td><input type='number' name='retries' value='" . intval($config['retries']) . "'></td>";
        echo "</tr>";

        // Categorias que disparam integração
        echo "<tr class='tab_bg_1'>";
        echo "<td style='vertical-align:top; padding-top:8px;'>";
        echo "<strong>Categorias que disparam integração</strong><br>";
        echo "<small style='color:#666'>(Status = Fechado + categoria abaixo → envia medição)</small>";
        echo "</td>";
        echo "<td>";
        echo "<select name='itilcategories[]' multiple style='width:80%; min-height:150px;'>";
        foreach ($allCategories as $catId => $catLabel) {
            $selected = in_array((int)$catId, $savedCategories, true) ? " selected" : "";
            echo "<option value='" . intval($catId) . "'" . $selected . ">" . htmlspecialchars($catLabel) . "</option>";
        }
        echo "</select>";
        echo "<br><small style='color:#888'>Segure Ctrl (ou Cmd) para selecionar múltiplas categorias.</small>";
        echo "</td>";
        echo "</tr>";

        // Submit
        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        if ($config['id'] > 0) {
            echo "<input type='hidden' name='id' value='" . $config['id'] . "'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
        } else {
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
        }
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</div>";
        Html::closeForm();
    }
}

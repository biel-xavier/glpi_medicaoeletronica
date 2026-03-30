<?php

namespace GlpiPlugin\Medicaoeletronica;

use CommonGLPI;
use CommonDBTM;
use GlpiPlugin\Medicaoeletronica\Repository\ConfigRepository;
use Html;
use Session;
use Ticket;

class History extends CommonDBTM
{
    static $rightname = 'medicaoeletronica_history';

    public static function getIcon()
    {
        return 'ti ti-api';
    }

    public static function getTypeName($nb = 0)
    {
        return __('Histórico de Medição', 'medicaoeletronica');
    }

    public function post_addItem()
    {
        // Custom logic after adding history if needed
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof Ticket || !Session::haveRight(self::$rightname, READ)) {
            return '';
        }

        if (!$this->shouldDisplayTabForTicket($item)) {
            return '';
        }

        return self::createTabEntry(
            __('Medição Eletrônica - Histories', 'medicaoeletronica'),
            0,
            $item::getType(),
            self::getIcon()
        );
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Ticket) {
            (new self())->showForTicket($item);
        }

        return true;
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

    public function showForTicket(Ticket $ticket): void
    {
        $histories = $this->findByTicket((int) $ticket->getID());

        echo "<div class='container-fluid'>";
        echo "<table class='table table-hover table-striped'>";
        echo "<thead><tr>";
        echo "<th>" . __('ID da tentativa', 'medicaoeletronica') . "</th>";
        echo "<th>" . __('Status', 'medicaoeletronica') . "</th>";
        echo "<th>" . __('O que foi enviado', 'medicaoeletronica') . "</th>";
        echo "<th>" . __('Retorno da API', 'medicaoeletronica') . "</th>";
        echo "<th>" . __('Data e hora', 'medicaoeletronica') . "</th>";
        echo "</tr></thead>";
        echo "<tbody>";

        if (count($histories) === 0) {
            echo "<tr><td colspan='5' class='center'>" . __('Nenhuma tentativa de envio encontrada.', 'medicaoeletronica') . "</td></tr>";
        } else {
            foreach ($histories as $history) {
                $isSuccess = (int) ($history['is_success'] ?? 0) === 1;
                $statusLabel = $isSuccess
                    ? __('Sucesso', 'medicaoeletronica')
                    : __('Falha', 'medicaoeletronica');
                $statusStyle = $isSuccess
                    ? 'background-color: #198754; color: #fff;'
                    : 'background-color: #dc3545; color: #fff;';

                echo "<tr>";
                echo "<td>" . (int) $history['id'] . "</td>";
                echo "<td><span class='badge' style='" . $statusStyle . "'>" . htmlescape($statusLabel) . "</span></td>";
                echo "<td><pre style='white-space: pre-wrap; word-break: break-word; margin: 0; max-width: 700px;'>" . htmlescape((string) ($history['payload'] ?? '')) . "</pre></td>";
                echo "<td><pre style='white-space: pre-wrap; word-break: break-word; margin: 0; max-width: 700px;'>" . htmlescape((string) ($history['response'] ?? '')) . "</pre></td>";
                echo "<td>" . Html::convDateTime($history['date_creation'] ?? null) . "</td>";
                echo "</tr>";
            }
        }

        echo "</tbody></table>";
        echo "</div>";
    }

    private function shouldDisplayTabForTicket(Ticket $ticket): bool
    {
        $categoryId = (int) ($ticket->fields['itilcategories_id'] ?? 0);

        $configuredCategories = (new ConfigRepository())->getConfiguredCategories();

        return in_array($categoryId, $configuredCategories, true);
    }

    private function findByTicket(int $ticketId): array
    {
        global $DB;

        $rows = [];
        $iterator = $DB->request([
            'FROM'   => 'glpi_plugin_medicaoeletronica_histories',
            'WHERE'  => [
                'tickets_id' => $ticketId
            ],
            'ORDER'  => [
                'date_creation DESC',
                'id DESC'
            ]
        ]);

        foreach ($iterator as $row) {
            $rows[] = $row;
        }

        return $rows;
    }
}

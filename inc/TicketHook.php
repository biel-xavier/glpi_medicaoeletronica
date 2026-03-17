<?php

namespace GlpiPlugin\Medicaoeletronica;

use Ticket;
use Session;
use Toolbox;

/**
 * TicketHook — controla o fechamento de chamados vinculados à Medição Eletrônica.
 *
 * pre_item_update → valida antes de salvar; bloqueia o fechamento e exibe
 *                   mensagem de erro na tela se os dados estiverem incompletos.
 * item_update     → depois do fechamento válido, dispara a integração.
 */
class TicketHook
{
    const STATUS_CLOSED = 6;

    // -------------------------------------------------------------------------
    // PRE_ITEM_UPDATE — bloqueia o fechamento se a validação falhar
    // -------------------------------------------------------------------------

    public static function handlePreItemUpdate(Ticket $ticket): void
    {
        global $DB;

        try {
            // Valor efetivo do status após a edição
            $newStatus = isset($ticket->input['status'])
                ? (int)$ticket->input['status']
                : (int)($ticket->fields['status'] ?? 0);

            if ($newStatus !== self::STATUS_CLOSED) {
                return;
            }

            // Categoria efetiva
            $categoryId = (int)(array_key_exists('itilcategories_id', $ticket->input)
                ? $ticket->input['itilcategories_id']
                : ($ticket->fields['itilcategories_id'] ?? 0));

            // Verificar categorias configuradas
            $configRow = $DB->request([
                'FROM'  => 'glpi_plugin_medicaoeletronica_configs',
                'LIMIT' => 1
            ])->current();

            if (empty($configRow) || empty($configRow['itilcategories'])) {
                return;
            }

            $configuredCategories = json_decode($configRow['itilcategories'], true);
            if (!is_array($configuredCategories) || empty($configuredCategories)) {
                return;
            }
            $configuredCategories = array_map('intval', $configuredCategories);

            if (!in_array($categoryId, $configuredCategories, true)) {
                return;
            }

            // Categoria bate → validar payload antes de permitir o fechamento
            $integration = new Integration();
            $ticketId    = $ticket->getID();
            $payload     = $integration->getDataTicket($ticketId);


            Toolbox::logInFile(
                'medicao_eletronica',
                "[MedicaoEletronica] Ticket #{$ticketId}: payload: " . print_r($payload, true) . "\n"
            );
            if (empty($payload)) {
                Session::addMessageAfterRedirect(
                    '<b>[Medição Eletrônica]</b> Não foi possível fechar o chamado: dados insuficientes para a integração (verifique se o chamado possui grupo técnico parceiro e contrato associados).',
                    true,
                    ERROR
                );
                $ticket->input = false;
                return;
            }

            $errors = $integration->validateBeforeSendMedicao($payload);
            if (!empty($errors)) {
                Session::addMessageAfterRedirect(
                    '<b>[Medição Eletrônica]</b> Não foi possível fechar o chamado. Campos obrigatórios ausentes ou inválidos: <b>'
                        . implode(', ', $errors) . '</b>.',
                    true,
                    ERROR
                );
                $ticket->input = false;
            }
        } catch (\Exception $e) {
            Toolbox::logInFile(
                'php-errors',
                '[MedicaoEletronica] pre_item_update exception: ' . $e->getMessage() . "\n"
            );
        }
    }

    // -------------------------------------------------------------------------
    // ITEM_UPDATE — dispara integração após fechamento válido
    // -------------------------------------------------------------------------

    public static function handleItemUpdate(Ticket $ticket): void
    {
        global $DB;

        try {
            if ((int)($ticket->fields['status'] ?? 0) !== self::STATUS_CLOSED) {
                return;
            }

            $configRow = $DB->request([
                'FROM'  => 'glpi_plugin_medicaoeletronica_configs',
                'LIMIT' => 1
            ])->current();

            if (empty($configRow) || empty($configRow['itilcategories'])) {
                return;
            }

            $configuredCategories = json_decode($configRow['itilcategories'], true);
            if (!is_array($configuredCategories) || empty($configuredCategories)) {
                return;
            }
            $configuredCategories = array_map('intval', $configuredCategories);

            $categoryId = (int)($ticket->fields['itilcategories_id'] ?? 0);
            if (!in_array($categoryId, $configuredCategories, true)) {
                return;
            }

            PluginApi::forceSendMedicao($ticket->getID());

            Toolbox::logInFile(
                'php-errors',
                "[MedicaoEletronica] Ticket #{$ticket->getID()}: integração disparada com sucesso via hook.\n"
            );
        } catch (\Exception $e) {
            Toolbox::logInFile(
                'php-errors',
                '[MedicaoEletronica] item_update hook exception: ' . $e->getMessage() . "\n"
            );
        }
    }
}

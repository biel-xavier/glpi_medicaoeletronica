<?php

namespace GlpiPlugin\Medicaoeletronica\Service;

use DateTime;
use Exception;
use GlpiPlugin\Medicaoeletronica\Repository\TicketRepository;
use Toolbox;

class MedicaoPayloadBuilder
{
    private TicketRepository $ticketRepository;

    public function __construct(?TicketRepository $ticketRepository = null)
    {
        $this->ticketRepository = $ticketRepository ?? new TicketRepository();
    }

    public function buildFromTicket(int $ticketId): ?array
    {
        try {
            $ticketRows = $this->ticketRepository->findIntegrationTicketData($ticketId);
            if (empty($ticketRows) || !isset($ticketRows[0])) {
                return null;
            }

            $ticketData = $ticketRows[0];
            $payload = $this->preparePayload($ticketId, $ticketData);

            $destinationCity = $this->ticketRepository->findLocation((int) $payload['cidade_destino']);
            $originCity = $this->ticketRepository->findLocation((int) $payload['cidade_origem']);
            $costs = $this->treatCosts($ticketId);
            $categories = $this->treatCategories((int) $ticketData['CATEGORIA']);
            $lastFollowup = $this->treatFollowup($ticketId);
            $partnerId = $this->ticketRepository->findPartnerId($ticketId);

            $payload['cidade_destino'] = $this->hasArrayIndex($destinationCity, 0) ? $destinationCity[0]['town'] : '';
            $payload['cidade_origem'] = $this->hasArrayIndex($originCity, 0) ? $originCity[0]['town'] : '';
            $payload['estado_destino'] = $this->hasArrayIndex($destinationCity, 0) ? $destinationCity[0]['state'] : '';
            $payload['estado_origem'] = $this->hasArrayIndex($originCity, 0) ? $originCity[0]['state'] : '';
            $payload['descricao_localidade'] = $this->hasArrayIndex($destinationCity, 0) ? $destinationCity[0]['name'] : 'descricao_localidade';
            $payload['custos_extras'] = isset($costs['sumCosts']) ? number_format($costs['sumCosts'], 2, '.', '') : '';
            $payload['des_material_utilizado'] = $costs['materialDescriptions'] ?? '';
            $payload['des_ata_chamado_acao'] = $categories['categorie'] ?? '';
            $payload['des_ata_equipto'] = $categories['subCategorie'] ?? '';
            $payload['tipo_fechamento'] = 'Produtiva';
            $payload['historico_fechamento'] = strlen($lastFollowup) > 0 ? $lastFollowup : 'historico_fechamento';
            $payload['cod_adm_usuario_parceiro'] = $this->hasArrayIndex($partnerId, 0) ? $partnerId[0]['ID'] : '';
            $payload['nro_oc'] = $ticketId;

            return $payload;
        } catch (Exception $e) {
            Toolbox::logInFile(
                'php-errors',
                sprintf(
                    __('%1$s: %2$s'),
                    'Medição Eletrônica: Catch Error',
                    sprintf(
                        __('Error during %s execution. Check in "%s" for more details.') . "\n",
                        print_r($e->getMessage(), true),
                        \GLPI_LOG_DIR . '/php-errors.log'
                    )
                )
            );

            return null;
        }
    }

    public function validateRequiredFields(array $payload): array
    {
        $errors = [];
        $requiredFields = [
            'cidade_destino',
            'cidade_origem',
            'cod_adm_usuario_parceiro',
            'cod_ata_chamado',
            'cod_fin_ccusto',
            'cod_int_fin_ccusto',
            'data_abertura',
            'data_chegada',
            'data_termino',
            'data_fechamento',
            'historico_fechamento',
            'nome_localidade',
            'nro_adv',
            'nro_oc',
            'razao_social_parceiro'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                $errors[] = $field;
            }
        }

        return $errors;
    }

    private function preparePayload(int $ticketId, array $ticketData): array
    {
        $dataAbertura = new DateTime($ticketData['data_abertura']);
        $dataTermino = new DateTime($ticketData['data_termino']);
        $dataFechamento = new DateTime($ticketData['data_fechamento']);

        return [
            'CHAVE_ACESSO'                  => '6efb3785-8aef-4350-acfa-9156a180e92b',
            'atach_garantia'                => '',
            'chamado_unificado'             => 'NÃO',
            'cidade_destino'                => $ticketData['cidade_destino'],
            'cidade_origem'                 => $ticketData['cidade_origem'],
            'cod_adm_usuario_parceiro'      => '',
            'cod_ata_chamado'               => $ticketData['cod_ata_chamado'],
            'cod_fin_ccusto'                => $ticketData['cod_fin_ccusto'],
            'cod_int_fin_ccusto'            => $ticketData['cod_int_fin_ccusto'],
            'cod_local_ata_localidade'      => 'cod_local_ata_localidade',
            'custos_extras'                 => 0,
            'data_abertura'                 => $dataAbertura->format('Y-m-d\TH:i:s'),
            'data_chegada'                  => $dataAbertura->format('Y-m-d\TH:i:s'),
            'data_termino'                  => $dataTermino->format('Y-m-d\TH:i:s'),
            'data_fechamento'               => $dataFechamento->format('Y-m-d\TH:i:s'),
            'Sla'                           => intval($ticketData['Sla'] / 60),
            'des_ata_chamado_acao'          => '',
            'des_ata_equipto'               => '',
            'des_fin_ccusto'                => $ticketData['des_fin_ccusto'],
            'des_material_utilizado'        => '',
            'descricao_localidade'          => '',
            'descricao_revisitas'           => '',
            'estado_destino'                => '',
            'estado_origem'                 => '',
            'garantia_preventiva'           => 'NÃO',
            'historico_fechamento'          => '',
            'nom_adm_cliente'               => $ticketData['des_fin_ccusto'],
            'nome_localidade'               => strlen($ticketData['nome_localidade']) > 0 ? $ticketData['nome_localidade'] : 'nome_localidade',
            'nro_adv'                       => $ticketId,
            'nro_oc'                        => '',
            'parceiro_garantia'             => '',
            'pendencia_documentacao_parceiro' => 0,
            'razao_social_parceiro'         => $ticketData['razao_social_parceiro'],
            'servicos_prestados'            => '',
            'tipo_fechamento'               => 'tipo_fechamento',
            'sub_tipo_fechamento'           => 'sub_tipo_fechamento',
            'total_equipamentos'            => 0,
            'total_revisitas'               => 0,
            'total_revisitas_improdutivas'  => 0,
            'total_revisitas_produtivas'    => 0,
        ];
    }

    private function treatFollowup(int $ticketId): string
    {
        $followups = $this->ticketRepository->findFollowups($ticketId);
        if (count($followups) < 1) {
            return '';
        }

        $content = strip_tags($followups[max(array_keys($followups))]['content']);
        return strip_tags(html_entity_decode($content));
    }

    private function treatCategories(int $categoryId): array
    {
        $categories = $this->ticketRepository->findCategory($categoryId);
        if (empty($categories)) {
            return ['categorie' => '', 'subCategorie' => ''];
        }

        if ((int) $categories[0]['itilcategories_id'] === 0) {
            return [
                'categorie'    => $categories[0]['name'],
                'subCategorie' => $categories[0]['name']
            ];
        }

        $parent = $this->ticketRepository->findCategory((int) $categories[0]['itilcategories_id']);

        return [
            'categorie'    => $parent[0]['name'] ?? '',
            'subCategorie' => $categories[0]['name'] ?? ''
        ];
    }

    private function treatCosts(int $ticketId): array
    {
        $costs = [];
        $descriptionMaterial = [];

        foreach ($this->ticketRepository->findTicketCosts($ticketId) as $value) {
            $actionTime = $value['actiontime'];
            $costTime = $value['cost_time'];
            $costFixed = $value['cost_fixed'];
            $costMaterial = $value['cost_material'];

            if ($actionTime > 0) {
                $costs[] = ($actionTime / 60) * ($costTime / 60);
            }

            if ($costFixed > 0) {
                $costs[] = $costFixed;
            }

            if ($costMaterial > 0) {
                $costs[] = $costMaterial;
                $descriptionMaterial[] = sprintf(
                    'Material: %s - Qtde: 1 - Preço Unit %s',
                    $value['name'],
                    number_format($costMaterial, 2, ',')
                );
            }
        }

        return [
            'sumCosts'             => array_sum($costs),
            'materialDescriptions' => implode(' | ', $descriptionMaterial),
        ];
    }

    private function hasArrayIndex($data, $key): bool
    {
        return is_array($data) && array_key_exists($key, $data);
    }
}

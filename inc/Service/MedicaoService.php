<?php

namespace GlpiPlugin\Medicaoeletronica\Service;

use GlpiPlugin\Medicaoeletronica\Client\MedicaoSoapClient;
use GlpiPlugin\Medicaoeletronica\History;
use GlpiPlugin\Medicaoeletronica\Repository\TicketRepository;

class MedicaoService
{
    private MedicaoPayloadBuilder $payloadBuilder;
    private MedicaoSoapClient $soapClient;
    private TicketRepository $ticketRepository;

    public function __construct(
        ?MedicaoPayloadBuilder $payloadBuilder = null,
        ?MedicaoSoapClient $soapClient = null,
        ?TicketRepository $ticketRepository = null
    ) {
        $this->payloadBuilder = $payloadBuilder ?? new MedicaoPayloadBuilder();
        $this->soapClient = $soapClient ?? new MedicaoSoapClient();
        $this->ticketRepository = $ticketRepository ?? new TicketRepository();
    }

    public function getDataTicket(int $ticketId): ?array
    {
        return $this->payloadBuilder->buildFromTicket($ticketId);
    }

    public function getApiTicketData(int $ticketId): array
    {
        return $this->ticketRepository->findApiTicketData($ticketId);
    }

    public function validateBeforeSendMedicao(array $payload): array
    {
        return $this->payloadBuilder->validateRequiredFields($payload);
    }

    public function forceSendMedicao(int $ticketId): array
    {
        $payload = $this->getDataTicket($ticketId);
        if ($payload === null) {
            return [
                'status'   => 1,
                'response' => 'Payload não pôde ser gerado'
            ];
        }

        $response = $this->soapClient->send($payload);
        (new History())->register(
            $ticketId,
            $this->soapClient->payloadToXml($payload),
            is_string($response['response'] ?? null) ? $response['response'] : json_encode($response['response'] ?? ''),
            (int) ($response['status'] ?? 1)
        );

        return $response;
    }

    public function arrayToXml(array $payload): string
    {
        return $this->soapClient->payloadToXml($payload);
    }
}

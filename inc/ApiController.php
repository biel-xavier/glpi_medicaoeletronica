<?php

namespace GlpiPlugin\Medicaoeletronica;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GlpiPlugin\Medicaoeletronica\Repository\CostCenterRepository;
use GlpiPlugin\Medicaoeletronica\Repository\PartnerRepository;
use GlpiPlugin\Medicaoeletronica\Service\MedicaoService;
use GlpiPlugin\Medicaoeletronica\SessionAuthMiddleware;

class ApiController extends AbstractController
{
    #[Route(path: '/medicaoeletronica/getPartners', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '1.0')]
    public function getPartners(Request $request): Response
    {
        $partners = (new PartnerRepository())->findPartners();
        return new JSONResponse($partners);
    }

    #[Route(path: '/medicaoeletronica/getContactsPartner', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '1.0')]
    public function getContactsPartner(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse([]);
        }
        $contacts = (new PartnerRepository())->findPartnerContacts((int) $id);
        return new JSONResponse($contacts);
    }

    #[Route(path: '/medicaoeletronica/getContactsExecutivePartner', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '1.0')]
    public function getContactsExecutivePartner(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse([]);
        }
        $contacts = (new PartnerRepository())->findExecutiveContacts((int) $id);
        return new JSONResponse($contacts);
    }

    #[Route(path: '/medicaoeletronica/getPartnersCapillarity', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '1.0')]
    public function getPartnersCapillarity(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse([]);
        }
        $data = (new PartnerRepository())->findPartnersCapillarity((int) $id);
        return new JSONResponse($data);
    }

    #[Route(path: '/medicaoeletronica/getCostCenter', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '1.0')]
    public function getCostCenter(Request $request): Response
    {
        $costs = (new CostCenterRepository())->findCostCenters();
        return new JSONResponse($costs);
    }

    #[Route(path: '/medicaoeletronica/getDataTicket', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '1.0')]
    public function getDataTicket(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse([]);
        }
        $data = (new MedicaoService())->getApiTicketData((int) $id);
        return new JSONResponse($data);
    }

    #[Route(path: '/medicaoeletronica/forceSendMedicao', methods: ['POST'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '1.0')]
    public function forceSendMedicao(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse(['error' => 'id é obrigatório'], 400);
        }
        $response = (new MedicaoService())->forceSendMedicao((int) $id);
        return new JSONResponse($response);
    }

    #[Route(path: '/medicaoeletronica/version', methods: ['GET'], security_level: Route::SECURITY_NONE)]
    #[RouteVersion(introduced: '1.0')]
    public function getVersion(Request $request): Response
    {
        return new JSONResponse([
            'plugin' => 'Medição Eletrônica',
            'version' => '1.0.0',
            'glpi' => GLPI_VERSION
        ]);
    }
}

<?php

namespace GlpiPlugin\Medicaoeletronica;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GlpiPlugin\Medicaoeletronica\SessionAuthMiddleware;

class ApiController extends AbstractController
{
    #[Route(path: '/medicaoeletronica/getPartners', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    public function getPartners(Request $request): Response
    {
        $partners = PluginApi::getPartners();
        return new JSONResponse($partners);
    }

    #[Route(path: '/medicaoeletronica/getContactsPartner', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    public function getContactsPartner(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse([]);
        }
        $contacts = PluginApi::getContactsPartner($id);
        return new JSONResponse($contacts);
    }

    #[Route(path: '/medicaoeletronica/getContactsExecutivePartner', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    public function getContactsExecutivePartner(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse([]);
        }
        $contacts = PluginApi::getContactsExecutivePartner($id);
        return new JSONResponse($contacts);
    }

    #[Route(path: '/medicaoeletronica/getPartnersCapillarity', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    public function getPartnersCapillarity(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse([]);
        }
        $data = PluginApi::getPartnersCapillarity($id);
        return new JSONResponse($data);
    }

    #[Route(path: '/medicaoeletronica/getCostCenter', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    public function getCostCenter(Request $request): Response
    {
        $costs = PluginApi::getCostCenter();
        return new JSONResponse($costs);
    }

    #[Route(path: '/medicaoeletronica/getDataTicket', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    public function getDataTicket(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse([]);
        }
        $data = PluginApi::getDataTicket($id);
        return new JSONResponse($data);
    }

    #[Route(path: '/medicaoeletronica/forceSendMedicao', methods: ['POST'], security_level: Route::SECURITY_NONE, middlewares: [SessionAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    public function forceSendMedicao(Request $request): Response
    {
        $id = $request->getParameter('id');
        if (!$id) {
            return new JSONResponse(['error' => 'id é obrigatório'], 400);
        }
        $response = PluginApi::forceSendMedicao($id);
        return new JSONResponse($response);
    }

    #[Route(path: '/medicaoeletronica/version', methods: ['GET'], security_level: Route::SECURITY_NONE)]
    #[RouteVersion(introduced: '2.0')]
    public function getVersion(Request $request): Response
    {
        return new JSONResponse([
            'plugin' => 'Medição Eletrônica',
            'version' => '1.0.0',
            'glpi' => GLPI_VERSION
        ]);
    }
}

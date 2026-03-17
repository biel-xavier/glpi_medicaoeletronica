<?php

namespace GlpiPlugin\Medicaoeletronica;

use Glpi\Api\HL\Middleware\AbstractMiddleware;
use Glpi\Api\HL\Middleware\RequestMiddlewareInterface;
use Glpi\Api\HL\Middleware\MiddlewareInput;
use Glpi\Http\JSONResponse;
use Session;

/**
 * Handle Session-Token and Glpi-Session-Token headers for authentication.
 */
class SessionAuthMiddleware extends AbstractMiddleware implements RequestMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        // If already authenticated by core (e.g. OAuth2 Bearer token or session cookies)
        if (Session::getLoginUserID() !== false) {
            $next($input);
            return;
        }

        $request = $input->request;
        $token = null;

        // Check for Session-Token headers (external requests)
        if ($request->hasHeader('Session-Token')) {
            $token = $request->getHeaderLine('Session-Token');
        } elseif ($request->hasHeader('Glpi-Session-Token')) {
            $token = $request->getHeaderLine('Glpi-Session-Token');
        }

        if ($token !== null && !empty($token)) {
            // Validate token format
            if (!preg_match('/^[a-zA-Z0-9,-]+$/', $token)) {
                $input->response = new JSONResponse([
                    'title' => 'Invalid Session Token',
                    'detail' => 'The Session-Token header contains invalid characters',
                    'status' => 'ERROR_INVALID_PARAMETER'
                ], 400);
                return;
            }

            $current_id = session_id();
            if ($token !== $current_id) {
                if (!empty($current_id)) {
                    session_write_close();
                }
                session_id($token);
                try {
                    Session::start();
                    Session::loadLanguage();
                } catch (\Throwable $e) {
                    $input->response = new JSONResponse([
                        'title' => 'Session Error',
                        'detail' => 'Failed to start session with the provided token',
                        'status' => 'ERROR_SESSION_FAILURE'
                    ], 401);
                    return;
                }
            }

            if (($user_id = Session::getLoginUserID()) !== false) {
                // Successful authentication with token
                $input->client = [
                    'client_id' => 'session_token',
                    'users_id'  => $user_id,
                    'scopes'    => ['api'],
                ];
                $input->response = null;
                $next($input);
                return;
            }
        } else {
            // No session token provided - try to use existing session cookies (internal calls)
            try {
                Session::start();
                if (($user_id = Session::getLoginUserID()) !== false) {
                    // Authenticated via session cookies (internal request)
                    $input->client = [
                        'client_id' => 'session_cookie',
                        'users_id'  => $user_id,
                        'scopes'    => ['api'],
                    ];
                    $input->response = null;
                    $next($input);
                    return;
                }
            } catch (\Throwable $e) {
                // Session cookie authentication failed, continue to error response
            }
        }

        // If we reach here, authentication failed.
        // Return 401 error.
        $input->response = new JSONResponse([
            'title' => 'You are not authenticated',
            'detail' => 'The Session-Token header is missing or invalid, and no valid session cookies found',
            'status' => 'ERROR_UNAUTHENTICATED',
        ], 401);
    }
}

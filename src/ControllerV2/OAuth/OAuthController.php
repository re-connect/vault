<?php

namespace App\ControllerV2\OAuth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    private const array QUERY_PARAMS = ['grant_type', 'client_id', 'client_secret', 'username', 'password', '_auth_code'];

    #[Route(path: '/oauth/v2/token', name: 'oauth_server_token_post_old', methods: ['GET', 'POST'])]
    public function forwardTokenAuthentication(Request $request): Response
    {
        foreach (self::QUERY_PARAMS as $paramName) {
            $request->request->set($paramName, $request->request->get($paramName) ?? $request->query->get($paramName));
        }

        return $this->forward('league.oauth2_server.controller.token::indexAction');
    }
}

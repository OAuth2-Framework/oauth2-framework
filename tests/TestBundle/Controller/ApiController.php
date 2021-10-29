<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Controller;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api')]
final class ApiController extends AbstractController
{
    #[Route(path: '/hello/{name}', name: 'api_hello')]
    public function serviceAction(string $name): Response
    {
        return new JsonResponse([
            'name' => $name,
            'message' => sprintf('Hello %s!', $name),
        ]);
    }

    /**
     * @OAuth2(scope="profile openid")
     */
    #[Route(path: '/hello-profile', name: 'api_scope')]
    public function scopeProtectionAction(): Response
    {
        return new JsonResponse([
            'name' => 'I am protected by scope',
            'message' => 'Hello!',
        ]);
    }

    /**
     * @OAuth2(token_type="MAC")
     */
    #[Route(path: '/hello-token', name: 'api_token')]
    public function tokenTypeProtectionAction(): Response
    {
        return new JsonResponse([
            'name' => 'I am protected by scope',
            'message' => 'Hello!',
        ]);
    }

    #[Route(path: '/hello-resolver', name: 'api_resolver')]
    public function accessTokenResolverAction(AccessToken $accessToken): Response
    {
        return new JsonResponse($accessToken);
    }
}

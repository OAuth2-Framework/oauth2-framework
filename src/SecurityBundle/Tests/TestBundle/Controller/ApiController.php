<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\SecurityBundle\Tests\TestBundle\Controller;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api")
 */
final class ApiController extends Controller
{
    /**
     * @Route("/hello/{name}", name="api_hello")
     */
    public function serviceAction(string $name): Response
    {
        return new JsonResponse(['name' => $name, 'message' => \Safe\sprintf('Hello %s!', $name)]);
    }

    /**
     * @OAuth2(scope="profile openid")
     * @Route("/hello-profile", name="api_scope")
     */
    public function scopeProtectionAction(): Response
    {
        return new JsonResponse(['name' => 'I am protected by scope', 'message' => 'Hello!']);
    }

    /**
     * @OAuth2(token_type="MAC")
     * @Route("/hello-token", name="api_token")
     */
    public function tokenTypeProtectionAction(): Response
    {
        return new JsonResponse(['name' => 'I am protected by scope', 'message' => 'Hello!']);
    }

    /**
     * @Route("/hello-resolver", name="api_resolver")
     */
    public function accessTokenResolverAction(AccessToken $accessToken): Response
    {
        return new JsonResponse($accessToken);
    }
}

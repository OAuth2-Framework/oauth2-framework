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

namespace OAuth2Framework\ServerBundle\Service;

use Assert\Assertion;
use Base64Url\Base64Url;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use Psr\Http\Message\ServerRequestInterface;
use function Safe\sprintf;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthorizationRequestSessionStorage implements AuthorizationRequestStorage
{
    /**
     * @var string
     */
    private const SESSION_AUTHORIZATION_REQUEST_ID_PARAMETER_NAME = 'oauth2_server.authorization_request.session_storage.id';
    private const SESSION_AUTHORIZATION_REQUEST_PARAMETER_NAME = 'oauth2_server.authorization_request.session_storage';

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function getId(ServerRequestInterface $request): string
    {
        return $this->session->get(self::SESSION_AUTHORIZATION_REQUEST_ID_PARAMETER_NAME) ?? Base64Url::encode(random_bytes(32));
    }

    public function set(string $authorizationId, AuthorizationRequest $authorization): void
    {
        $this->session->set(sprintf('/%s/%s', self::SESSION_AUTHORIZATION_REQUEST_PARAMETER_NAME, $authorizationId), $authorization);
        $this->session->save();
    }

    public function remove(string $authorizationId): ?AuthorizationRequest
    {
        $authorization = $this->session->get(sprintf('/%s/%s', self::SESSION_AUTHORIZATION_REQUEST_PARAMETER_NAME, $authorizationId));
        Assertion::nullOrIsInstanceOf($authorization, AuthorizationRequest::class, 'Invalid authorization ID.');

        return $authorization;
    }
}

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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use function Safe\sprintf;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionAuthorizationRequestStorage implements AuthorizationRequestStorage
{
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function set(string $authorizationId, AuthorizationRequest $authorization): void
    {
        $this->session->set(sprintf('/OAuth2-Authorization/%s', $authorizationId), $authorization);
    }

    public function remove(string $authorizationId): ?AuthorizationRequest
    {
        return $this->session->remove(sprintf('/OAuth2-Authorization/%s', $authorizationId));
    }
}

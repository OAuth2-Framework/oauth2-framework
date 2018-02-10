<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Rule;

use Base64Url\Base64Url;
use OAuth2Framework\Component\ClientRegistrationEndpoint\Rule\ClientRegistrationManagementRule as Base;
use OAuth2Framework\Component\Core\Client\ClientId;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ClientRegistrationManagementRule extends Base
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * ClientRegistrationManagementRule constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRegistrationClientUri(ClientId $clientId): string
    {
        return $this->router->generate('oauth2_server_client_configuration', ['client_id' => $clientId->getValue()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateRegistrationAccessToken(): string
    {
        return Base64Url::encode(random_bytes(512));
    }
}

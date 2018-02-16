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

namespace OAuth2Framework\Component\ClientAuthentication\Rule;

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class ClientAuthenticationMethodEndpointRule implements Rule
{
    /**
     * @var AuthenticationMethodManager
     */
    private $clientAuthenticationMethodManager;

    /**
     * ClientAuthenticationMethodEndpointRule constructor.
     *
     * @param AuthenticationMethodManager $clientAuthenticationMethodManager
     */
    public function __construct(AuthenticationMethodManager $clientAuthenticationMethodManager)
    {
        $this->clientAuthenticationMethodManager = $clientAuthenticationMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if (!$commandParameters->has('token_endpoint_auth_method')) {
            $commandParameters = $commandParameters->with('token_endpoint_auth_method', 'client_secret_basic');
        }

        if (!is_string($commandParameters->get('token_endpoint_auth_method'))) {
            throw new \InvalidArgumentException('The parameter "token_endpoint_auth_method" must be a string.');
        }
        if (!$this->clientAuthenticationMethodManager->has($commandParameters->get('token_endpoint_auth_method'))) {
            throw new \InvalidArgumentException(sprintf('The token endpoint authentication method "%s" is not supported. Please use one of the following values: %s', $commandParameters->get('token_endpoint_auth_method'), implode(', ', $this->clientAuthenticationMethodManager->list())));
        }

        $clientAuthenticationMethod = $this->clientAuthenticationMethodManager->get($commandParameters->get('token_endpoint_auth_method'));
        $validatedParameters = $clientAuthenticationMethod->checkClientConfiguration($commandParameters, $validatedParameters);

        $validatedParameters = $validatedParameters->with('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}

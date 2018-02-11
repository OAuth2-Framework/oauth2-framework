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

namespace OAuth2Framework\Component\ClientRule;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\TokenEndpoint\AuthenticationMethod\AuthenticationMethodManager;

class TokenEndpointAuthenticationMethodEndpointRule implements Rule
{
    /**
     * @var AuthenticationMethodManager
     */
    private $tokenEndpointAuthenticationMethodManager;

    /**
     * TokenEndpointAuthenticationMethodEndpointRule constructor.
     *
     * @param AuthenticationMethodManager $tokenEndpointAuthenticationMethodManager
     */
    public function __construct(AuthenticationMethodManager $tokenEndpointAuthenticationMethodManager)
    {
        $this->tokenEndpointAuthenticationMethodManager = $tokenEndpointAuthenticationMethodManager;
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
        if (!$this->tokenEndpointAuthenticationMethodManager->has($commandParameters->get('token_endpoint_auth_method'))) {
            throw new \InvalidArgumentException(sprintf('The token endpoint authentication method "%s" is not supported. Please use one of the following values: %s', $commandParameters->get('token_endpoint_auth_method'), implode(', ', $this->tokenEndpointAuthenticationMethodManager->list())));
        }

        $tokenEndpointAuthenticationMethod = $this->tokenEndpointAuthenticationMethodManager->get($commandParameters->get('token_endpoint_auth_method'));
        $validatedParameters = $tokenEndpointAuthenticationMethod->checkClientConfiguration($commandParameters, $validatedParameters);

        $validatedParameters = $validatedParameters->with('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}

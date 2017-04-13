<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\Client\Rule;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;

final class TokenEndpointAuthMethodEndpointRule implements RuleInterface
{
    /**
     * @var TokenEndpointAuthMethodManager
     */
    private $tokenEndpointAuthMethodManager;

    /**
     * TokenEndpointAuthMethodEndpointRule constructor.
     *
     * @param TokenEndpointAuthMethodManager $tokenEndpointAuthMethodManager
     */
    public function __construct(TokenEndpointAuthMethodManager $tokenEndpointAuthMethodManager)
    {
        $this->tokenEndpointAuthMethodManager = $tokenEndpointAuthMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        Assertion::true($commandParameters->has('token_endpoint_auth_method'), 'The parameter \'token_endpoint_auth_method\' is missing.');
        Assertion::string($commandParameters->get('token_endpoint_auth_method'), 'The parameter \'token_endpoint_auth_method\' must be a string.');
        Assertion::true($this->tokenEndpointAuthMethodManager->has($commandParameters->get('token_endpoint_auth_method')), sprintf('The token endpoint authentication method \'%s\' is not supported. Please use one of the following values: %s', $commandParameters->get('token_endpoint_auth_method'), implode(', ', $this->tokenEndpointAuthMethodManager->all())));

        $tokenEndpointAuthMethod = $this->tokenEndpointAuthMethodManager->get($commandParameters->get('token_endpoint_auth_method'));
        $validatedParameters = $tokenEndpointAuthMethod->checkClientConfiguration($commandParameters, $validatedParameters);

        $validatedParameters = $validatedParameters->with('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}

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
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;

final class RedirectionUriRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if (!$commandParameters->has('redirect_uris')) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'The parameter \'redirect_uris\' is mandatory.']);
        }
        $validatedParameters = $next($commandParameters, $validatedParameters, $userAccountId);
        $redirectUris = $commandParameters->get('redirect_uris');
        Assertion::isArray($redirectUris, 'The parameter \'redirect_uris\' must be a list of URI.');
        Assertion::allUrl($redirectUris, 'The parameter \'redirect_uris\' must be a list of URI.');
        $this->checkRedirectUris($validatedParameters, $redirectUris);
        $validatedParameters = $validatedParameters->with('redirect_uris', $redirectUris);

        return $validatedParameters;
    }

    /**
     * @param DataBag $validatedParameters
     * @param array   $redirectUris
     */
    private function checkRedirectUris(DataBag $validatedParameters, array $redirectUris)
    {
        $application_type = $validatedParameters->has('application_type') ? $validatedParameters->get('application_type') : 'web';
        $response_types = $validatedParameters->has('response_types') ? $validatedParameters->get('response_types') : [];
        $uses_implicit_grant_type = false;
        foreach ($response_types as $response_type) {
            if (false !== strpos($response_type, 'token')) {
                $uses_implicit_grant_type = true;
            }
        }

        foreach ($redirectUris as $redirectUri) {
            $parsed = parse_url($redirectUri);
            Assertion::keyNotExists($parsed, 'fragment', 'The parameter \'redirect_uris\' must only contain URIs without fragment.');
            if ('web' === $application_type && true === $uses_implicit_grant_type) {
                Assertion::notEq($parsed['host'], 'localhost', 'The host \'localhost\' is not allowed for web applications that use the Implicit Grant Type.');
                Assertion::eq($parsed['scheme'], 'https', 'The parameter \'redirect_uris\' must only contain URIs with the HTTPS scheme for web applications that use the Implicit Grant Type.');
            }
        }
        //FIXME: allow servers to add custom constraints
    }
}

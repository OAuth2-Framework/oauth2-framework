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

namespace OAuth2Framework\Component\ClientRule;

use InvalidArgumentException;
use function League\Uri\parse;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use function Safe\preg_match;

/**
 * TODO: If there are multiple hostnames in the registered redirect_uris and pairwise ID is set, the client MUST register a sector_identifier_uri.
 */
final class RedirectionUriRule implements Rule
{
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        /** @var DataBag $validatedParameters */
        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);

        // No need for redirect URIs as no response type is used.
        if (!$validatedParameters->has('response_types') || 0 === \count($validatedParameters->get('response_types'))) {
            $validatedParameters->set('redirect_uris', []);

            return $validatedParameters;
        }

        if (!$validatedParameters->has('token_endpoint_auth_method')) {
            throw new InvalidArgumentException('Unable to determine the token endpoint authentication method.');
        }
        $isClientPublic = 'none' === $validatedParameters->get('token_endpoint_auth_method');

        $applicationType = $validatedParameters->has('application_type') ? $validatedParameters->get('application_type') : 'web';
        $response_types = $validatedParameters->has('response_types') ? $validatedParameters->get('response_types') : [];
        $usesImplicitGrantType = false;
        foreach ($response_types as $response_type) {
            $types = \explode(' ', $response_type);
            if (\in_array('token', $types, true)) {
                $usesImplicitGrantType = true;

                break;
            }
        }

        if (!$commandParameters->has('redirect_uris')) {
            if ($isClientPublic) {
                throw new InvalidArgumentException('Non-confidential clients must register at least one redirect URI.');
            }
            if ($usesImplicitGrantType) {
                throw new InvalidArgumentException('Confidential clients must register at least one redirect URI when using the "token" response type.');
            }
            $redirectUris = [];
        } else {
            $redirectUris = $commandParameters->get('redirect_uris');
            if (!\is_array($redirectUris)) {
                throw new InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
            }
            $this->checkAllUris($redirectUris, $applicationType, $usesImplicitGrantType, $isClientPublic);
        }

        $validatedParameters->set('redirect_uris', $redirectUris);

        return $validatedParameters;
    }

    private function checkAllUris(array $value, string $applicationType, bool $usesImplicitGrantType, bool $isClientPublic): void
    {
        foreach ($value as $redirectUri) {
            if (!\is_string($redirectUri)) {
                throw new InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
            }
            $this->checkUri($redirectUri, $applicationType, $usesImplicitGrantType);
        }
    }

    private function checkUri(string $uri, string $applicationType, bool $usesImplicitGrantType): void
    {
        if (0 === mb_strpos($uri, 'urn:', 0, '8bit')) {
            $this->checkUrn($uri);
        } else {
            $parsed = parse($uri);
            if (null === $parsed['scheme'] || null === $parsed['path']) {
                throw new InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
            }
            if (1 === preg_match('#/\.\.?(/|$)#', $parsed['path'])) {
                throw new InvalidArgumentException('The URI listed in the "redirect_uris" parameter must not contain any path traversal.');
            }
            if (null !== $parsed['fragment']) {
                throw new InvalidArgumentException('The parameter "redirect_uris" must only contain URIs without fragment.');
            }
            if ('web' === $applicationType && true === $usesImplicitGrantType) {
                if ('localhost' === $parsed['host']) {
                    throw new InvalidArgumentException('The host "localhost" is not allowed for web applications that use the Implicit Grant Type.');
                }
                if ('https' !== $parsed['scheme']) {
                    throw new InvalidArgumentException('The parameter "redirect_uris" must only contain URIs with the HTTPS scheme for web applications that use the Implicit Grant Type.');
                }
            }
        }
    }

    private function checkUrn(string $urn): void
    {
        if (1 !== preg_match('/^urn:[a-z0-9][a-z0-9-]{0,31}:([a-z0-9()+,-.:=@;$_!*\']|%(0[1-9a-f]|[1-9a-f][0-9a-f]))+$/i', $urn)) {
            throw new InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
        }
    }
}

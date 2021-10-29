<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRule;

use function count;
use function in_array;
use InvalidArgumentException;
use function is_array;
use function is_string;
use function League\Uri\parse;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

/**
 * TODO: If there are multiple hostnames in the registered redirect_uris and pairwise ID is set, the client MUST
 * register a sector_identifier_uri.
 */
final class RedirectionUriRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        /** @var DataBag $validatedParameters */
        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);

        // No need for redirect URIs as no response type is used.
        if (! $validatedParameters->has('response_types') || count($validatedParameters->get('response_types')) === 0) {
            $validatedParameters->set('redirect_uris', []);

            return $validatedParameters;
        }

        if (! $validatedParameters->has('token_endpoint_auth_method')) {
            throw new InvalidArgumentException('Unable to determine the token endpoint authentication method.');
        }
        $isClientPublic = $validatedParameters->get('token_endpoint_auth_method') === 'none';

        $applicationType = $validatedParameters->has('application_type') ? $validatedParameters->get(
            'application_type'
        ) : 'web';
        $response_types = $validatedParameters->has('response_types') ? $validatedParameters->get(
            'response_types'
        ) : [];
        $usesImplicitGrantType = false;
        foreach ($response_types as $response_type) {
            $types = explode(' ', $response_type);
            if (in_array('token', $types, true)) {
                $usesImplicitGrantType = true;

                break;
            }
        }

        if (! $commandParameters->has('redirect_uris')) {
            if ($isClientPublic) {
                throw new InvalidArgumentException('Non-confidential clients must register at least one redirect URI.');
            }
            if ($usesImplicitGrantType) {
                throw new InvalidArgumentException(
                    'Confidential clients must register at least one redirect URI when using the "token" response type.'
                );
            }
            $redirectUris = [];
        } else {
            $redirectUris = $commandParameters->get('redirect_uris');
            if (! is_array($redirectUris)) {
                throw new InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
            }
            $this->checkAllUris($redirectUris, $applicationType, $usesImplicitGrantType, $isClientPublic);
        }

        $validatedParameters->set('redirect_uris', $redirectUris);

        return $validatedParameters;
    }

    private function checkAllUris(array $value, string $applicationType, bool $usesImplicitGrantType): void
    {
        foreach ($value as $redirectUri) {
            if (! is_string($redirectUri)) {
                throw new InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
            }
            $this->checkUri($redirectUri, $applicationType, $usesImplicitGrantType);
        }
    }

    private function checkUri(string $uri, string $applicationType, bool $usesImplicitGrantType): void
    {
        if (mb_strpos($uri, 'urn:', 0, '8bit') === 0) {
            $this->checkUrn($uri);
        } else {
            $parsed = parse($uri);
            if ($parsed['scheme'] === null || $parsed['path'] === null) {
                throw new InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
            }
            if (preg_match('#/\.\.?(/|$)#', $parsed['path']) === 1) {
                throw new InvalidArgumentException(
                    'The URI listed in the "redirect_uris" parameter must not contain any path traversal.'
                );
            }
            if ($parsed['fragment'] !== null) {
                throw new InvalidArgumentException(
                    'The parameter "redirect_uris" must only contain URIs without fragment.'
                );
            }
            if ($applicationType === 'web' && $usesImplicitGrantType === true) {
                if ($parsed['host'] === 'localhost') {
                    throw new InvalidArgumentException(
                        'The host "localhost" is not allowed for web applications that use the Implicit Grant Type.'
                    );
                }
                if ($parsed['scheme'] !== 'https') {
                    throw new InvalidArgumentException(
                        'The parameter "redirect_uris" must only contain URIs with the HTTPS scheme for web applications that use the Implicit Grant Type.'
                    );
                }
            }
        }
    }

    private function checkUrn(string $urn): void
    {
        if (preg_match(
            '/^urn:[a-z0-9][a-z0-9-]{0,31}:([a-z0-9()+,-.:=@;$_!*\']|%(0[1-9a-f]|[1-9a-f][0-9a-f]))+$/i',
            $urn
        ) !== 1) {
            throw new InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
        }
    }
}

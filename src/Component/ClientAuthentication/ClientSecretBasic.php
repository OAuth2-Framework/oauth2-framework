<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ClientAuthentication;

use Base64Url\Base64Url;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use Psr\Http\Message\ServerRequestInterface;
use function Safe\base64_decode;
use function Safe\sprintf;

final class ClientSecretBasic implements AuthenticationMethod
{
    /**
     * @var string
     */
    private $realm;

    /**
     * @var int
     */
    private $secretLifetime;

    public function __construct(string $realm, int $secretLifetime = 0)
    {
        if ($secretLifetime < 0) {
            throw new \InvalidArgumentException('The secret lifetime must be at least 0 (= unlimited).');
        }

        $this->realm = $realm;
        $this->secretLifetime = $secretLifetime;
    }

    public function getSchemesParameters(): array
    {
        return [
            sprintf('Basic realm="%s",charset="UTF-8"', $this->realm),
        ];
    }

    /**
     * @param null|mixed $clientCredentials
     */
    public function findClientIdAndCredentials(ServerRequestInterface $request, &$clientCredentials = null): ?ClientId
    {
        $authorization_headers = $request->getHeader('Authorization');
        if (0 < \count($authorization_headers)) {
            foreach ($authorization_headers as $authorization_header) {
                $clientId = $this->findClientIdAndCredentialsInAuthorizationHeader($authorization_header, $clientCredentials);
                if (null !== $clientId) {
                    return $clientId;
                }
            }
        }

        return null;
    }

    public function checkClientConfiguration(DataBag $command_parameters, DataBag $validated_parameters): DataBag
    {
        $validated_parameters->set('client_secret', $this->createClientSecret());
        $validated_parameters->set('client_secret_expires_at', (0 === $this->secretLifetime ? 0 : time() + $this->secretLifetime));

        return $validated_parameters;
    }

    /**
     * @param null|mixed $clientCredentials
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool
    {
        return hash_equals($client->get('client_secret'), $clientCredentials);
    }

    public function getSupportedMethods(): array
    {
        return ['client_secret_basic'];
    }

    private function findClientIdAndCredentialsInAuthorizationHeader(string $authorization_header, ?string &$clientCredentials = null): ?ClientId
    {
        if ('basic ' === mb_strtolower(mb_substr($authorization_header, 0, 6, '8bit'), '8bit')) {
            list($client_id, $client_secret) = explode(':', base64_decode(mb_substr($authorization_header, 6, mb_strlen($authorization_header, '8bit') - 6, '8bit'), true));
            if ('' !== $client_id && '' !== $client_secret) {
                $clientCredentials = $client_secret;

                return new ClientId($client_id);
            }
        }

        return null;
    }

    private function createClientSecret(): string
    {
        return Base64Url::encode(random_bytes(32));
    }
}

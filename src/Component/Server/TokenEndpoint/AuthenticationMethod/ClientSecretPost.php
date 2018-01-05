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

namespace OAuth2Framework\Component\Server\TokenEndpoint\AuthenticationMethod;

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use Psr\Http\Message\ServerRequestInterface;

final class ClientSecretPost implements TokenEndpointAuthenticationMethod
{
    /**
     * @var int
     */
    private $secretLifetime;

    /**
     * ClientSecretPost constructor.
     *
     * @param int $secretLifetime
     */
    public function __construct(int $secretLifetime = 0)
    {
        if ($secretLifetime < 0) {
            throw new \InvalidArgumentException('The secret lifetime must be at least 0 (= unlimited).');
        }

        $this->secretLifetime = $secretLifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemesParameters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function findClientId(ServerRequestInterface $request, &$clientCredentials = null): ? ClientId
    {
        $parameters = $request->getParsedBody() ?? [];
        if (array_key_exists('client_id', $parameters) && array_key_exists('client_secret', $parameters)) {
            $clientCredentials = $parameters['client_secret'];

            return ClientId::create($parameters['client_id']);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkClientConfiguration(DataBag $command_parameters, DataBag $validated_parameters): DataBag
    {
        $validated_parameters = $validated_parameters->with('client_secret', $this->createClientSecret());
        $validated_parameters = $validated_parameters->with('client_secret_expires_at', (0 === $this->secretLifetime ? 0 : time() + $this->secretLifetime));

        return $validated_parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool
    {
        return hash_equals($client->get('client_secret'), $clientCredentials);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedAuthenticationMethods(): array
    {
        return ['client_secret_post'];
    }

    /**
     * @return string
     */
    private function createClientSecret(): string
    {
        return bin2hex(random_bytes(128));
    }
}
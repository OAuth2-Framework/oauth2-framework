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

namespace OAuth2Framework\Component\ClientAuthentication;

use Base64Url\Base64Url;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class ClientSecretPost implements AuthenticationMethod
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
    public function findClientIdAndCredentials(ServerRequestInterface $request, &$clientCredentials = null): ? ClientId
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        if (\array_key_exists('client_id', $parameters) && \array_key_exists('client_secret', $parameters)) {
            $clientCredentials = $parameters['client_secret'];

            return ClientId::create($parameters['client_id']);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkClientConfiguration(DataBag $command_parameters, DataBag $validatedParameters): DataBag
    {
        $validatedParameters->with('client_secret', $this->createClientSecret());
        $validatedParameters->with('client_secret_expires_at', (0 === $this->secretLifetime ? 0 : \time() + $this->secretLifetime));

        return $validatedParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool
    {
        return \hash_equals($client->get('client_secret'), $clientCredentials);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedMethods(): array
    {
        return ['client_secret_post'];
    }

    /**
     * @return string
     */
    private function createClientSecret(): string
    {
        return Base64Url::encode(\random_bytes(32));
    }
}

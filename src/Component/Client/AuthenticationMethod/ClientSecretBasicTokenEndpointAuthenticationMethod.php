<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\AuthenticationMethod;

use Assert\Assertion;
use OAuth2Framework\Component\Client\Client\OAuth2ClientInterface;
use OAuth2Framework\Component\Client\Metadata\ServerMetadata;
use Psr\Http\Message\RequestInterface;

final class ClientSecretBasicTokenEndpointAuthenticationMethod extends AbstractAuthenticationMethod implements TokenEndpointAuthenticationMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'client_secret_basic';
    }

    /**
     * {@inheritdoc}
     */
    public function prepareRequest(ServerMetadata $server_metadata, OAuth2ClientInterface $client, RequestInterface &$request, array &$post_request)
    {
        Assertion::keyExists($client->getConfiguration(), 'client_secret');
        $this->checkClientTokenEndpointAuthenticationMethod($client);

        $encoded = base64_encode(sprintf(
            '%s:%s',
            $client->getConfiguration()['client_id'],
            $client->getConfiguration()['client_secret']
        ));

        $request = $request->withHeader('Authorization', sprintf('Basic %s', $encoded));
    }
}

<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\AuthenticationMethod;

use Assert\Assertion;
use OAuth2Framework\Component\Client\Client\OAuth2ClientInterface;
use OAuth2Framework\Component\Client\Metadata\ServerMetadata;
use Psr\Http\Message\RequestInterface;

final class ClientSecretPostTokenEndpointAuthenticationMethod extends AbstractAuthenticationMethod implements TokenEndpointAuthenticationMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'client_secret_post';
    }

    /**
     * {@inheritdoc}
     */
    public function prepareRequest(ServerMetadata $server_metadata, OAuth2ClientInterface $client, RequestInterface & $request, array &$post_request)
    {
        Assertion::keyExists($client->getConfiguration(), 'client_secret');
        $this->checkClientTokenEndpointAuthenticationMethod($client);

        $post_request['client_id'] = $client->getPublicId();
        $post_request['client_secret'] = $client->getClientSecret();
    }
}

<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Client\Client\OAuth2ClientFactory;
use OAuth2Framework\Component\Client\Grant\ClientCredentialsGrantType;
use OAuth2Framework\Component\Client\Metadata\ServerMetadata;
use OAuth2Framework\Component\Client\Request\OAuth2Request;

/**
 * Class RequestFactoryTest.
 */
class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testRequestCreation()
    {
        $client = OAuth2ClientFactory::createFromValues([
            'public_id'                  => 'foo',
            'public_secret'              => 'secret',
            'token_endpoint_auth_method' => 'client_secret_basic',
        ]);

        $grant_type = new ClientCredentialsGrantType();

        $metadata = ServerMetadata::createFromServerUri('https://accounts.google.com/.well-known/openid-configuration');

        dump($client);
        dump($grant_type);
        dump($metadata);

        $request = new OAuth2Request();
        $access_token = $request->getAccessToken($grant_type, [], [], [], []);

        dump($access_token);
    }
}

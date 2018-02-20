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
use Base64Url\Base64Url;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Component\Client\Client\OAuth2ClientInterface;
use OAuth2Framework\Component\Client\Metadata\ServerMetadata;
use Psr\Http\Message\RequestInterface;

final class ClientSecretJwtTokenEndpointAuthenticationMethod extends AbstractAuthenticationMethod implements TokenEndpointAuthenticationMethodInterface
{
    /**
     * @var JWSBuilder
     */
    private $jwsBuilder;

    /**
     * @var JWK
     */
    private $signatureKey;

    /**
     * @var string
     */
    private $signature_algorithm;

    /**
     * ClientSecretJwtTokenEndpointAuthenticationMethod constructor.
     *
     * @param JWSBuilder $jwsBuilder
     * @param JWK        $signatureKey
     * @param string     $signature_algorithm
     */
    public function __construct(JWSBuilder $jwsBuilder, JWK $signatureKey, $signature_algorithm)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKey = $signatureKey;
        $this->signature_algorithm = $signature_algorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'client_secret_jwt';
    }

    /**
     * {@inheritdoc}
     */
    public function prepareRequest(ServerMetadata $server_metadata, OAuth2ClientInterface $client, RequestInterface &$request, array &$post_request)
    {
        Assertion::keyExists($client->getConfiguration(), 'client_secret');
        $this->checkClientTokenEndpointAuthenticationMethod($client);

        $claims = $this->getClaims($server_metadata, $client);
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($claims)
            ->addSignature($this->signatureKey, ['alg' => $this->signature_algorithm])
            ->build();
        $serializer = new CompactSerializer();
        $assertion = $serializer->serialize($jws, 0);

        $post_request['client_assertion_type'] = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
        $post_request['client_assertion'] = $assertion;
    }

    /**
     * @param \OAuth2Framework\Component\Client\Metadata\ServerMetadata      $server_metadata
     * @param \OAuth2Framework\Component\Client\Client\OAuth2ClientInterface $client
     *
     * @return array
     */
    protected function getClaims(ServerMetadata $server_metadata, OAuth2ClientInterface $client)
    {
        $claims = [
            'jti' => Base64Url::encode(random_bytes(64)),
            'iss' => $client->getPublicId(),
            'exp' => time() + 60,
            'iat' => time(),
            'nbf' => time(),
        ];
        if (true === $server_metadata->has('issuer')) {
            $client['aud'] = $server_metadata->get('issuer');
        }

        return $claims;
    }
}

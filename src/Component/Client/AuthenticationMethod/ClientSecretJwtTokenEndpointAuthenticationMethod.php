<?php

namespace OAuth2Framework\Component\Client\AuthenticationMethod;

use Assert\Assertion;
use Base64Url\Base64Url;
use Jose\JWTCreatorInterface;
use Jose\Object\JWKInterface;
use OAuth2Framework\Component\Client\Client\OAuth2ClientInterface;
use OAuth2Framework\Component\Client\Metadata\ServerMetadata;
use Psr\Http\Message\RequestInterface;

final class ClientSecretJwtTokenEndpointAuthenticationMethod extends AbstractAuthenticationMethod implements TokenEndpointAuthenticationMethodInterface
{
    /**
     * @var \Jose\JWTCreatorInterface
     */
    private $jwt_creator;

    /**
     * @var \Jose\Object\JWKInterface
     */
    private $signature_key;

    /**
     * @var string
     */
    private $signature_algorithm;

    /**
     * ClientSecretJwtTokenEndpointAuthenticationMethod constructor.
     *
     * @param \Jose\JWTCreatorInterface $jwt_creator
     * @param \Jose\Object\JWKInterface $signature_key
     * @param string                    $signature_algorithm
     */
    public function __construct(JWTCreatorInterface $jwt_creator, JWKInterface $signature_key, $signature_algorithm)
    {
        $this->jwt_creator = $jwt_creator;
        $this->signature_key = $signature_key;
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
        $jwt = $this->jwt_creator->sign($claims, ['alg' => $this->signature_algorithm], $this->signature_key);

        $post_request['client_assertion_type'] = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
        $post_request['client_assertion'] = $jwt;
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
        if (true === $server_metadata->has('issuer') ) {
            $client['aud'] = $server_metadata->get('issuer');
        }

        return $claims;
    }
}

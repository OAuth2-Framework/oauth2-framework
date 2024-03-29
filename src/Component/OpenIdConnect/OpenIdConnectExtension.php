<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect;

use function array_key_exists;
use function in_array;
use InvalidArgumentException;
use function is_array;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

class OpenIdConnectExtension implements TokenEndpointExtension
{
    private ?JWEBuilder $jweBuilder = null;

    public function __construct(
        private readonly IdTokenBuilderFactory $idTokenBuilderFactory,
        private readonly string $defaultSignatureAlgorithm,
        private readonly JWSBuilder $jwsBuilder,
        private readonly JWKSet $signatureKeys
    ) {
    }

    public function enableEncryption(JWEBuilder $jweBuilder): static
    {
        $this->jweBuilder = $jweBuilder;

        return $this;
    }

    public function beforeAccessTokenIssuance(
        ServerRequestInterface $request,
        GrantTypeData $grantTypeData,
        GrantType $grantType,
        callable $next
    ): GrantTypeData {
        return $next($request, $grantTypeData, $grantType);
    }

    public function afterAccessTokenIssuance(
        Client $client,
        ResourceOwner $resourceOwner,
        AccessToken $accessToken,
        callable $next
    ): array {
        $data = $next($client, $resourceOwner, $accessToken);
        if ($resourceOwner instanceof UserAccount && $this->hasOpenIdScope(
            $accessToken
        ) && $accessToken->getMetadata()
            ->has('redirect_uri')) {
            $idToken = $this->issueIdToken($client, $resourceOwner, $accessToken);
            $data['id_token'] = $idToken;
        }

        return $data;
    }

    private function issueIdToken(Client $client, UserAccount $userAccount, AccessToken $accessToken): string
    {
        $redirectUri = $accessToken->getMetadata()
            ->get('redirect_uri')
        ;
        $idTokenBuilder = $this->idTokenBuilderFactory->createBuilder($client, $userAccount, $redirectUri);

        $requestedClaims = $this->getIdTokenClaims($accessToken);
        $idTokenBuilder->withRequestedClaims($requestedClaims);
        $idTokenBuilder->withAccessTokenId($accessToken->getId());

        if ($client->has('id_token_signed_response_alg')) {
            $signatureAlgorithm = $client->get('id_token_signed_response_alg');
            $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $signatureAlgorithm);
        } else {
            $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $this->defaultSignatureAlgorithm);
        }
        if ($client->has('userinfo_encrypted_response_alg') && $client->has(
            'userinfo_encrypted_response_enc'
        ) && $this->jweBuilder !== null) {
            $keyEncryptionAlgorithm = $client->get('userinfo_encrypted_response_alg');
            $contentEncryptionAlgorithm = $client->get('userinfo_encrypted_response_enc');
            $idTokenBuilder->withEncryption($this->jweBuilder, $keyEncryptionAlgorithm, $contentEncryptionAlgorithm);
        }
        if ($client->has('require_auth_time')) {
            $idTokenBuilder->withAuthenticationTime();
        }
        $idTokenBuilder->setAccessToken($accessToken);

        return $idTokenBuilder->build();
    }

    private function getIdTokenClaims(AccessToken $accessToken): array
    {
        if (! $accessToken->getMetadata()->has('requested_claims')) {
            return [];
        }

        $requestedClaims = $accessToken->getMetadata()
            ->get('requested_claims')
        ;
        $requestedClaims = json_decode($requestedClaims, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($requestedClaims)) {
            throw new InvalidArgumentException('Invalid claim request');
        }
        if (array_key_exists('id_token', $requestedClaims) === true) {
            return $requestedClaims['id_token'];
        }

        return [];
    }

    private function hasOpenIdScope(AccessToken $accessToken): bool
    {
        return $accessToken->getParameter()
            ->has('scope') && in_array(
                'openid',
                explode(' ', (string) $accessToken->getParameter()->get('scope')),
                true
            );
    }
}

<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\IdTokenGrant;

use function array_key_exists;
use DateTimeImmutable;
use function in_array;
use InvalidArgumentException;
use function is_array;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;

final class IdTokenResponseType implements ResponseType
{
    private string $defaultSignatureAlgorithm;

    public function __construct(
        private IdTokenBuilderFactory $idTokenBuilderFactory,
        string $defaultSignatureAlgorithm,
        private JWSBuilder $jwsBuilder,
        private JWKSet $signatureKeys,
        private ?JWEBuilder $jweBuilder
    ) {
        if ($defaultSignatureAlgorithm === 'none') {
            throw new InvalidArgumentException(
                'The algorithm "none" is not allowed for ID Tokens issued through the authorization endpoint.'
            );
        }
        $this->defaultSignatureAlgorithm = $defaultSignatureAlgorithm;
    }

    public static function create(
        IdTokenBuilderFactory $idTokenBuilderFactory,
        string $defaultSignatureAlgorithm,
        JWSBuilder $jwsBuilder,
        JWKSet $signatureKeys,
        ?JWEBuilder $jweBuilder
    ): self {
        return new self(
            $idTokenBuilderFactory,
            $defaultSignatureAlgorithm,
            $jwsBuilder,
            $signatureKeys,
            $jweBuilder
        );
    }

    public function associatedGrantTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'id_token';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function preProcess(AuthorizationRequest $authorization): void
    {
        // Nothing to do
    }

    public function process(AuthorizationRequest $authorization, TokenType $tokenType): void
    {
        if ($authorization->hasQueryParam('scope') && in_array(
            'openid',
            explode(' ', $authorization->getQueryParam('scope')),
            true
        )) {
            if (! array_key_exists('nonce', $authorization->getQueryParams())) {
                throw OAuth2Error::invalidRequest('The parameter "nonce" is mandatory using "id_token" response type.');
            }

            $this->populateWithIdToken($authorization);
        }
    }

    private function populateWithIdToken(AuthorizationRequest $authorization): AuthorizationRequest
    {
        $params = $authorization->getQueryParams();
        $requestedClaims = $this->getIdTokenClaims($authorization);
        if ($authorization->hasQueryParam('claims')) {
            //$authorization->withMetadata('requested_claims', $authorization->getQueryParam('claims'));
        }

        $idTokenBuilder = $this->idTokenBuilderFactory->createBuilder(
            $authorization->getClient(),
            $authorization->getUserAccount(),
            $authorization->getRedirectUri()
        );
        $idTokenBuilder->withRequestedClaims($requestedClaims);
        $idTokenBuilder->withScope($authorization->getQueryParam('scope'));
        $idTokenBuilder->withNonce($params['nonce']);

        if ($authorization->hasResponseParameter('code')) {
            $idTokenBuilder->withAuthorizationCodeId(
                AuthorizationCodeId::create($authorization->getResponseParameter('code'))
            );
        }

        if ($authorization->hasResponseParameter('access_token')) {
            $idTokenBuilder->withAccessTokenId(
                AccessTokenId::create($authorization->getResponseParameter('access_token'))
            );
        }

        if ($authorization->hasQueryParam('claims_locales')) {
        }

        if ($authorization->hasResponseParameter('expires_in')) {
            $idTokenBuilder->withExpirationAt(
                new DateTimeImmutable(sprintf('now +%s sec', $authorization->getResponseParameter('expires_in')))
            );
        }

        if ($authorization->hasQueryParam('max_age')) {
            $idTokenBuilder->withAuthenticationTime();
        }

        if ($authorization->getClient()->has('id_token_signed_response_alg')) {
            $signatureAlgorithm = $authorization->getClient()
                ->get('id_token_signed_response_alg')
            ;
            if ($signatureAlgorithm === 'none') {
                throw new OAuth2Error(
                    400,
                    OAuth2Error::ERROR_INVALID_CLIENT,
                    'The ID Token signature algorithm set for the client (parameter "id_token_signed_response_alg") is "none" but this algorithm is not allowed for ID Tokens issued through the authorization endpoint.'
                );
            }
            $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $signatureAlgorithm);
        } else {
            $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $this->defaultSignatureAlgorithm);
        }
        if ($authorization->getClient()->has('id_token_encrypted_response_alg') && $authorization->getClient()->has(
            'id_token_encrypted_response_enc'
        ) && $this->jweBuilder !== null) {
            $keyEncryptionAlgorithm = $authorization->getClient()
                ->get('id_token_encrypted_response_alg')
            ;
            $contentEncryptionAlgorithm = $authorization->getClient()
                ->get('id_token_encrypted_response_enc')
            ;
            $idTokenBuilder->withEncryption($this->jweBuilder, $keyEncryptionAlgorithm, $contentEncryptionAlgorithm);
        }

        $idToken = $idTokenBuilder->build();
        $authorization->setResponseParameter('id_token', $idToken);

        return $authorization;
    }

    private function getIdTokenClaims(AuthorizationRequest $authorization): array
    {
        if (! $authorization->hasQueryParam('claims')) {
            return [];
        }

        $requestedClaims = $authorization->getQueryParam('claims');
        $requestedClaims = json_decode($requestedClaims, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($requestedClaims)) {
            throw new InvalidArgumentException('Invalid claim request');
        }
        if (array_key_exists('id_token', $requestedClaims) === true) {
            return $requestedClaims['id_token'];
        }

        return [];
    }
}

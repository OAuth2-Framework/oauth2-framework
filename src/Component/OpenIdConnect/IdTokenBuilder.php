<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect;

use function array_key_exists;
use Base64Url\Base64Url;
use DateTimeImmutable;
use function in_array;
use InvalidArgumentException;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Core\Util\JsonConverter;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use LogicException;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class IdTokenBuilder
{
    private ?JWKSet $signatureKeys = null;

    private ?string $scope = null;

    private array $requestedClaims = [];

    private ?AccessTokenId $accessTokenId = null;

    private ?AuthorizationCodeId $authorizationCodeId = null;

    private ?string $nonce = null;

    private bool $withAuthenticationTime = false;

    private ?JWSBuilder $jwsBuilder = null;

    private ?string $signatureAlgorithm = null;

    private ?JWEBuilder $jweBuilder = null;

    private ?string $keyEncryptionAlgorithm = null;

    private ?string $contentEncryptionAlgorithm = null;

    private ?DateTimeImmutable $expiresAt = null;

    public function __construct(
        private string $issuer,
        private int $lifetime,
        private Client $client,
        private UserAccount $userAccount,
        private ?JKUFactory $jkuFactory,
        private ?AuthorizationCodeRepository $authorizationCodeRepository
    ) {
    }

    public function setAccessToken(AccessToken $accessToken): void
    {
        $this->accessTokenId = $accessToken->getId();
        $this->expiresAt = $accessToken->getExpiresAt();
        $this->scope = $accessToken->getParameter()
            ->has('scope') ? $accessToken->getParameter()
            ->get('scope') : null;

        if ($accessToken->getMetadata()->has('authorization_code_id') && $this->authorizationCodeRepository !== null) {
            $authorizationCodeId = new AuthorizationCodeId($accessToken->getMetadata()->get('authorization_code_id'));
            $authorizationCode = $this->authorizationCodeRepository->find($authorizationCodeId);
            if ($authorizationCode === null) {
                return;
            }
            $this->authorizationCodeId = $authorizationCodeId;
            $queryParams = $authorizationCode->getQueryParameters();
            foreach ([
                'nonce' => 'nonce',
                'claims_locales' => 'claimsLocales',
            ] as $k => $v) {
                if (array_key_exists($k, $queryParams)) {
                    $this->{$v} = $queryParams[$k];
                }
            }
            $this->withAuthenticationTime = array_key_exists('max_age', $authorizationCode->getQueryParameters());
        }
    }

    public function withAccessTokenId(AccessTokenId $accessTokenId): void
    {
        $this->accessTokenId = $accessTokenId;
    }

    public function withAuthorizationCodeId(AuthorizationCodeId $authorizationCodeId): void
    {
        $this->authorizationCodeId = $authorizationCodeId;
    }

    public function withClaimsLocales(string $claimsLocales): void
    {
    }

    public function withAuthenticationTime(): void
    {
        $this->withAuthenticationTime = true;
    }

    public function withScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function withRequestedClaims(array $requestedClaims): void
    {
        $this->requestedClaims = $requestedClaims;
    }

    public function withNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function withExpirationAt(DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function withoutAuthenticationTime(): void
    {
        $this->withAuthenticationTime = false;
    }

    public function withSignature(JWSBuilder $jwsBuilder, JWKSet $signatureKeys, string $signatureAlgorithm): void
    {
        if (! in_array($signatureAlgorithm, $jwsBuilder->getSignatureAlgorithmManager()->list(), true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported signature algorithm "%s". Please use one of the following one: %s',
                $signatureAlgorithm,
                implode(', ', $jwsBuilder->getSignatureAlgorithmManager()->list())
            ));
        }
        if ($signatureKeys->count() === 0) {
            throw new InvalidArgumentException('The signature key set must contain at least one key.');
        }
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKeys = $signatureKeys;
        $this->signatureAlgorithm = $signatureAlgorithm;
    }

    public function withEncryption(
        JWEBuilder $jweBuilder,
        string $keyEncryptionAlgorithm,
        string $contentEncryptionAlgorithm
    ): void {
        if (! in_array($keyEncryptionAlgorithm, $jweBuilder->getKeyEncryptionAlgorithmManager()->list(), true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported key encryption algorithm "%s". Please use one of the following one: %s',
                $keyEncryptionAlgorithm,
                implode(', ', $jweBuilder->getKeyEncryptionAlgorithmManager()->list())
            ));
        }
        if (! in_array(
            $contentEncryptionAlgorithm,
            $jweBuilder->getContentEncryptionAlgorithmManager()
                ->list(),
            true
        )) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported content encryption algorithm "%s". Please use one of the following one: %s',
                $contentEncryptionAlgorithm,
                implode(', ', $jweBuilder->getContentEncryptionAlgorithmManager()->list())
            ));
        }
        $this->jweBuilder = $jweBuilder;
        $this->keyEncryptionAlgorithm = $keyEncryptionAlgorithm;
        $this->contentEncryptionAlgorithm = $contentEncryptionAlgorithm;
    }

    public function build(): string
    {
        if ($this->scope === null) {
            throw new LogicException('It is mandatory to set the scope.');
        }
        //$data = $this->updateClaimsWithAmrAndAcrInfo($data, $this->userAccount);
        $data = $this->updateClaimsWithAuthenticationTime($data, $this->requestedClaims);
        $data = $this->updateClaimsWithNonce($data);
        if ($this->signatureAlgorithm !== null) {
            $data = $this->updateClaimsWithJwtClaims($data);
            $data = $this->updateClaimsWithTokenHash($data);
            $data = $this->updateClaimsAudience($data);
            $result = $this->computeIdToken($data);
        } else {
            $result = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($this->keyEncryptionAlgorithm !== null && $this->contentEncryptionAlgorithm !== null) {
            $result = $this->tryToEncrypt($this->client, $result);
        }

        return $result;
    }

    private function updateClaimsWithJwtClaims(array $claims): array
    {
        if ($this->expiresAt === null) {
            $this->expiresAt = (new DateTimeImmutable())->setTimestamp(time() + $this->lifetime);
        }

        return $claims + [
            'iat' => time(),
            'nbf' => time(),
            'exp' => $this->expiresAt->getTimestamp(),
            'jti' => Base64Url::encode(random_bytes(16)),
            'iss' => $this->issuer,
        ];
    }

    private function updateClaimsWithAuthenticationTime(array $claims, array $requestedClaims): array
    {
        if (($this->withAuthenticationTime === true || array_key_exists(
            'auth_time',
            $requestedClaims
        )) && $this->userAccount->getLastLoginAt() !== null) {
            $claims['auth_time'] = $this->userAccount->getLastLoginAt();
        }

        return $claims;
    }

    private function updateClaimsWithNonce(array $claims): array
    {
        if ($this->nonce !== null) {
            $claims['nonce'] = $this->nonce;
        }

        return $claims;
    }

    private function updateClaimsAudience(array $claims): array
    {
        $claims['aud'] = [$this->client->getPublicId()->getValue(), $this->issuer];
        $claims['azp'] = $this->client->getPublicId()->getValue();

        return $claims;
    }

    private function computeIdToken(array $claims): string
    {
        $signatureKey = $this->getSignatureKey($this->signatureAlgorithm);
        $header = $this->getHeaders($signatureKey, $this->signatureAlgorithm);
        $claimsAsArray = JsonConverter::encode($claims);
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($claimsAsArray)
            ->addSignature($signatureKey, $header)
            ->build()
        ;
        $serializer = new JwsCompactSerializer();

        return $serializer->serialize($jws, 0);
    }

    private function tryToEncrypt(Client $client, string $jwt): string
    {
        $clientKeySet = $this->getClientKeySet($client);
        $keyEncryptionAlgorithm = $this->jweBuilder->getKeyEncryptionAlgorithmManager()
            ->get($this->keyEncryptionAlgorithm)
        ;
        $encryptionKey = $clientKeySet->selectKey('enc', $keyEncryptionAlgorithm);
        if ($encryptionKey === null) {
            throw new InvalidArgumentException('No encryption key available for the client.');
        }
        $header = [
            'typ' => 'JWT',
            'jti' => Base64Url::encode(random_bytes(16)),
            'alg' => $this->keyEncryptionAlgorithm,
            'enc' => $this->contentEncryptionAlgorithm,
        ];
        $jwe = $this->jweBuilder
            ->create()
            ->withPayload($jwt)
            ->withSharedProtectedHeader($header)
            ->addRecipient($encryptionKey)
            ->build()
        ;
        $serializer = new JweCompactSerializer();

        return $serializer->serialize($jwe, 0);
    }

    private function getSignatureKey(string $signatureAlgorithm): JWK
    {
        $keys = $this->signatureKeys;
        if ($this->client->has('client_secret')) {
            $jwk = new JWK([
                'kty' => 'oct',
                'use' => 'sig',
                'k' => Base64Url::encode($this->client->get('client_secret')),
            ]);
            $keys = $keys->with($jwk);
        }
        $algorithm = $this->jwsBuilder->getSignatureAlgorithmManager()
            ->get($signatureAlgorithm)
        ;
        if ($algorithm->name() === 'none') {
            return new JWK([
                'kty' => 'none',
                'alg' => 'none',
                'use' => 'sig',
            ]);
        }
        $signatureKey = $keys->selectKey('sig', $algorithm);
        if ($signatureKey === null) {
            throw new InvalidArgumentException(
                'Unable to find a key to sign the ID Token. Please verify the selected key set contains suitable keys.'
            );
        }

        return $signatureKey;
    }

    private function getHeaders(JWK $signatureKey, string $signatureAlgorithm): array
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $signatureAlgorithm,
        ];
        if ($signatureKey->has('kid')) {
            $header['kid'] = $signatureKey->get('kid');
        }

        return $header;
    }

    private function updateClaimsWithTokenHash(array $claims): array
    {
        if ($this->signatureAlgorithm === 'none') {
            return $claims;
        }
        if ($this->accessTokenId !== null) {
            $claims['at_hash'] = $this->getHash($this->accessTokenId->getValue());
        }
        if ($this->authorizationCodeId !== null) {
            $claims['c_hash'] = $this->getHash($this->authorizationCodeId->getValue());
        }

        return $claims;
    }

    private function getHash(string $tokenId): string
    {
        return Base64Url::encode(
            mb_substr(hash($this->getHashMethod(), $tokenId, true), 0, $this->getHashSize(), '8bit')
        );
    }

    private function getHashMethod(): string
    {
        $map = [
            'HS256' => 'sha256',
            'ES256' => 'sha256',
            'RS256' => 'sha256',
            'PS256' => 'sha256',
            'HS384' => 'sha384',
            'ES384' => 'sha384',
            'RS384' => 'sha384',
            'PS384' => 'sha384',
            'HS512' => 'sha512',
            'ES512' => 'sha512',
            'RS512' => 'sha512',
            'PS512' => 'sha512',
        ];

        if (! array_key_exists($this->signatureAlgorithm, $map)) {
            throw new InvalidArgumentException(sprintf('Algorithm "%s" is not supported', $this->signatureAlgorithm));
        }

        return $map[$this->signatureAlgorithm];
    }

    private function getHashSize(): int
    {
        $map = [
            'HS256' => 16,
            'ES256' => 16,
            'RS256' => 16,
            'PS256' => 16,
            'HS384' => 24,
            'ES384' => 24,
            'RS384' => 24,
            'PS384' => 24,
            'HS512' => 32,
            'ES512' => 32,
            'RS512' => 32,
            'PS512' => 32,
        ];

        if (! array_key_exists($this->signatureAlgorithm, $map)) {
            throw new InvalidArgumentException(sprintf('Algorithm "%s" is not supported', $this->signatureAlgorithm));
        }

        return $map[$this->signatureAlgorithm];
    }

    private function getClientKeySet(Client $client): JWKSet
    {
        $keyset = new JWKSet([]);
        if ($client->has('jwks')) {
            $jwks = JWKSet::createFromJson($client->get('jwks'));
            foreach ($jwks as $jwk) {
                $keyset = $keyset->with($jwk);
            }
        }
        if ($client->has('client_secret')) {
            $jwk = new JWK([
                'kty' => 'oct',
                'use' => 'enc',
                'k' => Base64Url::encode($client->get('client_secret')),
            ]);
            $keyset = $keyset->with($jwk);
        }
        if ($client->has('jwks_uri') && $this->jkuFactory !== null) {
            $jwksUri = $this->jkuFactory->loadFromUrl($client->get('jwks_uri'));
            foreach ($jwksUri as $jwk) {
                $keyset = $keyset->with($jwk);
            }
        }

        if ($keyset->count() === 0) {
            throw new InvalidArgumentException('The client has no key or key set.');
        }

        return $keyset;
    }
}

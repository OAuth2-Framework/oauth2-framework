<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect;

use Assert\Assertion;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSLoader;
use const JSON_THROW_ON_ERROR;
use Throwable;

class IdTokenLoader
{
    public function __construct(
        private JWSLoader $jwsLoader,
        private JWKSet $signatureKeySet
    ) {
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return $this->jwsLoader->getJwsVerifier()
            ->getSignatureAlgorithmManager()
            ->list()
        ;
    }

    public function load(IdTokenId $idTokenId): ?IdToken
    {
        $value = $idTokenId->getValue();

        try {
            $jwt = $this->jwsLoader->loadAndVerifyWithKeySet($value, $this->signatureKeySet, $signature);
            Assertion::eq(0, $signature, 'Invalid ID Token.');
            $payload = $jwt->getPayload();
            Assertion::string($payload, 'Invalid ID Token.');
            $claims = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            Assertion::isArray($claims, 'Invalid ID Token.');

            return new IdToken($idTokenId, $claims);
        } catch (Throwable) {
            return null;
        }
    }
}

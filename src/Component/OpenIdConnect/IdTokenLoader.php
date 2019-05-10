<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\OpenIdConnect;

use Assert\Assertion;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSLoader;

class IdTokenLoader
{
    /**
     * @var JWKSet
     */
    private $signatureKeySet;

    /**
     * @var JWSLoader
     */
    private $jwsLoader;

    /**
     * @var string[]
     */
    private $signatureAlgorithms;

    /**
     * IdTokenLoader constructor.
     */
    public function __construct(JWSLoader $jwsLoader, JWKSet $signatureKeySet, array $signatureAlgorithms)
    {
        $this->signatureAlgorithms = $signatureAlgorithms;
        $this->signatureKeySet = $signatureKeySet;
        $this->jwsLoader = $jwsLoader;
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return $this->jwsLoader->getJwsVerifier()->getSignatureAlgorithmManager()->list();
    }

    public function load(IdTokenId $idTokenId): ?IdToken
    {
        $value = $idTokenId->getValue();

        try {
            $jwt = $this->jwsLoader->loadAndVerifyWithKeySet($value, $this->signatureKeySet, $signature);
            Assertion::eq(0, $signature, 'Invalid ID Token.');
            $payload = $jwt->getPayload();
            Assertion::string($payload, 'Invalid ID Token.');
            $claims = \Safe\json_decode($payload, true);
            Assertion::isArray($claims, 'Invalid ID Token.');

            return new IdToken($idTokenId, $claims);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\OpenIdConnect;

use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSVerifier;

final class IdTokenLoader
{
    /**
     * @var JWKSet
     */
    private $signatureKeySet;

    /**
     * @var JWSVerifier
     */
    private $jwsVerifier;

    /**
     * @var string[]
     */
    private $signatureAlgorithms;

    /**
     * IdTokenLoader constructor.
     *
     * @param JWSVerifier $jwsVerifier
     * @param JWKSet      $signatureKeySet
     * @param array       $signatureAlgorithms
     */
    public function __construct(JWSVerifier $jwsVerifier, JWKSet $signatureKeySet, array $signatureAlgorithms)
    {
        $this->signatureAlgorithms = $signatureAlgorithms;
        $this->signatureKeySet = $signatureKeySet;
        $this->jwsVerifier = $jwsVerifier;
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return $this->jwsVerifier->getSignatureAlgorithmManager()->list();
    }

    /**
     * @param IdTokenId $idTokenId
     *
     * @return IdToken|null
     */
    public function load(IdTokenId $idTokenId): ? IdToken
    {
        $value = $idTokenId->getValue();

        try {
            $jwt = $this->jwsVerifier->load($value);
            $claims = json_decode($jwt->getPayload(), true);
            Assertion::isArray($claims, 'Invalid ID Token');
            $validSignature = $this->jwsVerifier->verifyWithKeySet($jwt, $this->signatureKeySet, 0);
            Assertion::inArray($jwt->getSignature($validSignature)->getProtectedHeader('alg'), $this->signatureAlgorithms);
            $idToken = IdToken::create($idTokenId, $claims);

            return $idToken;
        } catch (\Exception $e) {
            return null;
        }
    }
}

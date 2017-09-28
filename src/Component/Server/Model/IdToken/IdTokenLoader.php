<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\IdToken;

use Assert\Assertion;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSLoader;

final class IdTokenLoader
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
     *
     * @param JWSLoader $jwsLoader
     * @param JWKSet    $signatureKeySet
     * @param array     $signatureAlgorithms
     */
    public function __construct(JWSLoader $jwsLoader, JWKSet $signatureKeySet, array $signatureAlgorithms)
    {
        $this->signatureKeySet = $signatureKeySet;
        $this->signatureAlgorithms = $signatureAlgorithms;
        $this->jwsLoader = $jwsLoader;
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return $this->jwsLoader->getSupportedSignatureAlgorithms();
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
            $jwt = $this->jwsLoader->load($value);
            $claims = json_decode($jwt->getPayload(), true);
            Assertion::isArray($claims, 'Invalid ID Token');
            $validSignature = $this->jwsLoader->verifyWithKeySet($jwt, $this->signatureKeySet);
            Assertion::inArray($jwt->getSignature($validSignature)->getProtectedHeader('alg'), $this->signatureAlgorithms);
            $idToken = IdToken::create($idTokenId, $claims);

            return $idToken;
        } catch (\Exception $e) {
            return null;
        }
    }
}

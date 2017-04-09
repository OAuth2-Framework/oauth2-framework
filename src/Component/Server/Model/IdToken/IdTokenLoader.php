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
use Jose\JWTLoaderInterface;
use Jose\Object\JWKSetInterface;

final class IdTokenLoader
{
    /**
     * @var JWKSetInterface
     */
    private $signatureKeySet;

    /**
     * @var JWTLoaderInterface
     */
    private $jwtLoader;

    /**
     * @var string[]
     */
    private $signatureAlgorithms;

    /**
     * IdTokenLoader constructor.
     *
     * @param JWTLoaderInterface $jwtLoader
     * @param JWKSetInterface    $signatureKeySet
     * @param array              $signatureAlgorithms
     */
    public function __construct(JWTLoaderInterface $jwtLoader, JWKSetInterface $signatureKeySet, array $signatureAlgorithms)
    {
        $this->signatureKeySet = $signatureKeySet;
        $this->signatureAlgorithms = $signatureAlgorithms;
        $this->jwtLoader = $jwtLoader;
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return $this->jwtLoader->getSupportedSignatureAlgorithms();
    }

    /**
     * @return string[]
     */
    public function getSupportedKeyEncryptionAlgorithms(): array
    {
        return $this->jwtLoader->getSupportedKeyEncryptionAlgorithms();
    }

    /**
     * @return string[]
     */
    public function getSupportedContentEncryptionAlgorithms(): array
    {
        return $this->jwtLoader->getSupportedContentEncryptionAlgorithms();
    }

    /**
     * @param IdTokenId $idTokenId
     *
     * @return IdToken|null
     */
    public function load(IdTokenId $idTokenId): ? IdToken
    {
        try {
            $jwt = $this->jwtLoader->load($idTokenId->getValue());
            Assertion::true($jwt->hasClaims(), 'Invalid ID Token');
            $validSignature = $this->jwtLoader->verify($jwt, $this->signatureKeySet);
            Assertion::inArray($jwt->getSignature($validSignature)->getProtectedHeader('alg'), $this->signatureAlgorithms);
            $idToken = IdToken::create($idTokenId, $jwt->getClaims());

            return $idToken;
        } catch (\Exception $e) {
            return null;
        }
    }
}

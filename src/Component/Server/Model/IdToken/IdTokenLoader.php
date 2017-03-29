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
     * @var string
     */
    private $signatureAlgorithm;

    /**
     * IdTokenLoader constructor.
     *
     * @param JWTLoaderInterface $jwtLoader
     * @param JWKSetInterface    $signatureKeySet
     * @param string             $signatureAlgorithm
     */
    public function __construct(JWTLoaderInterface $jwtLoader, JWKSetInterface $signatureKeySet, string $signatureAlgorithm)
    {
        $this->signatureKeySet = $signatureKeySet;
        $this->signatureAlgorithm = $signatureAlgorithm;
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
    public function load(IdTokenId $idTokenId): ?IdToken
    {
        try {
            $jwt = $this->jwtLoader->load($idTokenId->getValue());
            Assertion::true($jwt->hasClaims(), 'Invalid ID Token');
            $validSignature = $this->jwtLoader->verify($jwt, $this->signatureKeySet);
            Assertion::eq($this->signatureAlgorithm, $jwt->getSignature($validSignature)->getProtectedHeader('alg'));
            $idToken = IdToken::create($idTokenId, $jwt->getClaims());

            return $idToken;
        } catch (\Exception $e) {
            return null;
        }
    }
}

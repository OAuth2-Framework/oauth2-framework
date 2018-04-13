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

namespace OAuth2Framework\Component\OpenIdConnect;

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
     *
     * @param JWSLoader $jwsLoader
     * @param JWKSet    $signatureKeySet
     * @param array     $signatureAlgorithms
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
        return $this->jwsLoader->getSignatureAlgorithmManager()->list();
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
            $jwt = $this->jwsLoader->loadAndVerifyWithKeySet($value, $this->signatureKeySet, $signature);
            if (0 !== $signature) {
                throw new \InvalidArgumentException('Invalid ID Token.');
            }
            $claims = json_decode($jwt->getPayload(), true);
            if (!is_array($claims)) {
                throw new \InvalidArgumentException('Invalid ID Token.');
            }
            $idToken = IdToken::create($idTokenId, $claims);

            return $idToken;
        } catch (\Exception $e) {
            return null;
        }
    }
}

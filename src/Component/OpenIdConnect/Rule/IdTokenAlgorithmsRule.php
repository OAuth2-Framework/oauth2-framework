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

namespace OAuth2Framework\Component\OpenIdConnect\Rule;

use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class IdTokenAlgorithmsRule implements Rule
{
    /**
     * @var JWSBuilder
     */
    private $jwsBuilder;

    /**
     * @var JWEBuilder|null
     */
    private $jweBuilder;

    /**
     * IdTokenAlgorithmsRule constructor.
     *
     * @param JWSBuilder      $jwsBuilder
     * @param JWEBuilder|null $jweBuilder
     */
    public function __construct(JWSBuilder $jwsBuilder, ?JWEBuilder $jweBuilder)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->jweBuilder = $jweBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('id_token_signed_response_alg')) {
            $this->checkAlgorithms('id_token_signed_response_alg', $commandParameters, $this->jwsBuilder->getSignatureAlgorithmManager()->list());
            $validatedParameters = $validatedParameters->with('id_token_signed_response_alg', $commandParameters->get('id_token_signed_response_alg'));
        }

        if ($commandParameters->has('id_token_encrypted_response_alg') && $commandParameters->has('id_token_encrypted_response_enc') && null !== $this->jweBuilder) {
            $this->checkAlgorithms('id_token_encrypted_response_alg', $commandParameters, $this->jweBuilder->getKeyEncryptionAlgorithmManager()->list());
            $this->checkAlgorithms('id_token_encrypted_response_enc', $commandParameters, $this->jweBuilder->getContentEncryptionAlgorithmManager()->list());
            $validatedParameters = $validatedParameters->with('id_token_encrypted_response_alg', $commandParameters->get('id_token_encrypted_response_alg'));
            $validatedParameters = $validatedParameters->with('id_token_encrypted_response_enc', $commandParameters->get('id_token_encrypted_response_enc'));
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }

    /**
     * @param string  $parameter
     * @param DataBag $commandParameters
     * @param array   $allowedAlgorithms
     */
    private function checkAlgorithms(string $parameter, DataBag $commandParameters, array $allowedAlgorithms)
    {
        $algorithm = $commandParameters->get($parameter);
        if (!is_string($algorithm) || !in_array($algorithm, $allowedAlgorithms)) {
            throw new \InvalidArgumentException(sprintf('The parameter "%s" must be an algorithm supported by this server. Please choose one of the following value(s): %s', $parameter, implode(', ', $allowedAlgorithms)));
        }
    }
}

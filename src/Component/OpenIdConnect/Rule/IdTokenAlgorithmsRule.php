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

namespace OAuth2Framework\Component\OpenIdConnect\Rule;

use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class IdTokenAlgorithmsRule implements Rule
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
     */
    public function __construct(JWSBuilder $jwsBuilder, ?JWEBuilder $jweBuilder)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->jweBuilder = $jweBuilder;
    }

    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        if ($commandParameters->has('id_token_signed_response_alg')) {
            $this->checkAlgorithms('id_token_signed_response_alg', $commandParameters, $this->jwsBuilder->getSignatureAlgorithmManager()->list());
            $validatedParameters->set('id_token_signed_response_alg', $commandParameters->get('id_token_signed_response_alg'));
        }

        if ($commandParameters->has('id_token_encrypted_response_alg') && $commandParameters->has('id_token_encrypted_response_enc') && null !== $this->jweBuilder) {
            $this->checkAlgorithms('id_token_encrypted_response_alg', $commandParameters, $this->jweBuilder->getKeyEncryptionAlgorithmManager()->list());
            $this->checkAlgorithms('id_token_encrypted_response_enc', $commandParameters, $this->jweBuilder->getContentEncryptionAlgorithmManager()->list());
            $validatedParameters->set('id_token_encrypted_response_alg', $commandParameters->get('id_token_encrypted_response_alg'));
            $validatedParameters->set('id_token_encrypted_response_enc', $commandParameters->get('id_token_encrypted_response_enc'));
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }

    private function checkAlgorithms(string $parameter, DataBag $commandParameters, array $allowedAlgorithms): void
    {
        $algorithm = $commandParameters->get($parameter);
        if (!\is_string($algorithm) || !\in_array($algorithm, $allowedAlgorithms, true)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The parameter "%s" must be an algorithm supported by this server. Please choose one of the following value(s): %s', $parameter, \implode(', ', $allowedAlgorithms)));
        }
    }
}

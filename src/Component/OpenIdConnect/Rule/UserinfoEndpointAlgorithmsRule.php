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
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class UserinfoEndpointAlgorithmsRule implements Rule
{
    /**
     * @var JWSBuilder|null
     */
    private $jwsBuilder;

    /**
     * @var JWEBuilder|null
     */
    private $jweBuilder;

    /**
     * UserinfoEndpointAlgorithmsRule constructor.
     */
    public function __construct(?JWSBuilder $jwsBuilder, ?JWEBuilder $jweBuilder)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->jweBuilder = $jweBuilder;
    }

    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        if ($commandParameters->has('userinfo_signed_response_alg') && null !== $this->jwsBuilder) {
            $this->checkAlgorithms('userinfo_signed_response_alg', $commandParameters, $this->jwsBuilder->getSignatureAlgorithmManager()->list());
            $validatedParameters->set('userinfo_signed_response_alg', $commandParameters->get('userinfo_signed_response_alg'));
        }

        if ($commandParameters->has('userinfo_encrypted_response_alg') && $commandParameters->has('userinfo_encrypted_response_enc') && null !== $this->jweBuilder) {
            $this->checkAlgorithms('userinfo_encrypted_response_alg', $commandParameters, $this->jweBuilder->getKeyEncryptionAlgorithmManager()->list());
            $validatedParameters->set('userinfo_encrypted_response_alg', $commandParameters->get('userinfo_encrypted_response_alg'));
            $this->checkAlgorithms('userinfo_encrypted_response_enc', $commandParameters, $this->jweBuilder->getContentEncryptionAlgorithmManager()->list());
            $validatedParameters->set('userinfo_encrypted_response_enc', $commandParameters->get('userinfo_encrypted_response_enc'));
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }

    private function checkAlgorithms(string $parameter, DataBag $commandParameters, array $allowedAlgorithms)
    {
        $algorithm = $commandParameters->get($parameter);
        if (!\is_string($algorithm) || !\in_array($algorithm, $allowedAlgorithms, true)) {
            throw new \InvalidArgumentException(\sprintf('The parameter "%s" must be an algorithm supported by this server. Please choose one of the following value(s): %s', $parameter, \implode(', ', $allowedAlgorithms)));
        }
    }
}

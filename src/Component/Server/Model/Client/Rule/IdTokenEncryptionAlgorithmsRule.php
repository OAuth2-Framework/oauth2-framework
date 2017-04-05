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

namespace OAuth2Framework\Component\Server\Model\Client\Rule;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\IdToken\idTokenCreatorInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class IdTokenEncryptionAlgorithmsRule implements RuleInterface
{
    /**
     * @var idTokenCreatorInterface
     */
    private $idTokenCreator;

    /**
     * IdTokenAlgorithmsRule constructor.
     *
     * @param idTokenCreatorInterface $idTokenCreator
     */
    public function __construct(idTokenCreatorInterface $idTokenCreator)
    {
        $this->idTokenCreator = $idTokenCreator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('id_token_encrypted_response_alg') && $commandParameters->has('id_token_encrypted_response_enc')) {
            Assertion::string($commandParameters['id_token_encrypted_response_alg'], 'Invalid parameter \'id_token_encrypted_response_alg\'. The value must be a string.');
            Assertion::string($commandParameters['id_token_encrypted_response_enc'], 'Invalid parameter \'id_token_encrypted_response_enc\'. The value must be a string.');
            Assertion::inArray($commandParameters['id_token_encrypted_response_alg'], $this->idTokenCreator->getSupportedKeyEncryptionAlgorithms(), sprintf('The ID Token content encryption algorithm \'%s\' is not supported. Please choose one of the following algorithm: %s', $commandParameters->get('id_token_encrypted_response_alg'), implode(', ', $this->idTokenCreator->getSupportedContentEncryptionAlgorithms())));
            Assertion::inArray($commandParameters['id_token_encrypted_response_enc'], $this->idTokenCreator->getSupportedContentEncryptionAlgorithms(), sprintf('The ID Token key encryption algorithm \'%s\' is not supported. Please choose one of the following algorithm: %s', $commandParameters->get('id_token_encrypted_response_enc'), implode(', ', $this->idTokenCreator->getSupportedKeyEncryptionAlgorithms())));
            $validatedParameters = $validatedParameters->with('id_token_encrypted_response_alg', $commandParameters['id_token_encrypted_response_alg']);
            $validatedParameters = $validatedParameters->with('id_token_encrypted_response_enc', $commandParameters['id_token_encrypted_response_enc']);
        } elseif ($commandParameters->has('id_token_encrypted_response_alg') || $commandParameters->has('id_token_encrypted_response_enc')) {
            throw new \InvalidArgumentException('The parameters \'id_token_encrypted_response_alg\' and \'id_token_encrypted_response_enc\' must be set together');
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}

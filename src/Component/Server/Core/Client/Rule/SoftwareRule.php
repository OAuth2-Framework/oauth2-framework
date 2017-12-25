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

namespace OAuth2Framework\Component\Server\Core\Client\Rule;

use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSLoader;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;

final class SoftwareRule implements Rule
{
    /**
     * @var JWSLoader
     */
    private $jwsLoader;

    /**
     * @var bool
     */
    private $isSoftwareStatementRequired;

    /**
     * @var JWKSet
     */
    private $softwareStatementSignatureKeySet;

    /**
     * @var string[]
     */
    private $allowedSignatureAlgorithms;

    /**
     * @param JWSLoader $jwsLoader
     * @param JWKSet    $signatureKeySet
     * @param bool      $isSoftwareStatementRequired
     * @param array     $allowedSignatureAlgorithms
     */
    public function __construct(JWSLoader $jwsLoader, JWKSet $signatureKeySet, bool $isSoftwareStatementRequired, array $allowedSignatureAlgorithms)
    {
        $this->jwsLoader = $jwsLoader;
        $this->softwareStatementSignatureKeySet = $signatureKeySet;
        $this->isSoftwareStatementRequired = $isSoftwareStatementRequired;
        $this->allowedSignatureAlgorithms = $allowedSignatureAlgorithms;
    }

    /**
     * @return bool
     */
    public function isSoftwareStatementRequired(): bool
    {
        return $this->isSoftwareStatementRequired;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        Assertion::false($this->isSoftwareStatementRequired() && !$commandParameters->has('software_statement'), 'The parameter "software_statement" is mandatory.');
        if ($commandParameters->has('software_statement')) {
            $statement = $commandParameters->get('software_statement');
            Assertion::string($statement, 'The software statement should be a string.');
            $software_statement = $this->loadSoftwareStatement($statement);
            $validatedParameters = $validatedParameters->with('software_statement', $commandParameters->get('software_statement'));
        } else {
            $software_statement = [];
        }

        foreach (['software_id', 'software_version'] as $key) {
            if ($commandParameters->has($key)) {
                $validatedParameters = $validatedParameters->with($key, $commandParameters->get($key));
            }
        }

        $validatedParameters = $next($clientId, $commandParameters, $validatedParameters);
        $validatedParameters = $validatedParameters->withParameters($software_statement);

        return $validatedParameters;
    }

    /**
     * @param string $software_statement
     *
     * @return array
     */
    private function loadSoftwareStatement(string $software_statement): array
    {
        try {
            $jws = $this->jwsLoader->load($software_statement);
            $signatureVerified = $this->jwsLoader->verifyWithKeySet($jws, $this->softwareStatementSignatureKeySet);
            Assertion::inArray($jws->getSignature($signatureVerified)->getProtectedHeader('alg'), $this->allowedSignatureAlgorithms);
            $claims = json_decode($jws->getPayload(), true);
            Assertion::isArray($claims, 'Invalid Software Statement.');

            return $claims;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid Software Statement.', $e->getCode(), $e);
        }
    }
}

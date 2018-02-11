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

namespace OAuth2Framework\Component\ClientRule;

use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSLoader;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class SoftwareRule implements Rule
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
        if ($this->isSoftwareStatementRequired() && !$commandParameters->has('software_statement')) {
            throw new \InvalidArgumentException('The parameter "software_statement" is mandatory.');
        }
        if ($commandParameters->has('software_statement')) {
            $statement = $commandParameters->get('software_statement');
            if (!is_string($statement)) {
                throw new \InvalidArgumentException('The software statement must be a string.');
            }
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
            $jws = $this->jwsLoader->loadAndVerifyWithKeySet($software_statement, $this->softwareStatementSignatureKeySet, $signatureVerified);
            if (!in_array($jws->getSignature($signatureVerified)->getProtectedHeaderParameter('alg'), $this->allowedSignatureAlgorithms)) {
                throw new \InvalidArgumentException('Invalid Software Statement.');
            }
            $claims = json_decode($jws->getPayload(), true);
            if (!is_array($claims)) {
                throw new \InvalidArgumentException('Invalid Software Statement.');
            }

            return $claims;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid Software Statement.', $e->getCode(), $e);
        }
    }
}

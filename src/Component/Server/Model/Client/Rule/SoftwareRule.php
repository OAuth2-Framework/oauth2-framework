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
use Jose\JWTLoaderInterface;
use Jose\Object\JWKSetInterface;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class SoftwareRule implements RuleInterface
{
    /**
     * @var JWTLoaderInterface
     */
    private $jwtLoader;

    /**
     * @var bool
     */
    private $isSoftwareStatementRequired;

    /**
     * @var JWKSetInterface
     */
    private $softwareStatementSignatureKeySet;

    /**
     * @var string[]
     */
    private $allowedSignatureAlgorithms;

    /**
     * @return bool
     */
    public function isSoftwareStatementRequired(): bool
    {
        return $this->isSoftwareStatementRequired;
    }

    /**
     * @param JWTLoaderInterface $jwtLoader
     * @param JWKSetInterface    $signatureKeySet
     * @param bool               $isSoftwareStatementRequired
     * @param array              $allowedSignatureAlgorithms
     */
    public function __construct(JWTLoaderInterface $jwtLoader, JWKSetInterface $signatureKeySet, bool $isSoftwareStatementRequired, array $allowedSignatureAlgorithms)
    {
        $this->jwtLoader = $jwtLoader;
        $this->softwareStatementSignatureKeySet = $signatureKeySet;
        $this->isSoftwareStatementRequired = $isSoftwareStatementRequired;
        $this->allowedSignatureAlgorithms = $allowedSignatureAlgorithms;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ?UserAccountId $userAccountId, callable $next): DataBag
    {
        Assertion::false($this->isSoftwareStatementRequired() && !$commandParameters->has('software_statement'), 'The parameter \'software_statement\' is mandatory.');
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
                $validatedParameters = $validatedParameters->with($key, $commandParameters[$key]);
            }
        }

        $validatedParameters = $next($commandParameters, $validatedParameters, $userAccountId);
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
            $jws = $this->jwtLoader->load($software_statement);
            $signatureVerified = $this->jwtLoader->verify($jws, $this->softwareStatementSignatureKeySet);
            Assertion::inArray($jws->getSignature($signatureVerified)->getProtectedHeader('alg'), $this->allowedSignatureAlgorithms);

            return $jws->getClaims();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid Software Statement.', $e->getCode(), $e);
        }
    }
}

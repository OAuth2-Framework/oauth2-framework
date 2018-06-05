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
use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class JwksRule implements Rule
{
    /**
     * @var null|JKUFactory
     */
    private $jkuFactory;

    public function __construct(?JKUFactory $jkuFactory)
    {
        $this->jkuFactory = $jkuFactory;
    }

    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('jwks') && $commandParameters->has('jwks_uri')) {
            throw new \InvalidArgumentException('The parameters "jwks" and "jwks_uri" cannot be set together.');
        }

        if ($commandParameters->has('jwks')) {
            try {
                $keyset = JWKSet::createFromKeyData($commandParameters->get('jwks'));
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException('The parameter "jwks" must be a valid JWKSet object.', 0, $e);
            }
            if (0 === count($keyset)) {
                throw new \InvalidArgumentException('The parameter "jwks" must not be empty.');
            }
            $validatedParameters = $validatedParameters->with('jwks', $commandParameters->get('jwks'));
        }
        if ($commandParameters->has('jwks_uri')) {
            if (null === $this->jkuFactory) {
                throw new \InvalidArgumentException('Distant key sets cannot be used. Please use "jwks" instead of "jwks_uri".');
            }

            try {
                $jwks = $this->jkuFactory->loadFromUrl($commandParameters->get('jwks_uri'));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('The parameter "jwks_uri" must be a valid uri to a JWKSet.', 0, $e);
            }
            if (0 === $jwks->count()) {
                throw new \InvalidArgumentException('The distant key set is empty.');
            }
            $validatedParameters = $validatedParameters->with('jwks_uri', $commandParameters->get('jwks_uri'));
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}

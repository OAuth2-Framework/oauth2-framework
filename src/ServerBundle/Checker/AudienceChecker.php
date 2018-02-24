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

namespace OAuth2Framework\ServerBundle\Checker;

use Jose\Component\Checker\ClaimChecker;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AudienceChecker implements ClaimChecker
{
    private const CLAIM_NAME = 'aud';

    /**
     * @var bool
     */
    private $protectedHeader = false;

    /**
     * @var string
     */
    private $audience;

    /**
     * AudienceChecker constructor.
     *
     * @param RouterInterface $router
     * @param string          $routeName
     * @param array           $routeParameters
     */
    public function __construct(RouterInterface $router, string $routeName, array $routeParameters)
    {
        $this->audience = $router->generate($routeName, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function checkClaim($value)
    {
        return $this->checkValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function checkHeader($value)
    {
        return $this->checkValue($value);
    }

    /**
     * @param $value
     *
     * @throws \InvalidArgumentException
     */
    private function checkValue($value)
    {
        if (is_string($value) && $value !== $this->audience) {
            throw new \InvalidArgumentException('Bad audience.');
        } elseif (!is_array($value) || !in_array($this->audience, $value)) {
            throw new \InvalidArgumentException('Bad audience.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportedClaim(): string
    {
        return self::CLAIM_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function supportedHeader(): string
    {
        return self::CLAIM_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function protectedHeaderOnly(): bool
    {
        return $this->protectedHeader;
    }
}

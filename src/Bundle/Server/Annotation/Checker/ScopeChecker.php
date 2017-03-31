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

namespace OAuth2Framework\Bundle\Server\Annotation\Checker;

use OAuth2Framework\Bundle\Server\Annotation\OAuth2;
use OAuth2Framework\Bundle\Server\Security\Authentication\Token\OAuth2Token;
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ScopeChecker implements CheckerInterface
{
    /**
     * @var ScopeRepositoryInterface
     */
    private $scopeRepository;

    /**
     * ScopeChecker constructor.
     *
     * @param ScopeRepositoryInterface $scopeRepository
     */
    public function __construct(ScopeRepositoryInterface $scopeRepository)
    {
        $this->scopeRepository = $scopeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function check(OAuth2Token $token, OAuth2 $configuration)
    {
        if (null === $configuration->getScope()) {
            return;
        }

        $language = $this->getExpressionLanguage();
        $result = $language->evaluate(
            $configuration->getScope(),
            [
                'scope' => $token->getAccessToken()->getScope(),
            ]
        );

        // If the scope of the access token does not fulfill the scope rule, then returns an authentication error
        if (false === $result) {
            return sprintf('Insufficient scope. The scope rule is: %s', $configuration->getScope());
        }
    }

    /**
     * @return \Symfony\Component\ExpressionLanguage\ExpressionLanguage
     */
    private function getExpressionLanguage()
    {
        $language = new ExpressionLanguage();
        $language->register('has', function ($str) {
            return sprintf('(in_array(%1$s, scope))', $str);
        }, function ($arguments, $str) {
            return in_array($str, $arguments['scope']);
        });

        return $language;
    }
}

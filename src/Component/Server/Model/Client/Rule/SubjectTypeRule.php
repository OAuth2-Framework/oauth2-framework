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
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class SubjectTypeRule implements RuleInterface
{
    /**
     * @var UserInfo
     */
    private $userinfo;

    /**
     * SubjectTypeRule constructor.
     *
     * @param UserInfo $userinfo
     */
    public function __construct(UserInfo $userinfo)
    {
        $this->userinfo = $userinfo;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('subject_type')) {
            Assertion::string($commandParameters->get('subject_type'), 'Invalid parameter \'subject_type\'. The value must be a string.');
            $supported_types = ['public'];
            if ($this->userinfo->isPairwiseSubjectIdentifierSupported()) {
                $supported_types[] = 'pairwise';
            }

            Assertion::inArray($commandParameters->get('subject_type'), $supported_types, sprintf('The subject type \'%s\' is not supported. Please use one of the following value: %s', $commandParameters->get('subject_type'), implode(', ', $supported_types)));
            $validatedParameters = $validatedParameters->with('subject_type', $commandParameters->get('subject_type'));
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}

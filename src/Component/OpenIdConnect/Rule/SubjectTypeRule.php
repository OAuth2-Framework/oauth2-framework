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

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;

class SubjectTypeRule implements Rule
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
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('subject_type')) {
            $subjectType = $commandParameters->get('subject_type');
            if (!is_string($subjectType)) {
                throw new \InvalidArgumentException('Invalid parameter "subject_type". The value must be a string.');
            }
            $supported_types = ['public'];
            if ($this->userinfo->isPairwiseSubjectIdentifierSupported()) {
                $supported_types[] = 'pairwise';
            }

            if (!in_array($subjectType, $supported_types)) {
                throw new \InvalidArgumentException(sprintf('The subject type "%s" is not supported. Please use one of the following value(s): %s', $subjectType, implode(', ', $supported_types)));
            }
            $validatedParameters = $validatedParameters->with('subject_type', $subjectType);
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}

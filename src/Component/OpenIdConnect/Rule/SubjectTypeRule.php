<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\OpenIdConnect\Rule;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;

final class SubjectTypeRule implements Rule
{
    /**
     * @var UserInfo
     */
    private $userinfo;

    /**
     * SubjectTypeRule constructor.
     */
    public function __construct(UserInfo $userinfo)
    {
        $this->userinfo = $userinfo;
    }

    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        if ($commandParameters->has('subject_type')) {
            $subjectType = $commandParameters->get('subject_type');
            if (!\is_string($subjectType)) {
                throw new \InvalidArgumentException('Invalid parameter "subject_type". The value must be a string.');
            }
            $supported_types = ['public'];
            if ($this->userinfo->isPairwiseSubjectIdentifierSupported()) {
                $supported_types[] = 'pairwise';
            }

            if (!\in_array($subjectType, $supported_types, true)) {
                throw new \InvalidArgumentException(\Safe\sprintf('The subject type "%s" is not supported. Please use one of the following value(s): %s', $subjectType, implode(', ', $supported_types)));
            }
            $validatedParameters->set('subject_type', $subjectType);
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}

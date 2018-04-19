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

namespace OAuth2Framework\Component\AuthorizationEndpoint\UserAccount;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\CreateRedirectionException;
use OAuth2Framework\Component\Core\Message\OAuth2Message;

final class PromptNoneParameterAccountChecker implements UserAccountChecker
{
    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization)
    {
        if (null === $authorization->getUserAccount() && $authorization->hasPrompt('none')) {
            throw new CreateRedirectionException($authorization, OAuth2Message::ERROR_LOGIN_REQUIRED, 'The resource owner is not logged in.');
        }
    }
}

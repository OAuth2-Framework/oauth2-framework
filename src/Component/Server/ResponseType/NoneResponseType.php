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

namespace OAuth2Framework\Component\Server\ResponseType;

use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use SimpleBus\Message\Bus\MessageBus;

/**
 * This response type has been introduced by OpenID Connect
 * It creates an access token, but does not returns anything.
 *
 * At this time, this response type is not complete, because it always redirect the client.
 * But if no redirect URI is specified, no redirection should occurred as per OpenID Connect specification.
 *
 * @see http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html#none
 */
final class NoneResponseType implements ResponseTypeInterface
{
    /**
     * @var MessageBus
     */
    private $commandBus;

    /**
     * NoneResponseType constructor.
     *
     * @param MessageBus $commandBus
     */
    public function __construct(MessageBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedGrantTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseType(): string
    {
        return 'none';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_QUERY;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        if (1 !== count($authorization->getResponseTypes())) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'The response type \'none\' cannot be used with another response type.', 'authorization' => $authorization]);
        }

        /*
         * Fixme
         */
        /*$dataTransporter = new DataTransporter();
        $command = PrepareAccessTokenCreationCommand::create(
            $authorization->getClient()->getPublicId(),
            $authorization->getUserAccount()->getPublicId(),
            $authorization->getTokenType()->getInformation(),
            ['redirect_uri' => $authorization->getRedirectUri()],
            $authorization->getScopes(),
            null, // Refresh token
            null, // Resource Server
            $dataTransporter
        );
        $this->commandBus->handle($command);*/

        return $next($authorization);
    }
}

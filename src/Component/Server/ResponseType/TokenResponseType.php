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

use OAuth2Framework\Component\Server\Command\AccessToken\CreateAccessTokenCommand;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use SimpleBus\Message\Bus\MessageBus;

final class TokenResponseType implements ResponseTypeInterface
{
    /**
     * @var MessageBus
     */
    private $commandBus;

    /**
     * ImplicitGrantType constructor.
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
        return 'token';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        $dataTransporter = new DataTransporter();
        $command = CreateAccessTokenCommand::create(
            $authorization->getClient()->getPublicId(),
            $authorization->getUserAccount()->getPublicId(),
            DataBag::createFromArray($authorization->getTokenType()->getInformation()),
            DataBag::createFromArray(['redirect_uri' => $authorization->getRedirectUri()]),
            $authorization->getScopes(),
            null, // Refresh token
            null, // Resource Server
            $dataTransporter
        );

        $this->commandBus->handle($command);
        $parameters = $dataTransporter->getData()->getResponseData();
        foreach ($parameters as $k => $v) {
            $authorization = $authorization->withResponseParameter($k, $v);
        }

        return $next($authorization);
    }
}

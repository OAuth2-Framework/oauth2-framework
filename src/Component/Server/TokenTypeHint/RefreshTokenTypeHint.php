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

namespace OAuth2Framework\Component\Server\TokenTypeHint;

use OAuth2Framework\Component\Server\Command\RefreshToken\RevokeRefreshTokenCommand;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshToken;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Token\Token;
use SimpleBus\Message\Bus\MessageBus;

final class RefreshTokenTypeHint implements TokenTypeHintInterface
{
    /**
     * @var RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepository;

    /**
     * @var MessageBus
     */
    private $commandBus;

    /**
     * RefreshToken constructor.
     *
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     * @param MessageBus                      $commandBus
     */
    public function __construct(RefreshTokenRepositoryInterface $refreshTokenRepository, MessageBus $commandBus)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function hint(): string
    {
        return 'refresh_token';
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $token)
    {
        $id = RefreshTokenId::create($token);

        return $this->refreshTokenRepository->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function revoke(Token $token)
    {
        if (!$token instanceof RefreshToken || true === $token->isRevoked()) {
            return;
        }
        $revokeRefreshTokenCommand = RevokeRefreshTokenCommand::create($token->getRefreshTokenId());
        $this->commandBus->handle($revokeRefreshTokenCommand);
    }

    /**
     * {@inheritdoc}
     */
    public function introspect(Token $token): array
    {
        if (!$token instanceof RefreshToken || true === $token->isRevoked()) {
            return [
                'active' => false,
            ];
        }

        $result = [
            'active' => !$token->hasExpired(),
            'client_id' => $token->getClientId(),
            'exp' => $token->getExpiresAt()->getTimestamp(),
        ];

        if (!empty($token->getScopes())) {
            $result['scp'] = $token->getScopes();
        }

        return $result;
    }
}

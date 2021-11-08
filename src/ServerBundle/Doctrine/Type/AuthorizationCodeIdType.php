<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Doctrine\Type;

use Assert\Assertion;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;

final class AuthorizationCodeIdType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return $value;
        }

        Assertion::isInstanceOf($value, AuthorizationCodeId::class, 'Invalid object');

        return $value->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?AuthorizationCodeId
    {
        if ($value === null || $value instanceof AuthorizationCodeId) {
            return $value;
        }

        return AuthorizationCodeId::create($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'authorization_code_id';
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

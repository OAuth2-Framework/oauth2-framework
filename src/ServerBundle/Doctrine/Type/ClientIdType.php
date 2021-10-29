<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Doctrine\Type;

use Assert\Assertion;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use OAuth2Framework\Component\Core\Client\ClientId;

final class ClientIdType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return $value;
        }
        Assertion::isInstanceOf($value, ClientId::class, 'Invalid object');

        return $value->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?ClientId
    {
        if ($value === null || $value instanceof ClientId) {
            return $value;
        }

        return new ClientId($value);
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
        return 'client_id';
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

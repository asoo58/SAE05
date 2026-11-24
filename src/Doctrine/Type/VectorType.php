<?php

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class VectorType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VECTOR(768)';
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return ['vector'];
    }
}

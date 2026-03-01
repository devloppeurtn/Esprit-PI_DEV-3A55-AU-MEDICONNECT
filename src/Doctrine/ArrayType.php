<?php

namespace App\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

class ArrayType extends JsonType
{
    public function getName(): string
    {
        return 'array';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        $decoded = parent::convertToPHPValue($value, $platform);

        if ($decoded === null) {
            return [];
        }

        return $decoded;
    }
}


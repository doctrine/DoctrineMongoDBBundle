<?php

declare(strict_types=1);

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;

return static function (ClassMetadata $metadata): void {
    $metadata->mapField([
        'name'      => '_id',
        'fieldName' => 'id',
        'type'      => Type::ID,
        'id'        => true,
    ]);
    $metadata->mapField([
        'name'      => 'name',
        'fieldName' => 'name',
        'type'      => Type::STRING,
    ]);
};

<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedXmlDriver as BaseXmlDriver;

/**
 * XmlDriver that additionally looks for mapping information in a global file.
 */
class XmlDriver extends BaseXmlDriver
{
    public const DEFAULT_FILE_EXTENSION = '.mongodb.xml';

    /**
     * {@inheritDoc}
     */
    public function __construct($prefixes, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($prefixes, $fileExtension);
    }
}

<?php


namespace Doctrine\Bundle\MongoDBBundle\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedYamlDriver as BaseYamlDriver;

/**
 * YamlDriver that additionally looks for mapping information in a global file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kris Wallsmith <kris@symfony.com>
 */
class YamlDriver extends BaseYamlDriver
{
    const DEFAULT_FILE_EXTENSION = '.mongodb.yml';

    /**
     * {@inheritDoc}
     */
    public function __construct($prefixes, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($prefixes, $fileExtension);
    }
}

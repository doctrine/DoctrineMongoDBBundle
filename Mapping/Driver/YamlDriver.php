<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Mapping\Driver;

@trigger_error('The '.__NAMESPACE__.'\YamlDriver class is deprecated since version 3.1 and will be removed in 4.0. Use the Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedYamlDriver class instead.', E_USER_DEPRECATED);

use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedYamlDriver as BaseYamlDriver;

/**
 * YamlDriver that additionally looks for mapping information in a global file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kris Wallsmith <kris@symfony.com>
 * @deprecated since version 3.1, to be removed in 4.0.
 *             Use {@link Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedYamlDriver} instead.
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

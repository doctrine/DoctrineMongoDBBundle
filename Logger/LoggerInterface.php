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

namespace Doctrine\Bundle\MongoDBBundle\Logger;

/**
 * Logger for the Doctrine MongoDB ODM.
 *
 * The {@link logQuery()} method must be configured as the logger callable in
 * the service container.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
interface LoggerInterface
{
    function logQuery(array $query);
}

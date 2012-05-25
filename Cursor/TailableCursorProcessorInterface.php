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

namespace Doctrine\Bundle\MongoDBBundle\Cursor;

/**
 * Contract for tailable cursor processors.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
interface TailableCursorProcessorInterface
{
    function process($document);
}

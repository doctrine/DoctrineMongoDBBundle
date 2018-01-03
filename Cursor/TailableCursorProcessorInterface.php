<?php


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

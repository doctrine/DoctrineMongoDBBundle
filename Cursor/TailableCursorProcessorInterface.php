<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Cursor;

/**
 * Contract for tailable cursor processors.
 */
interface TailableCursorProcessorInterface
{
    /**
     * @param mixed $document
     */
    public function process($document);
}

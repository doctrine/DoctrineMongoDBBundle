<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * Base class for Doctrine ODM console commands to extend.
 *
 * @internal since version 5.0
 */
abstract class DoctrineODMCommand extends Command
{
    public function __construct(private ManagerRegistry $registry)
    {
        parent::__construct();
    }

    /** @param string $dmName */
    public static function setApplicationDocumentManager(Application $application, $dmName)
    {
        $dm        = $application->getKernel()->getContainer()->get('doctrine_mongodb')->getManager($dmName);
        $helperSet = $application->getHelperSet();
        $helperSet->set(new DocumentManagerHelper($dm), 'dm');
    }

    /**
     * @internal
     *
     * @return ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        return $this->registry;
    }
}

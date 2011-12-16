<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\DoctrineMongoDBBundle;

use Doctrine\Bundle\DoctrineMongoDBBundle\DependencyInjection\Compiler\CreateHydratorDirectoryPass;
use Doctrine\Bundle\DoctrineMongoDBBundle\DependencyInjection\Compiler\CreateProxyDirectoryPass;
use Doctrine\Bundle\DoctrineMongoDBBundle\DependencyInjection\Compiler\EventManagerPass;
use Doctrine\Bundle\DoctrineMongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Doctrine MongoDB ODM bundle.
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Kris Wallsmith <kris@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class DoctrineMongoDBBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EventManagerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new CreateProxyDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new CreateHydratorDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    public function getContainerExtension()
    {
        return new DoctrineMongoDBExtension();
    }
}

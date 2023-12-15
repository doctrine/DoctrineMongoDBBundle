<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DeprecateChangedClassParametersPass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DeprecateChangesClassParametersPassTest extends TestCase
{
    use ExpectDeprecationTrait;

    /** @group legacy */
    public function testChangeParameterClass(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('doctrine_mongodb.odm.connection.class', stdClass::class);

        $container->addCompilerPass(new DeprecateChangedClassParametersPass());

        $this->expectDeprecation('Since doctrine/mongodb-odm-bundle 4.7: "doctrine_mongodb.odm.connection.class" parameter is deprecated, use a compiler pass to update the service instead.');

        $container->compile();
    }
}

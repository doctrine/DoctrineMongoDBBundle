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

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DoctrineMongoDBExtensionTest extends \PHPUnit_Framework_TestCase
{
    public static function buildConfiguration(array $settings = array())
    {
        return array(array_merge(
            array(
                'connections' => array('dummy' => array()),
                'document_managers' => array('dummy' => array()),
            ),
            $settings
        ));
    }

    public function buildMinimalContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.root_dir'    => __DIR__,
            'kernel.environment' => 'test',
            'kernel.debug'       => 'true',
            'kernel.bundles'     => array(),
        )));
        return $container;
    }

    public function testBackwardCompatibilityAliases()
    {
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(), $container = $this->buildMinimalContainer());

        $this->assertEquals('doctrine_mongodb.odm.document_manager', (string) $container->getAlias('doctrine.odm.mongodb.document_manager'));
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testParameterOverride($option, $parameter, $value)
    {
        $container = $this->buildMinimalContainer();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', array());
        $loader = new DoctrineMongoDBExtension();
        $loader->load(self::buildConfiguration(array($option => $value)), $container);

        $this->assertEquals($value, $container->getParameter('doctrine_mongodb.odm.'.$parameter));
    }

    private function getContainer($bundles = 'YamlBundle')
    {
        $bundles = (array) $bundles;

        $map = array();
        foreach ($bundles as $bundle) {
            require_once __DIR__.'/Fixtures/Bundles/'.$bundle.'/'.$bundle.'.php';

            $map[$bundle] = 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\\'.$bundle.'\\'.$bundle;
        }

        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
            'kernel.bundles'     => $map,
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir'    => __DIR__.'/../../' // src dir
        )));
    }

    public function parameterProvider()
    {
        return array(
            array('proxy_namespace', 'proxy_namespace', 'foo'),
            array('proxy-namespace', 'proxy_namespace', 'bar'),
        );
    }

    public function getAutomappingConfigurations()
    {
        return array(
            array(
                array(
                    'dm1' => array(
                        'mappings' => array(
                            'YamlBundle' => null
                        )
                    ),
                    'dm2' => array(
                        'mappings' => array(
                            'XmlBundle' => null
                        )
                    )
                )
            ),
            array(
                array(
                    'dm1' => array(
                        'auto_mapping' => true
                    ),
                    'dm2' => array(
                        'mappings' => array(
                            'XmlBundle' => null
                        )
                    )
                )
            ),
            array(
                array(
                    'dm1' => array(
                        'auto_mapping' => true,
                        'mappings' => array(
                            'YamlBundle' => null
                        )
                    ),
                    'dm2' => array(
                        'mappings' => array(
                            'XmlBundle' => null
                        )
                    )
                )
            )
        );
    }

    /**
     * @dataProvider getAutomappingConfigurations
     */
    public function testAutomapping(array $documentManagers)
    {
        $container = $this->getContainer(array(
            'YamlBundle',
            'XmlBundle'
        ));

        $loader = new DoctrineMongoDBExtension();

        $loader->load(
            array(
                array(
                    'default_database' => 'test_database',
                    'connections' => array(
                        'cn1' => array(),
                        'cn2' => array()
                    ),
                    'document_managers' => $documentManagers
                )
            ), $container);

        $configDm1 = $container->getDefinition('doctrine_mongodb.odm.dm1_configuration');
        $configDm2 = $container->getDefinition('doctrine_mongodb.odm.dm2_configuration');

        $this->assertContains(
            array(
                'setDocumentNamespaces',
                array(
                    array(
                        'YamlBundle' => 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\YamlBundle\Document'
                    )
                )
            ), $configDm1->getMethodCalls());

        $this->assertContains(
            array(
                'setDocumentNamespaces',
                array(
                    array(
                        'XmlBundle' => 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\XmlBundle\Document'
                    )
                )
            ), $configDm2->getMethodCalls());
    }
}

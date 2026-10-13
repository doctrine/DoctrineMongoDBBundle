<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\EntityExistsValidator;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function class_exists;
use function sys_get_temp_dir;

class ContainerTest extends TestCase
{
    private ContainerBuilder $container;

    private DoctrineMongoDBExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles'         => [],
            'kernel.cache_dir'       => sys_get_temp_dir(),
            'kernel.root_dir'        => sys_get_temp_dir(),
            'kernel.project_dir'     => sys_get_temp_dir(),
            'kernel.environment'     => 'test',
            'kernel.name'            => 'kernel',
            'kernel.debug'           => true,
            'kernel.container_class' => Container::class,
        ]));

        $this->extension = new DoctrineMongoDBExtension();
    }

    #[DataProvider('provideLoggerConfigs')]
    public function testLoggerConfig(bool $expected, array $config, bool $debug): void
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->extension->load([$config], $this->container);

        $definition = $this->container->getDefinition('doctrine_mongodb.odm.psr_command_logger');
        $this->assertSame($expected, $definition->hasTag('doctrine_mongodb.odm.command_logger'));

        $this->container->compile();

        // Fetch the command logger registry to make sure the appropriate number of services has been registered
        $this->container->get('doctrine_mongodb.odm.command_logger_registry');
    }

    /** @return array<string, array{expected: bool, config: array, debug: bool}> */
    public static function provideLoggerConfigs(): array
    {
        $config = ['connections' => ['default' => []]];

        return [
            'Debug mode enabled' => [
                // Logging is always enabled in debug mode
                'expected' => true,
                'config' => [
                    'document_managers' => ['default' => []],
                ] + $config,
                'debug' => true,
            ],
            'Debug mode disabled' => [
                // Logging is disabled by default when not in debug mode
                'expected' => false,
                'config' => [
                    'document_managers' => ['default' => []],
                ] + $config,
                'debug' => false,
            ],
            'Logging enabled by config' => [
                // Logging can be enabled by config
                'expected' => true,
                'config' => [
                    'document_managers' => ['default' => ['logging' => true]],
                ] + $config,
                'debug' => false,
            ],
        ];
    }

    #[DataProvider('provideDataCollectorConfigs')]
    public function testDataCollectorConfig(bool $expected, array $config, bool $debug): void
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->extension->load([$config], $this->container);

        $loggerDefinition = $this->container->getDefinition('doctrine_mongodb.odm.data_collector.command_logger');
        $this->assertSame($expected, $loggerDefinition->hasTag('doctrine_mongodb.odm.command_logger'));

        $stopwatchLoggerDefinition = $this->container->getDefinition('doctrine_mongodb.odm.stopwatch_command_logger');
        $this->assertSame($expected, $stopwatchLoggerDefinition->hasTag('doctrine_mongodb.odm.command_logger'));

        $dataCollectorDefinition = $this->container->getDefinition('doctrine_mongodb.odm.data_collector');
        $this->assertSame($expected, $dataCollectorDefinition->hasTag('data_collector'));

        $this->container->compile();

        // Fetch the command logger registry to make sure the appropriate number of services has been registered
        $this->container->get('doctrine_mongodb.odm.command_logger_registry');
    }

    /** @return array<string, array{expected: bool, config: array, debug: bool}> */
    public static function provideDataCollectorConfigs(): array
    {
        $config = ['connections' => ['default' => []]];

        return [
            'Debug mode enabled' => [
                // Profiling is always enabled in debug mode
                'expected' => true,
                'config' => [
                    'document_managers' => ['default' => []],
                ] + $config,
                'debug' => true,
            ],
            'Debug mode disabled' => [
                // Profiling is disabled by default when not in debug mode
                'expected' => false,
                'config' => [
                    'document_managers' => ['default' => []],
                ] + $config,
                'debug' => false,
            ],
            'Profiling enabled by config' => [
                // Profiling can be enabled by config
                'expected' => true,
                'config' => [
                    'document_managers' => ['default' => ['profiler' => true]],
                ] + $config,
                'debug' => false,
            ],
        ];
    }

    public function testEntityExistsValidatorIsRegisteredWhenAvailable(): void
    {
        $this->extension->load([
            [
                'connections' => ['default' => []],
                'document_managers' => ['default' => []],
            ],
        ], $this->container);

        // EntityExistsValidator exists since symfony/doctrine-bridge 8.2
        if (class_exists(EntityExistsValidator::class)) {
            $definition = $this->container->getDefinition('doctrine_odm.mongodb.validator.exists');
            $this->assertSame(EntityExistsValidator::class, $definition->getClass());
            $this->assertSame(
                [['alias' => 'doctrine_odm.mongodb.exists']],
                $definition->getTag('validator.constraint_validator'),
            );

            return;
        }

        $this->assertFalse($this->container->hasDefinition('doctrine_odm.mongodb.validator.exists'));
    }

    public function testDocumentValueResolverExpressionLanguageUsesTheSystemCache(): void
    {
        if (! class_exists(ExpressionLanguage::class)) {
            self::markTestSkipped('This test requires the ExpressionLanguage component');
        }

        $kernel = new class (false) extends TestKernel {
            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
                parent::registerContainerConfiguration($loader);

                // Enable framework.test so that test.service_container exposes private services.
                $loader->load(static function (ContainerBuilder $container): void {
                    $container->loadFromExtension('framework', ['test' => true]);
                });
            }
        };
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        $this->assertInstanceOf(ContainerInterface::class, $container);

        $expressionLanguage = $container->get('doctrine_mongodb.odm.document_value_resolver.expression_language');
        $this->assertInstanceOf(ExpressionLanguage::class, $expressionLanguage);

        $cache = (new ReflectionProperty(ExpressionLanguage::class, 'cache'))->getValue($expressionLanguage);
        $this->assertNotInstanceOf(ArrayAdapter::class, $cache);
    }
}

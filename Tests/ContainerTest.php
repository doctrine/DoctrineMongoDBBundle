<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

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

    /** @dataProvider provideLoggerConfigs */
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

    /** @dataProvider provideDataCollectorConfigs */
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
}

Bundles
=======

If you are developing a bundle that provides Doctrine MongoDB documents, you need to register the mappings so that the application's DocumentManager knows about them.

The ``DoctrineMongoDBBundle`` provides a compiler pass called ``DoctrineMongoDBMappingsPass`` that simplifies this process.

Example
-------

Here is an example of how to use ``DoctrineMongoDBMappingsPass`` in your bundle's ``build`` method:

.. code-block:: php

    namespace Awesome\AwesomeBundle;

    use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\HttpKernel\Bundle\Bundle;

    class AwesomeBundle extends Bundle
    {
        public function build(ContainerBuilder $container): void
        {
            parent::build($container);

            // The path to your mapping files
            $mappings = [
                __DIR__.'/Resources/config/doctrine-mapping' => 'Awesome\AwesomeBundle\Model',
            ];

            $container->addCompilerPass(
                DoctrineMongoDBMappingsPass::createXmlMappingDriver(
                    $mappings,
                    ['awesome_bundle.model_manager_name'],
                    'awesome_bundle.backend_type_mongodb'
                )
            );
        }
    }

The mapping configuration will be enabled for the specified DocumentManager
when parameter ``awesome_bundle.backend_type_mongodb`` is set to ``true``;
the DocumentManager that is used is determined by the value of the
``awesome_bundle.model_manager_name`` parameter.

Available Methods
-----------------

The ``DoctrineMongoDBMappingsPass`` class provides several static methods to create the compiler pass depending on your mapping type:

* ``createXmlMappingDriver``
* ``createPhpMappingDriver``
* ``createAttributeMappingDriver``
* ``createStaticPhpMappingDriver``

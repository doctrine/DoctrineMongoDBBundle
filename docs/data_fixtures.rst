Data Fixtures
=============

Data fixtures are a way to load sample data into your MongoDB database.
This is useful for testing, development, and seeding your database with initial data.

Installation
------------

First, install the `doctrine/data-fixtures`_ package:

.. code-block:: bash

    composer require --dev doctrine/data-fixtures

Creating Fixtures
-----------------

To create a fixture, create a class that implements ``ODMFixtureInterface`` or extends the
``Fixture`` base class. The best practice is to extend ``Doctrine\Bundle\MongoDBBundle\Fixture\Fixture``,
as it provides convenient access to the object manager and reference functionality:

.. code-block:: php

    // src/DataFixtures/ProductFixtures.php
    namespace App\DataFixtures;

    use App\Document\Product;
    use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
    use Doctrine\Persistence\ObjectManager;

    class ProductFixtures extends Fixture
    {
        public function load(ObjectManager $manager): void
        {
            $product = new Product();
            $product->setName('Example Product');
            $product->setPrice(19.99);

            $manager->persist($product);
            $manager->flush();
        }
    }

If you prefer not to extend the base ``Fixture`` class, you can implement the ``ODMFixtureInterface``
directly:

.. code-block:: php

    // src/DataFixtures/ProductFixtures.php
    namespace App\DataFixtures;

    use App\Document\Product;
    use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
    use Doctrine\Persistence\ObjectManager;

    class ProductFixtures implements ODMFixtureInterface
    {
        public function load(ObjectManager $manager): void
        {
            $product = new Product();
            $product->setName('Example Product');
            $product->setPrice(19.99);

            $manager->persist($product);
            $manager->flush();
        }
    }

Registering Fixtures
--------------------

Fixtures are automatically discovered and registered as services if they are
in an autoconfigured service namespace (e.g. ``App\DataFixtures``). If your fixtures are located
elsewhere, or if you have disabled autoconfiguration, you need to manually tag them
with the ``doctrine.fixture.odm.mongodb`` tag:

.. configuration-block::

    .. code-block:: yaml

        # config/services.yaml
        services:
            App\DataFixtures\ProductFixtures:
                tags:
                    - { name: 'doctrine.fixture.odm.mongodb' }


Loading Fixtures
----------------

You can load fixtures using the command line:

.. code-block:: bash

    php bin/console doctrine:mongodb:fixtures:load

This command will load all registered fixtures. You can also append data instead of truncating:

.. code-block:: bash

    # Append fixtures without truncating the database
    php bin/console doctrine:mongodb:fixtures:load --append

Fixture Dependencies
--------------------

Sometimes you need to load fixtures in a specific order because one fixture depends on data
created by another fixture. You can implement the ``DependentFixtureInterface`` to specify
which fixtures must be loaded first:

.. code-block:: php

    // src/DataFixtures/CategoryFixtures.php
    namespace App\DataFixtures;

    use App\Document\Category;
    use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
    use Doctrine\Persistence\ObjectManager;

    class CategoryFixtures extends Fixture
    {
        public function load(ObjectManager $manager): void
        {
            $category = new Category();
            $category->setName('Electronics');

            $manager->persist($category);
            $manager->flush();

            // Store a reference for other fixtures to use
            $this->addReference('category-electronics', $category);
        }
    }

.. code-block:: php

    // src/DataFixtures/ProductFixtures.php
    namespace App\DataFixtures;

    use App\Document\Product;
    use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
    use Doctrine\Common\DataFixtures\DependentFixtureInterface;
    use Doctrine\Persistence\ObjectManager;

    class ProductFixtures extends Fixture implements DependentFixtureInterface
    {
        public function load(ObjectManager $manager): void
        {
            $product = new Product();
            $product->setName('Laptop');
            $product->setPrice(999.99);
            $product->setCategory($this->getReference('category-electronics'));

            $manager->persist($product);
            $manager->flush();
        }

        public function getDependencies(): array
        {
            return [CategoryFixtures::class];
        }
    }

Using References
----------------

The ``Fixture`` base class provides methods to store and retrieve references to documents,
which is useful when you need to reference one fixture from another:

.. code-block:: php

    // Store a reference
    $this->addReference('my-product', $product);

    // Retrieve a reference
    $product = $this->getReference('my-product');

    // Check if a reference exists
    if ($this->hasReference('my-product')) {
        $product = $this->getReference('my-product');
    }

More Information
----------------

For more details about data fixtures and advanced usage patterns, see the official
`Doctrine Data Fixtures documentation`_.

.. _`doctrine/data-fixtures`: https://packagist.org/packages/doctrine/data-fixtures
.. _`Doctrine Data Fixtures documentation`: https://www.doctrine-project.org/projects/doctrine-data-fixtures/en/current/how-to/loading-fixtures.html


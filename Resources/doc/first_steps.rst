First steps using the ODM
=========================

The best way to understand the Doctrine MongoDB ODM is to see it in action.
In this section, you'll walk through each step needed to start persisting
documents to and from MongoDB.

.. sidebar:: Code along with the example

    If you want to follow along with the example in this chapter, create
    an ``AcmeStoreBundle`` via:

    .. code-block:: bash

        php bin/console generate:bundle --namespace=Acme/StoreBundle

A Simple Example: A Product
---------------------------

Creating a Document Class
~~~~~~~~~~~~~~~~~~~~~~~~~

Suppose you're building an application where products need to be displayed.
Without even thinking about Doctrine or MongoDB, you already know that you
need a ``Product`` object to represent those products. Create this class
inside the ``Document`` directory of your ``AcmeStoreBundle``:

.. code-block:: php

    // src/Acme/StoreBundle/Document/Product.php
    namespace Acme\StoreBundle\Document;

    class Product
    {
        protected $name;

        protected $price;
    }

The class - often called a "document", meaning *a basic class that holds data* -
is simple and helps fulfill the business requirement of needing products
in your application. This class can't be persisted to Doctrine MongoDB yet -
it's just a simple PHP class.

Add Mapping Information
~~~~~~~~~~~~~~~~~~~~~~~

Doctrine allows you to work with MongoDB in a much more interesting way
than just fetching data back and forth as an array. Instead, Doctrine allows
you to persist entire *objects* to MongoDB and fetch entire objects out of
MongoDB. This works by mapping a PHP class and its properties to entries
of a MongoDB collection.

For Doctrine to be able to do this, you just have to create "metadata", or
configuration that tells Doctrine exactly how the ``Product`` class and its
properties should be *mapped* to MongoDB. This metadata can be specified
in a number of different formats including XML or directly inside the
``Product`` class via annotations:

.. configuration-block::

    .. code-block:: php

        // src/Acme/StoreBundle/Document/Product.php
        namespace Acme\StoreBundle\Document;

        use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

        /**
         * @MongoDB\Document
         */
        class Product
        {
            /**
             * @MongoDB\Id
             */
            protected $id;

            /**
             * @MongoDB\Field(type="string")
             */
            protected $name;

            /**
             * @MongoDB\Field(type="float")
             */
            protected $price;
        }

    .. code-block:: xml

        <!-- src/Acme/StoreBundle/Resources/config/doctrine/Product.mongodb.xml -->
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                            http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

            <document name="Acme\StoreBundle\Document\Product">
                <id />
                <field fieldName="name" type="string" />
                <field fieldName="price" type="float" />
            </document>
        </doctrine-mongo-mapping>

.. seealso::

    You can also check out Doctrine's `Basic Mapping Documentation`_ for
    all details about mapping information. If you use annotations, you'll
    need to prepend all annotations with ``MongoDB\`` (e.g. ``MongoDB\String``),
    which is not shown in Doctrine's documentation. You'll also need to include
    the ``use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;`` statement,
    which *imports* the ``MongoDB`` annotations prefix.

Persisting Objects to MongoDB
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Now that you have a mapped ``Product`` document complete with getter and
setter methods, you're ready to persist data to MongoDB. From inside a controller,
this is pretty easy. Add the following method to the ``DefaultController``
of the bundle:

.. code-block:: php
    :linenos:

    // src/Acme/StoreBundle/Controller/DefaultController.php
    use Acme\StoreBundle\Document\Product;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Symfony\Component\HttpFoundation\Response;
    // ...

    public function createAction(DocumentManager $dm)
    {
        $product = new Product();
        $product->setName('A Foo Bar');
        $product->setPrice('19.99');

        $dm->persist($product);
        $dm->flush();

        return new Response('Created product id '.$product->getId());
    }

.. note::

    If you're following along with this example, you'll need to create a
    route that points to this action to see it in work.

Let's walk through this example:

* **lines 8-10** In this section, you instantiate and work with the ``$product``
  object like any other, normal PHP object;

* **line 12** This line fetches Doctrine's *document manager* object, which is
  responsible for handling the process of persisting and fetching objects
  to and from MongoDB;

* **line 13** The ``persist()`` method tells Doctrine to "manage" the ``$product``
  object. This does not actually cause a query to be made to MongoDB (yet);

* **line 14** When the ``flush()`` method is called, Doctrine looks through
  all of the objects that it's managing to see if they need to be persisted
  to MongoDB. In this example, the ``$product`` object has not been persisted yet,
  so the document manager makes a query to MongoDB, which adds a new entry.

If you are using `autowiring`, you can use type hinting to fetch the ``doctrine_mongodb.odm.document_manager`` service:

.. code-block:: php

    // App/Controller/DefaultController.php
    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
    use App\Document\Product;
    use Symfony\Component\HttpFoundation\Response;

    class DefaultController extends AbstractController
    {
        public function createProduct(DocumentManager $dm)
        {
            $product = new Product();
            $product->setName('A Foo Bar');
            $product->setPrice('19.99');

            $dm->persist($product);
            $dm->flush();

            return new Response('Created product id '.$product->getId());
        }
    }

.. note::

    In fact, since Doctrine is aware of all your managed objects, when you
    call the ``flush()`` method, it calculates an overall changeset and executes
    the most efficient operation possible.

When creating or updating objects, the workflow is always the same. In the
next section, you'll see how Doctrine is smart enough to update entries if
they already exist in MongoDB.

.. tip::

    Doctrine provides a library that allows you to programmatically load testing
    data into your project (i.e. "fixture data"). For information, see
    `DoctrineFixturesBundle`_.

Fetching Objects from MongoDB
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Fetching an object back out of MongoDB is even easier. For example, suppose
you've configured a route to display a specific ``Product`` based on its
``id`` value:

.. code-block:: php

    public function showAction(DocumentManager $dm, $id)
    {
        $product = $dm->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException('No product found for id '.$id);
        }

        // do something, like pass the $product object into a template
    }

When you query for a particular type of object, you always use what's known
as its "repository". You can think of a repository as a PHP class whose only
job is to help you fetch objects of a certain class. You can access the
repository object for a document class via:

.. code-block:: php

    $repository = $dm->getRepository(Product::class);

.. note::

    The ``AcmeStoreBundle:Product`` string is a shortcut you can use anywhere
    in Doctrine instead of the full class name of the document (i.e. ``Acme\StoreBundle\Document\Product``).
    As long as your document lives under the ``Document`` namespace of your bundle,
    this will work.

Once you have your repository, you have access to all sorts of helpful methods:

.. code-block:: php

    // query by the identifier (usually "id")
    $product = $repository->find($id);

    // dynamic method names to find based on a column value
    $product = $repository->findOneById($id);
    $product = $repository->findOneByName('foo');

    // find *all* products
    $products = $repository->findAll();

    // find a group of products based on an arbitrary column value
    $products = $repository->findByPrice(19.99);

.. note::

    Of course, you can also issue complex queries, which you'll learn more
    about in the `Querying for Objects`_ section.

You can also take advantage of the useful ``findBy()`` and ``findOneBy()`` methods
to easily fetch objects based on multiple conditions:

.. code-block:: php

    // query for one product matching be name and price
    $product = $repository->findOneBy(array('name' => 'foo', 'price' => 19.99));

    // query for all products matching the name, ordered by price
    $product = $repository->findBy(
        array('name' => 'foo'),
        array('price' => 'ASC'),
    );

Updating an Object
~~~~~~~~~~~~~~~~~~

Once you've fetched an object from Doctrine, updating it is easy. Suppose
you have a route that maps a product id to an update action in a controller:

.. code-block:: php

    public function updateAction(DocumentManager $dm, $id)
    {
        $product = $dm->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException('No product found for id '.$id);
        }

        $product->setName('New product name!');
        $dm->flush();

        return $this->redirectToRoute('homepage');
    }

Updating an object involves just three steps:

1. Fetching the object from Doctrine;
2. Modifying the object;
3. Calling ``flush()`` on the document manager.

Notice that calling ``$dm->persist($product)`` isn't necessary. Recall that
this method simply tells Doctrine to manage or "watch" the ``$product`` object.
In this case, since you fetched the ``$product`` object from Doctrine, it's
already managed.

Deleting an Object
~~~~~~~~~~~~~~~~~~

Deleting an object is very similar, but requires a call to the ``remove()``
method of the document manager:

.. code-block:: php

    $dm->remove($product);
    $dm->flush();

As you might expect, the ``remove()`` method notifies Doctrine that you'd
like to remove the given document from the MongoDB. The actual delete operation
however, isn't actually executed until the ``flush()`` method is called.

Querying for Objects
--------------------

As you saw above, the built-in repository class allows you to query for one
or many objects based on an number of different parameters. When this is
enough, this is the easiest way to query for documents. Of course, you can
also create more complex queries.

Using the Query Builder
~~~~~~~~~~~~~~~~~~~~~~~

Doctrine's ODM ships with a query "Builder" object, which allows you to construct
a query for exactly which documents you want to return. If you use an IDE,
you can also take advantage of auto-completion as you type the method names.
From inside a controller:

.. code-block:: php

    $products = $dm->createQueryBuilder(Product::class)
        ->field('name')->equals('foo')
        ->sort('price', 'ASC')
        ->limit(10)
        ->getQuery()
        ->execute()

In this case, 10 products with a name of "foo", ordered from lowest price
to highest price are returned.

The ``QueryBuilder`` object contains every method necessary to build your
query. For more information on Doctrine's Query Builder, consult Doctrine's
`Query Builder`_ documentation. For a list of the available conditions you
can place on the query, see the `Conditional Operators`_ documentation specifically.

Custom Repository Classes
~~~~~~~~~~~~~~~~~~~~~~~~~

In the previous section, you began constructing and using more complex queries
from inside a controller. In order to isolate, test and reuse these queries,
it's a good idea to create a custom repository class for your document and
add methods with your query logic there.

To do this, add the name of the repository class to your mapping definition.

.. configuration-block::

    .. code-block:: php-annotations

        // src/Acme/StoreBundle/Document/Product.php
        namespace Acme\StoreBundle\Document;

        use Acme\StoreBundle\Repository\ProductRepository;
        use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

        /**
         * @MongoDB\Document(repositoryClass=ProductRepository::class)
         */
        class Product
        {
            //...
        }

    .. code-block:: xml

        <!-- src/Acme/StoreBundle/Resources/config/doctrine/Product.mongodb.xml -->
        <!-- ... -->
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                            http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

            <document name="Acme\StoreBundle\Document\Product"
                    repository-class="Acme\StoreBundle\Repository\ProductRepository">
                <!-- ... -->
            </document>

        </doctrine-mongo-mapping>

You have to create the repository in the namespace indicated above. Make sure it
extends the default ``DocumentRepository``. Next, add a new method -
``findAllOrderedByName()`` - to the new repository class. This method will query
for all of the ``Product`` documents, ordered alphabetically.

.. code-block:: php

    // src/Acme/StoreBundle/Repository/ProductRepository.php
    namespace Acme\StoreBundle\Repository;

    use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

    class ProductRepository extends DocumentRepository
    {
        public function findAllOrderedByName()
        {
            return $this->createQueryBuilder()
                ->sort('name', 'ASC')
                ->getQuery()
                ->execute();
        }
    }

You can use this new method just like the default finder methods of the repository:

.. code-block:: php

    $products = $dm->getRepository(Product::class)
        ->findAllOrderedByName();


.. note::

    When using a custom repository class, you still have access to the default
    finder methods such as ``find()`` and ``findAll()``.

Service Repositories
~~~~~~~~~~~~~~~~~~~~

In the previous section, you learnt how to create custom repository classes and how
to get them using ``DocumentManager``. Another way of obtaining a repository instance
is to use the repository as a service and inject it as a dependency into other services.

.. code-block:: php

    // src/Acme/StoreBundle/Repository/ProductRepository.php
    namespace Acme\StoreBundle\Repository;

    use Acme\StoreBundle\Document\Product;
    use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
    use Doctrine\Common\Persistence\ManagerRegistry;

    /**
     * Remember to map this repository in the corresponding document's repositoryClass.
     * For more information on this see the previous chapter.
     */
    class ProductRepository extends ServiceDocumentRepository
    {
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, Product::class);
        }
    }

The ``ServiceDocumentRepository`` class your custom repository is extending allows you to
leverage Symfony's `autowiring`_ and `autoconfiguration`_. To register all of your
repositories as services you can use the following service configuration:

.. configuration-block::

    .. code-block:: yaml

        services:
            _defaults:
                autowire: true
                autoconfigure: true

            Acme\StoreBundle\Repository\:
                resource: '%kernel.root_dir%/../src/Acme/StoreBundle/Repository/*'

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services
                https://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <defaults autowire="true" autoconfigure="true"/>

                <prototype namespace="Acme\StoreBundle\Repository\" resource="%kernel.root_dir%/../src/Acme/StoreBundle/Repository/*"/>
            </services>
        </container>

.. _`Basic Mapping Documentation`: http://docs.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/basic-mapping.html
.. _`Conditional Operators`: http://docs.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/query-builder-api.html#conditional-operators
.. _`DoctrineFixturesBundle`: http://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html
.. _`Query Builder`: http://docs.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/query-builder-api.html
.. _`autowiring`: https://symfony.com/doc/current/service_container/autowiring.html
.. _`autoconfiguration`: https://symfony.com/doc/current/service_container.html#the-autoconfigure-option

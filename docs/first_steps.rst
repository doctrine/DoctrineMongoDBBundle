First steps using the ODM
=========================

The best way to understand the Doctrine MongoDB ODM is to see it in action.
In this section, you'll walk through each step needed to start persisting
documents to and from MongoDB.

An Introductory Example: A Product
----------------------------------

Creating a Document Class
~~~~~~~~~~~~~~~~~~~~~~~~~

Suppose you're building an application where products need to be displayed.
Without even thinking about Doctrine or MongoDB, you already know that you
need a ``Product`` object to represent those products. Create this class
inside the ``Document`` subdirectory of your project's source code:

.. code-block:: php

    // src/Document/Product.php
    namespace App\Document;

    class Product
    {
        protected $name;

        protected $price;
    }

The class - often called a "document", meaning *a basic class that holds data* -
helps fulfill the business requirement of needing products in your application.
This class can't be persisted to Doctrine MongoDB yet - currently it's
only a plain PHP class.

Add Mapping Information
~~~~~~~~~~~~~~~~~~~~~~~

Doctrine allows you to work with MongoDB in a much more interesting way
than just fetching data back and forth as an array. Instead, Doctrine allows
you to persist entire *objects* to MongoDB and fetch entire objects out of
MongoDB. This works by mapping a PHP class and its properties to entries
of a MongoDB collection.

For Doctrine to be able to do this, you have to create "metadata", or
configuration that tells Doctrine exactly how the ``Product`` class and its
properties should be *mapped* to MongoDB. This metadata can be specified
in a number of different formats including XML or directly inside the
``Product`` class via PHP 8 attributes:

.. versionadded:: 4.4

    The attribute mapping support was added in Doctrine MongoDB ODM Bundle 4.4 and requires PHP 8.0 or newer.

.. configuration-block::

    .. code-block:: php-attributes

        // src/Document/Product.php
        namespace App\Document;

        use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

        #[MongoDB\Document]
        class Product
        {
            #[MongoDB\Id]
            protected string $id;

            #[MongoDB\Field(type: 'string')]
            protected string $name;

            #[MongoDB\Field(type: 'float')]
            protected float $price;
        }

    .. code-block:: xml

        <!-- src/Resources/config/doctrine/Product.mongodb.xml -->
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                            https://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

            <document name="App\Document\Product">
                <id />
                <field field-name="name" type="string" />
                <field field-name="price" type="float" />
            </document>
        </doctrine-mongo-mapping>

.. seealso::

    You can also check out Doctrine's `Basic Mapping Documentation`_ for
    all details about mapping information. If you use attributes, you'll
    need to prepend all attributes with ``MongoDB\`` (e.g. ``MongoDB\String``),
    which is not shown in Doctrine's documentation. You'll also need to include
    the ``use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;`` statement,
    which *imports* the ``MongoDB`` attributes prefix.

Persisting Objects to MongoDB
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Now that you have a mapped ``Product`` document complete with getter and
setter methods, you're ready to persist data to MongoDB. Let's try it from inside
a controller. Create new Controller class inside source directory of your project:

.. code-block:: php
    :linenos:

    // src/App/Controller/ProductController.php
    use App\Document\Product;
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

        return new Response('Created product id ' . $product->getId());
    }

.. note::

    If you're following along with this example, you'll need to create a
    route that points to this action to see it in work.

Let's walk through this example:

* **lines 9-11** In this section, you instantiate and work with the ``$product``
  object like you would with any other, normal PHP object;

* **line 13** The ``persist()`` method tells Doctrine to "manage" the ``$product``
  object. This does not actually cause a query to be made to MongoDB (yet);

* **line 14** When the ``flush()`` method is called, Doctrine looks through
  all of the objects that it's managing to see if they need to be persisted
  to MongoDB. In this example, the ``$product`` object has not been persisted yet,
  so the document manager makes a query to MongoDB, which adds a new entry.

.. note::

    In fact, since Doctrine is aware of all your managed objects, when you
    call the ``flush()`` method, it calculates an overall changeset and executes
    the most efficient operation possible.

When creating or updating objects, the workflow is always the same. In the
next section, you'll see how Doctrine is smart enough to update entries if
they already exist in MongoDB.

.. tip::

    Doctrine provides a library that allows you to programmatically load testing
    data into your project (i.e. "fixture data"). For more information, see
    `DoctrineFixturesBundle`_.

Fetching Objects from MongoDB
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Fetching an object back out of MongoDB is also possible. For example, suppose
that you've configured a route to display a specific ``Product`` based on its
``id`` value:

.. code-block:: php

    public function showAction(DocumentManager $dm, $id)
    {
        $product = $dm->getRepository(Product::class)->find($id);

        if (! $product) {
            throw $this->createNotFoundException('No product found for id ' . $id);
        }

        // do something, like pass the $product object into a template
    }

When you query for a particular type of object, you always use what's known
as its "repository". You can think of a repository as a PHP class whose only
job is to help you fetch objects of a certain class. You can access the
repository object for a document class via:

.. code-block:: php

    $repository = $dm->getRepository(Product::class);

Once you have your repository, you have access to all sorts of helpful methods:

.. code-block:: php

    // query by the identifier (usually "id")
    $product = $repository->find($id);

    // find *all* products
    $products = $repository->findAll();

    // find a group of products based on an arbitrary field value
    $products = $repository->findBy(['price' => 19.99]);

.. note::

    You can also issue complex queries, you can learn more about them
    in the `Querying for Objects`_ section.

You can also take advantage of the useful ``findBy()`` and ``findOneBy()`` methods
to easily fetch objects based on multiple conditions:

.. code-block:: php

    // query for one product matching by name and price
    $product = $repository->findOneBy(['name' => 'foo', 'price' => 19.99]);

    // query for all products matching the name, ordered by price
    $product = $repository->findBy(
        ['name' => 'foo'],
        ['price' => 'ASC']
    );

Automatically Fetching Objects (DocumentValueResolver)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 4.6

    The support of this feature was added in Doctrine MongoDB ODM Bundle 4.6.

In many cases, you can use the ``DocumentValueResolver`` to do the query for
you automatically! You can simplify the controller to:

.. code-block:: php

    // src/Controller/ProductController.php
    namespace App\Controller;

    use App\Document\Product;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    // ...

    #[Route('/product/{id}')]
    public function showAction(Product $product): Response
    {
        // use the Product!
        // do something, like pass the $product object into a template
    }

That's it! The bundle uses the ``{id}`` from the route to query for the ``Product``
by the ``id`` field. If it's not found, a 404 page is generated.

This behavior is enabled by default on all your controllers. You can
disable it by setting the ``doctrine_mongodb.controller_resolver.auto_mapping``
config option to ``false``.

When disabled, you can enable it individually on the desired controllers by
using the ``MapDocument`` attribute:

.. code-block:: php

    // src/Controller/ProductController.php
    namespace App\Controller;

    use App\Document\Product;
    use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    // ...

    class ProductController extends AbstractController
    {
        #[Route('/product/{id}')]
        public function show(
            #[MapDocument]
            Product $product
        ): Response {
            // use the Product!
            // ...
        }
    }

.. tip::

    When enabled globally, it's possible to disable the behavior on a specific
    controller, by using the ``MapDocument`` set to ``disabled``:

.. code-block:: php

        public function show(
            #[CurrentUser]
            #[MapDocument(disabled: true)]
            User $user
        ): Response {
            // User is not resolved by the DocumentValueResolver
            // ...
        }

Fetch Automatically
~~~~~~~~~~~~~~~~~~~

If your route wildcards match properties in your document, then the resolver
will automatically fetch them:

.. code-block:: php

    /**
     * Fetch via identifier because {id} is in the route.
     */
    #[Route('/product/{id}')]
    public function showByIdentifier(Post $post): Response
    {
    }

    /**
     * Perform a findOneBy() where the slug property matches {slug}.
     */
    #[Route('/product/{slug}')]
    public function showBySlug(Post $post): Response
    {
    }

Automatic fetching works in these situations:

* If ``{id}`` is in your route, then this is used to fetch by
  identifier via the ``find()`` method.

* The resolver will attempt to do a ``findOneBy()`` fetch by using
  *all* of the wildcards in your route that are actually properties
  on your document (non-properties are ignored).

You can control this behavior by actually *adding* the ``MapDocument``
attribute and using the `MapDocument options`_.

Fetch via an Expression
~~~~~~~~~~~~~~~~~~~~~~~

If automatic fetching doesn't work, you can write an expression using the
`ExpressionLanguage component`_:

.. code-block:: php

    #[Route('/product/{product_id}')]
    public function show(
        #[MapDocument(expr: 'repository.find(product_id)')]
        Product $product
    ): Response {
    }

In the expression, the ``repository`` variable will be your document's
Repository class and any route wildcards - like ``{product_id}`` are
available as variables.

This can also be used to help resolve multiple arguments:

.. code-block:: php

    #[Route('/product/{id}/comments/{comment_id}')]
    public function show(
        Product $product,
        #[MapDocument(expr: 'repository.find(comment_id)')]
        Comment $comment
    ): Response {
    }

In the example above, the ``$product`` argument is handled automatically,
but ``$comment`` is configured with the attribute since they cannot both follow
the default convention.

MapDocument Options
~~~~~~~~~~~~~~~~~~~

A number of options are available on the ``MapDocument`` attribute to
control behavior:

``id``
    If an ``id`` option is configured and matches a route parameter, then
    the resolver will find by the identifier:

.. code-block:: php

        #[Route('/product/{product_id}')]
        public function show(
            #[MapDocument(id: 'product_id')]
            Product $product
        ): Response {
        }

``mapping``
    Configures the properties and values to use with the ``findOneBy()``
    method: the key is the route placeholder name and the value is the Doctrine
    property name:

.. code-block:: php

        #[Route('/product/{category}/{slug}/comments/{comment_slug}')]
        public function show(
            #[MapDocument(mapping: ['category' => 'category', 'slug' => 'slug'])]
            Product $product,
            #[MapDocument(mapping: ['comment_slug' => 'slug'])]
            Comment $comment
        ): Response {
        }

``exclude``
    Configures the properties that should be used in the ``findOneBy()``
    method by *excluding* one or more properties so that not *all* are used:

.. code-block:: php

        #[Route('/product/{slug}/{date}')]
        public function show(
            #[MapDocument(exclude: ['date'])]
            Product $product,
            \DateTime $date
        ): Response {
        }

``stripNull``
    If true, then when ``findOneBy()`` is used, any values that are
    ``null`` will not be used for the query.

``objectManager``
    By default, the ``DocumentValueResolver`` will choose the document manager
    that has the class registered for it, but you can configure this:

.. code-block:: php

        #[Route('/product/{id}')]
        public function show(
            #[MapDocument(objectManager: 'foo')]
            Product $product
        ): Response {
        }

``disabled``
    If true, the ``DoctrineValueResolver`` will not try to replace the argument.

Updating an Object
~~~~~~~~~~~~~~~~~~

Once you've fetched an object from Doctrine, let's try to update it. Suppose
you have a route that maps a product id to an update action in a controller:

.. code-block:: php

    public function updateAction(DocumentManager $dm, $id)
    {
        $product = $dm->getRepository(Product::class)->find($id);

        if (! $product) {
            throw $this->createNotFoundException('No product found for id ' . $id);
        }

        $product->setName('New product name!');

        $dm->flush();

        return $this->redirectToRoute('homepage');
    }

Updating an object involves three steps:

1. Fetching the object from Doctrine;
2. Modifying the object;
3. Calling ``flush()`` on the document manager.

Notice that calling ``$dm->persist($product)`` isn't necessary. Recall that
this method tells Doctrine to manage or "watch" the ``$product`` object.
In this case, since you fetched the ``$product`` object from Doctrine, it's
already managed.

Deleting an Object
~~~~~~~~~~~~~~~~~~

Deleting an object is very similar, but requires a call to the ``remove()``
method of the document manager:

.. code-block:: php

    $dm->remove($product);
    $dm->flush();

The ``remove()`` method notifies Doctrine that you'd like to remove
the given document from the MongoDB. The actual delete operation
however, isn't executed until the ``flush()`` method is called.

Querying for Objects
--------------------

As you saw above, the built-in repository class allows you to query for one
or many objects based on any number of different parameters. When this is
enough, this is the easiest way to query for documents. You can also create
more complex queries.

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
        ->execute();

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

    .. code-block:: php-attributes

        // src/Document/Product.php
        namespace App\Document;

        use App\Repository\ProductRepository;
        use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

        #[MongoDB\Document(repositoryClass: ProductRepository::class)]
        class Product
        {
            // ...
        }

    .. code-block:: xml

        <!-- src/Resources/config/doctrine/Product.mongodb.xml -->
        <!-- ... -->
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                            https://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

            <document name="App\Document\Product"
                    repository-class="App\Repository\ProductRepository">
                <!-- ... -->
            </document>

        </doctrine-mongo-mapping>

You have to create the repository in the namespace indicated above. Make sure it
extends the default ``DocumentRepository``. Next, add a new method -
``findAllOrderedByName()`` - to the new repository class. This method will query
for all of the ``Product`` documents, ordered alphabetically.

.. code-block:: php

    // src/Repository/ProductRepository.php
    namespace App\Repository;

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

You can use this new method like the default finder methods of the repository:

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

    // src/App/Repository/ProductRepository.php
    namespace App\Repository;

    use App\Document\Product;
    use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
    use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

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

        # config/services.yaml
        services:
            _defaults:
                autowire: true
                autoconfigure: true

            App\Repository\:
                resource: '../src/Repository/*'

    .. code-block:: xml

        <!-- config/services.xml -->
        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services
                https://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <defaults autowire="true" autoconfigure="true" />

                <prototype namespace="App\Repository\" resource="../src/Repository/*" />
            </services>
        </container>

.. _`Basic Mapping Documentation`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/basic-mapping.html
.. _`ExpressionLanguage component`: https://symfony.com/doc/current/components/expression_language.html
.. _`Conditional Operators`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/query-builder-api.html#conditional-operators
.. _`DoctrineFixturesBundle`: https://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html
.. _`Query Builder`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/query-builder-api.html
.. _`autowiring`: https://symfony.com/doc/current/service_container/autowiring.html
.. _`autoconfiguration`: https://symfony.com/doc/current/service_container.html#the-autoconfigure-option

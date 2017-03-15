DoctrineMongoDBBundle Configuration
===================================

Sample Configuration
--------------------

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
        doctrine_mongodb:
            connections:
                default:
                    server: mongodb://localhost:27017
                    options: {}
            default_database: hello_%kernel.environment%
            document_managers:
                default:
                    mappings:
                        AcmeDemoBundle: ~
                    filters:
                        filter-name:
                            class: Class\Example\Filter\ODM\ExampleFilter
                            enabled: true
                    metadata_cache_driver: array # array, apc, xcache, memcache

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:doctrine_mongodb="http://symfony.com/schema/dic/doctrine/odm/mongodb"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine_mongodb:config default-database="hello_%kernel.environment%">
                <doctrine_mongodb:connection id="default" server="mongodb://localhost:27017">
                    <doctrine_mongodb:options>
                    </doctrine_mongodb:options>
                </doctrine_mongodb:connection>
                <doctrine_mongodb:document-manager id="default">
                    <doctrine_mongodb:mapping name="AcmeDemoBundle" />
                    <doctrine_mongodb:filter name="filter-name" enabled="true" class="Class\Example\Filter\ODM\ExampleFilter" />
                    <doctrine_mongodb:metadata-cache-driver type="array" />
                </doctrine_mongodb:document-manager>
            </doctrine_mongodb:config>
        </container>

.. tip::

    If each environment requires a different MongoDB connection URI, you can
    define it in a separate parameter and reference it in the bundle config:

    .. code-block:: yaml

        # app/config/parameters.yml
        mongodb_server: mongodb://localhost:27017

    .. code-block:: yaml

        # app/config/config.yml
        doctrine_mongodb:
            connections:
                default:
                    server: "%mongodb_server%"

If you wish to use memcache to cache your metadata, you need to configure the
``Memcache`` instance; for example, you can do the following:

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
        doctrine_mongodb:
            default_database: hello_%kernel.environment%
            connections:
                default:
                    server: mongodb://localhost:27017
                    options: {}
            document_managers:
                default:
                    mappings:
                        AcmeDemoBundle: ~
                    metadata_cache_driver:
                        type: memcache
                        class: Doctrine\Common\Cache\MemcacheCache
                        host: localhost
                        port: 11211
                        instance_class: Memcache

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:doctrine_mongodb="http://symfony.com/schema/dic/doctrine/odm/mongodb"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine_mongodb:config default-database="hello_%kernel.environment%">
                <doctrine_mongodb:document-manager id="default">
                    <doctrine_mongodb:mapping name="AcmeDemoBundle" />
                    <doctrine_mongodb:metadata-cache-driver type="memcache">
                        <doctrine_mongodb:class>Doctrine\Common\Cache\MemcacheCache</doctrine_mongodb:class>
                        <doctrine_mongodb:host>localhost</doctrine_mongodb:host>
                        <doctrine_mongodb:port>11211</doctrine_mongodb:port>
                        <doctrine_mongodb:instance-class>Memcache</doctrine_mongodb:instance-class>
                    </doctrine_mongodb:metadata-cache-driver>
                </doctrine_mongodb:document-manager>
                <doctrine_mongodb:connection id="default" server="mongodb://localhost:27017">
                    <doctrine_mongodb:options>
                    </doctrine_mongodb:options>
                </doctrine_mongodb:connection>
            </doctrine_mongodb:config>
        </container>


Mapping Configuration
~~~~~~~~~~~~~~~~~~~~~

Explicit definition of all the mapped documents is the only necessary
configuration for the ODM and there are several configuration options that you
can control. The following configuration options exist for a mapping:

- ``type`` One of ``annotation``, ``xml``, ``yml``, ``php`` or ``staticphp``.
  This specifies which type of metadata type your mapping uses.

- ``dir`` Path to the mapping or entity files (depending on the driver). If
  this path is relative it is assumed to be relative to the bundle root. This
  only works if the name of your mapping is a bundle name. If you want to use
  this option to specify absolute paths you should prefix the path with the
  kernel parameters that exist in the DIC (for example %kernel.root_dir%).

- ``prefix`` A common namespace prefix that all documents of this mapping
  share. This prefix should never conflict with prefixes of other defined
  mappings otherwise some of your documents cannot be found by Doctrine. This
  option defaults to the bundle namespace + ``Document``, for example for an
  application bundle called ``AcmeHelloBundle``, the prefix would be
  ``Acme\HelloBundle\Document``.

- ``alias`` Doctrine offers a way to alias document namespaces to simpler,
  shorter names to be used in queries or for Repository access.

- ``is_bundle`` This option is a derived value from ``dir`` and by default is
  set to true if dir is relative proved by a ``file_exists()`` check that
  returns false. It is false if the existence check returns true. In this case
  an absolute path was specified and the metadata files are most likely in a
  directory outside of a bundle.

To avoid having to configure lots of information for your mappings you should
follow these conventions:

1. Put all your documents in a directory ``Document/`` inside your bundle. For
   example ``Acme/HelloBundle/Document/``.

2. If you are using xml, yml or php mapping put all your configuration files
   into the ``Resources/config/doctrine/`` directory
   suffixed with mongodb.xml, mongodb.yml or mongodb.php respectively.

3. Annotations is assumed if a ``Document/`` but no
   ``Resources/config/doctrine/`` directory is found.

The following configuration shows a bunch of mapping examples:

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            document_managers:
                default:
                    mappings:
                        MyBundle1: ~
                        MyBundle2: yml
                        MyBundle3: { type: annotation, dir: Documents/ }
                        MyBundle4: { type: xml, dir: Resources/config/doctrine/mapping }
                        MyBundle5:
                            type: yml
                            dir: my-bundle-mappings-dir
                            alias: BundleAlias
                        doctrine_extensions:
                            type: xml
                            dir: "%kernel.root_dir%/../src/vendor/DoctrineExtensions/lib/DoctrineExtensions/Documents"
                            prefix: DoctrineExtensions\Documents\
                            alias: DExt

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:doctrine_mongodb="http://symfony.com/schema/dic/doctrine/odm/mongodb"
                   xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                        http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine_mongodb:config>
                <doctrine_mongodb:document-manager id="default">
                    <doctrine_mongodb:mapping name="MyBundle1" />
                    <doctrine_mongodb:mapping name="MyBundle2" type="yml" />
                    <doctrine_mongodb:mapping name="MyBundle3" type="annotation" dir="Documents/" />
                    <doctrine_mongodb:mapping name="MyNundle4" type="xml" dir="Resources/config/doctrine/mapping" />
                    <doctrine_mongodb:mapping name="MyBundle5" type="yml" dir="my-bundle-mappings-dir" alias="BundleAlias" />
                    <doctrine_mongodb:mapping name="doctrine_extensions"
                                              type="xml"
                                              dir="%kernel.root_dir%/../src/vendor/DoctrineExtensions/lib/DoctrineExtensions/Documents"
                                              prefix="DoctrineExtensions\Documents\"
                                              alias="DExt" />
                </doctrine_mongodb:document-manager>
            </doctrine_mongodb:config>
        </container>

Filters
~~~~~~~

Filter classes may be used in order to add criteria to ODM queries, regardless
of where those queries are created within your application. Typically, filters
will limit themselves to operating on a particular class or interface. Filters
may also take parameters, which can be used to customize the injected query
criteria.

Filters may be registered with a document manager by using the following syntax:

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            document_managers:
                default:
                    filters:
                        basic_filter:
                            class: Vendor\Filter\BasicFilter
                            enabled: true
                        complex_filter:
                            class: Vendor\Filter\ComplexFilter
                            enabled: false
                            parameters:
                                author: bob
                                comments: { $gte: 10 }
                                tags: { $in: [ 'foo', 'bar' ] }

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:doctrine="http://symfony.com/schema/dic/doctrine/odm/mongodb"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine:mongodb>
                <doctrine:connection id="default" server="mongodb://localhost:27017" />

                <doctrine:document-manager id="default" connection="default">
                    <doctrine:filter name="basic_filter" enabled="true" class="Vendor\Filter\BasicFilter" />
                    <doctrine:filter name="complex_filter" enabled="true" class="Vendor\Filter\ComplexFilter">
                        <doctrine:parameter name="author">bob</doctrine:parameter>
                        <doctrine:parameter name="comments">{ "$gte": 10 }</doctrine:parameter>
                        <doctrine:parameter name="tags">{ "$in": [ "foo", "bar" ] }</doctrine:parameter>
                    </doctrine:filter>
                </doctrine:document-manager>
            </doctrine:mongodb>
        </container>

.. note::

    Unlike ORM, query parameters in MongoDB ODM may be non-scalar values. Since
    such values are difficult to express in XML, the bundle allows JSON strings
    to be used in ``parameter`` tags. While processing the configuration, the
    bundle will run the tag contents through ``json_decode()`` if the string is
    wrapped in square brackets or curly braces for arrays and objects,
    respectively.

Multiple Connections
~~~~~~~~~~~~~~~~~~~~

If you need multiple connections and document managers you can use the
following syntax:

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            default_database: hello_%kernel.environment%
            default_connection: conn2
            default_document_manager: dm2
            metadata_cache_driver: apc
            connections:
                conn1:
                    server: mongodb://localhost:27017
                conn2:
                    server: mongodb://localhost:27017
            document_managers:
                dm1:
                    connection: conn1
                    metadata_cache_driver: xcache
                    mappings:
                        AcmeDemoBundle: ~
                dm2:
                    connection: conn2
                    mappings:
                        AcmeHelloBundle: ~

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:doctrine_mongodb="http://symfony.com/schema/dic/doctrine/odm/mongodb"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine_mongodb:config
                    default-database="hello_%kernel.environment%"
                    default-document-manager="dm2"
                    default-connection="dm2"
                    proxy-namespace="MongoDBODMProxies"
                    auto-generate-proxy-classes="true">
                <doctrine_mongodb:connection id="conn1" server="mongodb://localhost:27017">
                    <doctrine_mongodb:options>
                    </doctrine_mongodb:options>
                </doctrine_mongodb:connection>
                <doctrine_mongodb:connection id="conn2" server="mongodb://localhost:27017">
                    <doctrine_mongodb:options>
                    </doctrine_mongodb:options>
                </doctrine_mongodb:connection>
                <doctrine_mongodb:document-manager id="dm1" metadata-cache-driver="xcache" connection="conn1">
                    <doctrine_mongodb:mapping name="AcmeDemoBundle" />
                </doctrine_mongodb:document-manager>
                <doctrine_mongodb:document-manager id="dm2" connection="conn2">
                    <doctrine_mongodb:mapping name="AcmeHelloBundle" />
                </doctrine_mongodb:document-manager>
            </doctrine_mongodb:config>
        </container>

Now you can retrieve the configured services connection services::

    $conn1 = $container->get('doctrine_mongodb.odm.conn1_connection');
    $conn2 = $container->get('doctrine_mongodb.odm.conn2_connection');

And you can also retrieve the configured document manager services which utilize the above
connection services::

    $dm1 = $container->get('doctrine_mongodb.odm.dm1_document_manager');
    $dm2 = $container->get('doctrine_mongodb.odm.dm2_document_manager');

Connecting to a pool of mongodb servers on 1 connection
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

It is possible to connect to several mongodb servers on one connection if
you are using a replica set by listing all of the servers within the connection
string as a comma separated list.

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            # ...
            connections:
                default:
                    server: "mongodb://mongodb-01:27017,mongodb-02:27017,mongodb-03:27017"

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:doctrine="http://symfony.com/schema/dic/doctrine/odm/mongodb"
                   xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine:mongodb>
                <doctrine:connection id="default" server="mongodb://mongodb-01:27017,mongodb-02:27017,mongodb-03:27017" />
            </doctrine:mongodb>
        </container>

Where mongodb-01, mongodb-02 and mongodb-03 are the machine hostnames. You
can also use IP addresses if you prefer.

Using Authentication on a Database Level
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

MongoDB supports authentication and authorisation on a database-level. This is mandatory if you have
e.g. a publicly accessible MongoDB Server. To make use of this feature you need to configure credentials
for each of your connections. Also every connection needs a database set to authenticate against. The setting is
represented by the *authSource* `connection string <https://docs.mongodb.com/manual/reference/connection-string/#urioption.authSource>`_.
Otherwise you will get a *auth failed* exception.

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            # ...
            connections:
                default:
                    server: "mongodb://localhost:27017"
                    options:
                        username: someuser
                        password: somepass
                        authSource: db_you_have_access_to

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:doctrine="http://symfony.com/schema/dic/doctrine/odm/mongodb"
                   xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine:mongodb>
                <doctrine:connection id="default" server="mongodb://localhost:27017"/>
                    <doctrine:options
                            username="someuser"
                            password="somepass"
                            authSource="db_you_have_access_to"
                    >
                    </doctrine:options>
                </doctrine:connection>
            </doctrine:mongodb>
        </container>

Specifying a context service
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The MongoDB driver supports receiving a stream context to set SSL and logging
options. This can be used to authenticate using SSL certificates. To do so, create a service that creates your logging context:

.. configuration-block::

    .. code-block:: yaml

        services:
            # ...

            app.mongodb.context_service:
                class: 'resource'
                factory: 'stream_context_create'
                arguments:
                    - { ssl: { verify_expiry: true } }

Note: the ``class`` option is not used when creating the service, but has to be
provided for the service definition to be valid.

You can then use this service in your configuration:

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            # ...
            connections:
                default:
                    server: "mongodb://localhost:27017"
                    driver_options:
                        context: "app.mongodb.context_service"

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:doctrine="http://symfony.com/schema/dic/doctrine/odm/mongodb"
                   xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine:mongodb>
                <doctrine:connection id="default" server="mongodb://localhost:27017"/>
                    <doctrine:driver-options
                        context="app.mongodb.context_service"
                    >
                    </doctrine:options>
                </doctrine:connection>
            </doctrine:mongodb>
        </container>


Retrying Connections and Queries
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Doctrine MongoDB supports automatically retrying connections and queries after
encountering an exception, which is helpful when dealing with situations such as
replica set failovers. This alleviates much of the need to catch exceptions from
the MongoDB PHP driver in your application and manually retry operations.

You may specify the number of times to retry connections and queries via the
`retry_connect` and `retry_query` options in the document manager configuration.
These options default to zero, which means that no operations will be retried.

Full Default Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            document_managers:

                # Prototype
                id:
                    connection:                    ~
                    database:                      ~
                    default_repository_class:      Doctrine\ODM\MongoDB\DocumentRepository
                    repository_factory:            ~
                    persistent_collection_factory: ~
                    logging:                       true
                    auto_mapping:                  false
                    retry_connect:                 0
                    retry_query:                   0
                    metadata_cache_driver:
                        type:                 ~
                        class:                ~
                        host:                 ~
                        port:                 ~
                        instance_class:       ~
                    mappings:

                        # Prototype
                        name:
                            mapping:              true
                            type:                 ~
                            dir:                  ~
                            prefix:               ~
                            alias:                ~
                            is_bundle:            ~
            connections:

                # Prototype
                id:
                    server:               ~
                    options:
                        authMechanism:        ~
                        connect:              ~
                        connectTimeoutMS:     ~
                        db:                   ~
                        authSource:           ~
                        journal:              ~
                        password:             ~
                        readPreference:       ~
                        readPreferenceTags:   ~
                        replicaSet:           ~ # replica set name
                        socketTimeoutMS:      ~
                        ssl:                  ~
                        username:             ~
                        w:                    ~
                        wTimeoutMS:           ~
                    driver_options:
                        context:              ~ # stream context to use for connection

            proxy_namespace:      MongoDBODMProxies
            proxy_dir:            "%kernel.cache_dir%/doctrine/odm/mongodb/Proxies"
            auto_generate_proxy_classes:  0
            hydrator_namespace:   Hydrators
            hydrator_dir:         "%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators"
            auto_generate_hydrator_classes:  0
            persistent_collection_namespace: PersistentCollections
            persistent_collection_dir: "%kernel.cache_dir%/doctrine/odm/mongodb/PersistentCollections"
            auto_generate_persistent_collection_classes: 0
            default_document_manager:  ~
            default_connection:   ~
            default_database:     default
            fixture_loader:       Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:doctrine="http://symfony.com/schema/dic/doctrine/odm/mongodb"
                   xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb http://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine:config
                    auto-generate-hydrator-classes="0"
                    auto-generate-proxy-classes="0"
                    default-connection=""
                    default-database="default"
                    default-document-manager=""
                    hydrator-dir="%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators"
                    hydrator-namespace="Hydrators"
                    proxy-dir="%kernel.cache_dir%/doctrine/odm/mongodb/Proxies"
                    proxy-namespace="Proxies"
                    fixture-loader="Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader"
            >
                <doctrine:document-manager id="id"
                                           connection=""
                                           database=""
                                           default-repository-class=""
                                           repository-factory=""
                                           logging="true"
                                           auto-mapping="false"
                                           retry-connect="0"
                                           retry-query="0"
                >
                    <doctrine:metadata-cache-driver type="">
                        <doctrine:class></doctrine:class>
                        <doctrine:host></doctrine:host>
                        <doctrine:port></doctrine:port>
                        <doctrine:instance-class></doctrine:instance-class>
                    </doctrine:metadata-cache-driver>
                    <doctrine:mapping name="name"
                                      type=""
                                      dir=""
                                      prefix=""
                                      alias=""
                                      is-bundle=""
                    />
                    <doctrine:profiler enabled="true" pretty="false" />
                </doctrine:document-manager>
                <doctrine:connection id="conn1" server="mongodb://localhost">
                    <doctrine:options
                            authMechanism=""
                            connect=""
                            connectTimeoutMS=""
                            db=""
                            authSource=""
                            journal=""
                            password=""
                            readPreference=""
                            replicaSet=""
                            socketTimeoutMS=""
                            ssl=""
                            username=""
                            w=""
                            wTimeoutMS=""
                    >
                    </doctrine:options>
                </doctrine:connection>
            </doctrine:config>
        </container>

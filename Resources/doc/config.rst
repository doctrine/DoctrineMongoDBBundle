Configuration
=============

Sample Configuration
--------------------

.. configuration-block::

    .. code-block:: yaml

        # config/packages/doctrine_mongodb.yaml
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
                    metadata_cache_driver: array # array, apc, apcu, memcache, memcached, redis, wincache, zenddata, xcache

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:doctrine_mongodb="http://symfony.com/schema/dic/doctrine/odm/mongodb"
            xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

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
    `define it as an environment variable`_ and reference it in the bundle's config:

    .. code-block:: yaml

        # .env
        MONGODB_URL=mongodb://localhost:27017

    .. code-block:: yaml

        # config/packages/doctrine_mongodb.yaml
        doctrine_mongodb:
            connections:
                default:
                    server: '%env(resolve:MONGODB_URL)%'

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
            xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

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
---------------------

Explicit definition of all the mapped documents is the only necessary
configuration for the ODM and there are several configuration options that you
can control. The following configuration options exist for a mapping:

- ``type`` One of ``annotation``, ``attribute``, ``xml``, ``php`` or ``staticphp``.
  This specifies which type of metadata type your mapping uses.

- ``dir`` Path to the mapping or document files (depending on the driver). If
  this path is relative it is assumed to be relative to the bundle root. This
  only works if the name of your mapping is a bundle name. If you want to use
  this option to specify absolute paths you should prefix the path with the
  kernel parameters that exist in the DIC (for example ``%kernel.project_dir%``).

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

1. Put all your documents in a directory ``Document/`` inside your project. For
   example ``src/Document/``.

2. If you are using xml, yml or php mapping put all your configuration files
   into either the ``config/doctrine/`` directory (requires Symfony 5.4 or
   later) or the ``Resources/config/doctrine/`` directory
   suffixed with mongodb.xml, mongodb.yml or mongodb.php respectively.

3. Annotations are assumed if a ``Document/`` but no
   ``config/doctrine/`` or ``Resources/config/doctrine/`` directory is found.

The following configuration shows a bunch of mapping examples:

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            document_managers:
                default:
                    mappings:
                        MyBundle1: ~
                        MyBundle2: xml
                        MyBundle3: { type: attribute, dir: Documents/ }
                        MyBundle4: { type: annotation, dir: Documents/Legacy/ }
                        MyBundle5: { type: xml, dir: Resources/config/doctrine/mapping }
                        MyBundle6:
                            type: xml
                            dir: my-bundle-mappings-dir
                            alias: BundleAlias
                        doctrine_extensions:
                            type: xml
                            dir: "%kernel.project_dir%/src/vendor/DoctrineExtensions/lib/DoctrineExtensions/Documents"
                            prefix: DoctrineExtensions\Documents\
                            alias: DExt

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:doctrine_mongodb="http://symfony.com/schema/dic/doctrine/odm/mongodb"
                   xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                        http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine_mongodb:config>
                <doctrine_mongodb:document-manager id="default">
                    <doctrine_mongodb:mapping name="MyBundle1" />
                    <doctrine_mongodb:mapping name="MyBundle2" type="yml" />
                    <doctrine_mongodb:mapping name="MyBundle3" type="attribute" dir="Documents/" />
                    <doctrine_mongodb:mapping name="MyBundle4" type="annotation" dir="Documents/Legacy/" />
                    <doctrine_mongodb:mapping name="MyBundle5" type="xml" dir="Resources/config/doctrine/mapping" />
                    <doctrine_mongodb:mapping name="MyBundle6" type="xml" dir="my-bundle-mappings-dir" alias="BundleAlias" />
                    <doctrine_mongodb:mapping name="doctrine_extensions"
                                              type="xml"
                                              dir="%kernel.project_dir%/src/vendor/DoctrineExtensions/lib/DoctrineExtensions/Documents"
                                              prefix="DoctrineExtensions\Documents\"
                                              alias="DExt" />
                </doctrine_mongodb:document-manager>
            </doctrine_mongodb:config>
        </container>

Custom Types
------------

`Custom types`_ can come in handy when you're missing a specific mapping type
or when you want to replace the existing implementation of a mapping type for
your documents.

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            types:
                custom_type: Fully\Qualified\Class\Name

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:doctrine_mongodb="http://symfony.com/schema/dic/doctrine/odm/mongodb"
                   xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                        http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine_mongodb:config>
                <doctrine_mongodb:type name="custom_type" class="Fully\Qualified\Class\Name" />
            </doctrine_mongodb:config>
        </container>

Filters
-------

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
            xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

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
--------------------

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
                    database: db1
                    metadata_cache_driver: xcache
                    mappings:
                        AcmeDemoBundle: ~
                dm2:
                    connection: conn2
                    database: db2
                    mappings:
                        AcmeHelloBundle: ~

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:doctrine_mongodb="http://symfony.com/schema/dic/doctrine/odm/mongodb"
            xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

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
                <doctrine_mongodb:document-manager id="dm1" metadata-cache-driver="xcache" connection="conn1" database="db1">
                    <doctrine_mongodb:mapping name="AcmeDemoBundle" />
                </doctrine_mongodb:document-manager>
                <doctrine_mongodb:document-manager id="dm2" connection="conn2" database="db2">
                    <doctrine_mongodb:mapping name="AcmeHelloBundle" />
                </doctrine_mongodb:document-manager>
            </doctrine_mongodb:config>
        </container>

Now you can retrieve the configured services connection services:

.. code-block:: php

    $conn1 = $container->get('doctrine_mongodb.odm.conn1_connection');
    $conn2 = $container->get('doctrine_mongodb.odm.conn2_connection');

And you can also retrieve the configured document manager services which utilize the above
connection services:

.. code-block:: php

    $dm1 = $container->get('doctrine_mongodb.odm.dm1_document_manager');
    $dm2 = $container->get('doctrine_mongodb.odm.dm2_document_manager');

Connecting to a pool of mongodb servers on 1 connection
-------------------------------------------------------

It is possible to connect to several mongodb servers on one connection if
you are using a replica set by listing all of the servers within the connection
string as a comma separated list and using ``replicaSet`` option.

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            # ...
            connections:
                default:
                    server: "mongodb://mongodb-01:27017,mongodb-02:27017,mongodb-03:27017/?replicaSet=replSetName"

    .. code-block:: xml

        <?xml version="1.0" ?>

        <container xmlns="http://symfony.com/schema/dic/services"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:doctrine="http://symfony.com/schema/dic/doctrine/odm/mongodb"
                   xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine:mongodb>
                <doctrine:connection id="default" server="mongodb://mongodb-01:27017,mongodb-02:27017,mongodb-03:27017/?replicaSet=replSetName" />
            </doctrine:mongodb>
        </container>

Where mongodb-01, mongodb-02 and mongodb-03 are the machine hostnames. You
can also use IP addresses if you prefer.

.. tip::

    Please refer to `Replica Sets`_ manual of MongoDB PHP Driver for futher details.


Using Authentication on a Database Level
----------------------------------------

MongoDB supports authentication and authorisation on a database-level. This is mandatory if you have
e.g. a publicly accessible MongoDB Server. To make use of this feature you need to configure credentials
for each of your connections. Every connection needs also a database to authenticate against. The setting is
represented by the *authSource* `connection string`_.
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
                   xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine:mongodb>
                <doctrine:connection id="default" server="mongodb://localhost:27017" />
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
options. This can be used to authenticate using SSL certificates. To do so,
create a service that creates your logging context:

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
                   xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

            <doctrine:mongodb>
                <doctrine:connection id="default" server="mongodb://localhost:27017" />
                    <doctrine:driver-options
                        context="app.mongodb.context_service"
                    >
                    </doctrine:options>
                </doctrine:connection>
            </doctrine:mongodb>
        </container>

Full Default Configuration
--------------------------

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            document_managers:

                # Prototype
                id:
                    connection:                        ~
                    database:                          ~
                    default_document_repository_class: Doctrine\ODM\MongoDB\Repository\DocumentRepository
                    default_gridfs_repository_class:   Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository
                    repository_factory:                ~
                    persistent_collection_factory:     ~
                    logging:                           true
                    auto_mapping:                      false
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
            types:

                # Prototype
                custom_type: Fully\Qualified\Class\Name
            connections:

                # Prototype
                id:
                    server:               ~
                    options:
                        authMechanism:                          ~
                        connectTimeoutMS:                       ~
                        db:                                     ~
                        authSource:                             ~
                        journal:                                ~
                        password:                               ~
                        readPreference:                         ~
                        readPreferenceTags:                     ~
                        replicaSet:                             ~ # replica set name
                        socketTimeoutMS:                        ~
                        ssl:                                    ~
                        tls:                                    ~
                        tlsAllowInvalidCertificates:            ~
                        tlsAllowInvalidHostnames:               ~
                        tlsCAFile:                              ~
                        tlsCertificateKeyFile:                  ~
                        tlsCertificateKeyFilePassword:          ~
                        tlsDisableCertificateRevocationCheck:   ~
                        tlsDisableOCSPEndpointCheck:            ~
                        tlsInsecure:                            ~
                        username:                               ~
                        retryReads:                             ~
                        retryWrites:                            ~
                        w:                                      ~
                        wTimeoutMS:                             ~
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
                   xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine/odm/mongodb https://symfony.com/schema/dic/doctrine/odm/mongodb/mongodb-1.0.xsd">

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
                                           default-document-repository-class=""
                                           default-gridfs-repository-class=""
                                           repository-factory=""
                                           logging="true"
                                           auto-mapping="false"
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
                <doctrine:type name="custom_type" class="Fully\Qualified\Class\Name" />
                <doctrine:connection id="conn1" server="mongodb://localhost">
                    <doctrine:options
                            authMechanism=""
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

.. _`Custom types`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/current/reference/custom-mapping-types.html
.. _`define it as an environment variable`: https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables
.. _`connection string`: https://docs.mongodb.com/manual/reference/connection-string/#urioption.authSource
.. _`Replica Sets`: https://www.php.net/manual/en/mongo.connecting.rs.php

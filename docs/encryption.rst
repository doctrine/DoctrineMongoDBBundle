Client-Side Field-Level Encryption (CSFLE) and Queryable Encryption (QE)
============================================================

This page documents how to configure and use MongoDB Client-Side Field-Level Encryption (CSFLE) and Queryable Encryption (QE) in DoctrineMongoDBBundle.

.. note::

    CSFLE and QE are advanced MongoDB features that allow you to encrypt specific fields in your documents, with optional support for searching encrypted data (Queryable Encryption).

Configuration
-------------

.. tip::

    For a general overview of configuration options, see :doc:`config`.

To enable CSFLE or QE, you need to configure the ``autoEncryption`` option under
your connection's configuration. At a minimum, you must specify the ``kmsProvider``
and the ``masterKey`` for KMS provider other than "local".
Additional options are available for advanced use cases.

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            connections:
                default:
                    server: "mongodb://localhost:27017"
                    autoEncryption:
                        kmsProvider:
                            local:
                                key: "YOUR_BASE64_KEY"
                        # Optional: see below for more options

    .. code-block:: php

        use Symfony\Config\DoctrineMongodbConfig;

        return static function (DoctrineMongodbConfig $config): void {
            $config->connection('default')
                ->server('mongodb://localhost:27017')
                ->autoEncryption([
                    'kmsProvider' => [
                        'type' => 'local',
                        'key' => 'YOUR_BASE64_KEY',
                    ],
                    // ... other options ...
                ]);
        };

Supported KMS Providers
-----------------------

The ``kmsProvider`` option specifies a single KMS provider that will be used for encryption.
The type of KMS provider is specified with the ``type`` property along with its options.

The configuration for each KMS provider varies and is described in the
`MongoDB Manager constructor documentation <https://www.php.net/manual/en/mongodb-driver-manager.construct.php>`.

Example of configuration for AWS


.. code-block:: yaml

    doctrine_mongodb:
        connections:
            default:
                autoEncryption:
                    kmsProvider:
                        type: aws
                        accessKeyId: YOUR_AWS_ACCESS_KEY_ID
                        secretAccessKey: YOUR_AWS_SECRET_ACCESS_KEY
                    masterKey:
                        region: "eu-west-1"
                        key: "arn:aws:kms:eu-west-1:123456789012:key/abcd1234-12ab-34cd-56ef-1234567890ab"


Queryable Encryption (QE)
-------------------------

Queryable Encryption (QE) allows you to run queries on encrypted fields. To use QE, you may need to provide an ``encryptedFieldsMap`` or use a schema map, depending on your driver and use case.

.. tabs::

    .. group-tab:: YAML

        .. code-block:: yaml

            doctrine_mongodb:
                connections:
                    default:
                        autoEncryption:
                            encryptedFieldsMap:
                                "mydatabase.mycollection":
                                    fields:
                                        - path: "sensitive_field"
                                          bsonType: "string"

    .. group-tab:: XML

        .. code-block:: xml

            <doctrine:connection>
                <doctrine:autoEncryption>
                    <doctrine:encryptedFieldsMap>
                        <doctrine:encryptedFields name="mydatabase.mycollection">
                            <doctrine:field path="sensitive_field" bsonType="string" />
                        </doctrine:encryptedFields>
                    </doctrine:encryptedFieldsMap>
                </doctrine:autoEncryption>
            </doctrine:connection>

    .. group-tab:: PHP

        .. code-block:: php

            use Symfony\Config\DoctrineMongodbConfig;

            return static function (DoctrineMongodbConfig $config): void {
                $config->connection('default')
                    ->autoEncryption([
                        'encryptedFieldsMap' => [
                            'mydatabase.mycollection' => [
                                'fields' => [
                                    [
                                        'path' => 'sensitive_field',
                                        'bsonType' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ]);
            };

TLS Options
-----------

If you are not specifying a custom ``keyVaultClient`` service, you can configure TLS settings for the internal key vault client using the ``tlsOptions`` key:

.. tabs::

    .. group-tab:: YAML

        .. code-block:: yaml

            doctrine_mongodb:
                connections:
                    default:
                        autoEncryption:
                            tlsOptions:
                                tlsCAFile: "/path/to/key-vault-ca.pem"
                                tlsCertificateKeyFile: "/path/to/key-vault-client.pem"
                                tlsCertificateKeyFilePassword: "keyvaultclientpassword"
                                tlsAllowInvalidCertificates: false
                                tlsAllowInvalidHostnames: false

    .. group-tab:: XML

        .. code-block:: xml

            <doctrine:connection>
                <doctrine:autoEncryption>
                    <doctrine:tlsOptions>
                        <doctrine:tlsCAFile>/path/to/key-vault-ca.pem</doctrine:tlsCAFile>
                        <doctrine:tlsCertificateKeyFile>/path/to/key-vault-client.pem</doctrine:tlsCertificateKeyFile>
                        <doctrine:tlsCertificateKeyFilePassword>keyvaultclientpassword</doctrine:tlsCertificateKeyFilePassword>
                        <doctrine:tlsAllowInvalidCertificates>false</doctrine:tlsAllowInvalidCertificates>
                        <doctrine:tlsAllowInvalidHostnames>false</doctrine:tlsAllowInvalidHostnames>
                    </doctrine:tlsOptions>
                </doctrine:autoEncryption>
            </doctrine:connection>

    .. group-tab:: PHP

        .. code-block:: php

            use Symfony\Config\DoctrineMongodbConfig;

            return static function (DoctrineMongodbConfig $config): void {
                $config->connection('default')
                    ->autoEncryption([
                        'tlsOptions' => [
                            'tlsCAFile' => '/path/to/key-vault-ca.pem',
                            'tlsCertificateKeyFile' => '/path/to/key-vault-client.pem',
                            'tlsCertificateKeyFilePassword' => 'keyvaultclientpassword',
                            'tlsAllowInvalidCertificates' => false,
                            'tlsAllowInvalidHostnames' => false,
                        ],
                    ]);
            };

Context Service for SSL
-----------------------

You can use a Symfony service to provide a stream context for SSL options:

.. code-block:: yaml

    services:
        app.mongodb.context_service:
            class: 'resource'
            factory: 'stream_context_create'
            arguments:
                - { ssl: { verify_expiry: true } }

Then reference this service in your connection configuration:

.. code-block:: yaml

    doctrine_mongodb:
        connections:
            default:
                server: "mongodb://localhost:27017"
                driver_options:
                    context: "app.mongodb.context_service"

Further Reading
---------------

- `MongoDB CSFLE documentation <https://www.mongodb.com/docs/manual/core/csfle/>`_
- `MongoDB PHP driver Manager::__construct <https://www.php.net/manual/en/mongodb-driver-manager.construct.php>`_
- :doc:`config`

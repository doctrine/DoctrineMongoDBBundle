Client-Side Field-Level Encryption (CSFLE) and Queryable Encryption (QE)
============================================================

This page documents how to configure and use MongoDB Client-Side Field-Level Encryption (CSFLE) and Queryable Encryption (QE) in DoctrineMongoDBBundle.

.. note::

    CSFLE and QE are advanced MongoDB features that allow you to encrypt specific fields in your documents, with optional support for searching encrypted data (Queryable Encryption).

Configuration
-------------

.. tip::

    For a general overview of configuration options, see :doc:`config`.

To enable CSFLE or QE, you need to configure the ``autoEncryption`` driver option under your connection's ``driver_options``. At a minimum, you must specify the ``keyVaultNamespace`` and ``kmsProviders``. Additional options are available for advanced use cases.

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            connections:
                default:
                    server: "mongodb://localhost:27017"
                    driver_options:
                        autoEncryption:
                            keyVaultNamespace: "encryption.__keyVault"
                            kmsProviders:
                                local:
                                    key: "YOUR_BASE64_KEY"
                            # Optional: see below for more options

    .. code-block:: php

        use Symfony\Config\DoctrineMongodbConfig;

        return static function (DoctrineMongodbConfig $config): void {
            $config->connection('default')
                ->server('mongodb://localhost:27017')
                ->driverOptions([
                    'autoEncryption' => [
                        'keyVaultNamespace' => 'encryption.__keyVault',
                        'kmsProviders' => [
                            'local' => [
                                'key' => 'YOUR_BASE64_KEY',
                            ],
                        ],
                        // ... other options ...
                    ],
                ]);
        };

Supported KMS Providers
-----------------------

The ``kmsProviders`` option is a map where each key specifies a KMS provider type (e.g., ``aws``, ``azure``, ``gcp``, ``kmip``, ``local``) and the value is a map of options for that provider.

See below for examples for each provider:

- **AWS**
- **Azure**
- **GCP**
- **KMIP**
- **Local**

.. code-block:: yaml

    doctrine_mongodb:
        connections:
            default:
                driver_options:
                    autoEncryption:
                        kmsProviders:
                            aws:
                                accessKeyId: YOUR_AWS_ACCESS_KEY_ID
                                secretAccessKey: YOUR_AWS_SECRET_ACCESS_KEY
                            azure:
                                tenantId: YOUR_AZURE_TENANT_ID
                                clientId: YOUR_AZURE_CLIENT_ID
                                clientSecret: YOUR_AZURE_CLIENT_SECRET
                                keyVaultEndpoint: https://yourkeyvault.vault.azure.net/
                            gcp:
                                email: your-gcp-service-account-email
                                privateKey: "-----BEGIN PRIVATE KEY-----\nYOUR_PRIVATE_KEY\n-----END PRIVATE KEY-----"
                                projectId: your-gcp-project-id
                                location: your-gcp-location
                                keyRing: your-key-ring
                                keyName: your-key-name
                            kmip:
                                endpoint: kmip.example.com:5696
                            local:
                                key: BASE64_ENCODED_96_BYTE_MASTER_KEY_STRING

Queryable Encryption (QE)
-------------------------

Queryable Encryption (QE) allows you to run queries on encrypted fields. To use QE, you may need to provide an ``encryptedFieldsMap`` or use a schema map, depending on your driver and use case.

.. tabs::

    .. group-tab:: YAML

        .. code-block:: yaml

            doctrine_mongodb:
                connections:
                    default:
                        driver_options:
                            autoEncryption:
                                encryptedFieldsMap:
                                    "mydatabase.mycollection":
                                        fields:
                                            - path: "sensitive_field"
                                              keyId: "/dataKeyId"
                                              bsonType: "string"

    .. group-tab:: XML

        .. code-block:: xml

            <doctrine:connection>
                <doctrine:driver-options>
                    <doctrine:autoEncryption>
                        <doctrine:encryptedFieldsMap>
                            <doctrine:collection name="mydatabase.mycollection">
                                <doctrine:field path="sensitive_field" keyId="/dataKeyId" bsonType="string" />
                            </doctrine:collection>
                        </doctrine:encryptedFieldsMap>
                    </doctrine:autoEncryption>
                </doctrine:driver-options>
            </doctrine:connection>

    .. group-tab:: PHP

        .. code-block:: php

            use Symfony\Config\DoctrineMongodbConfig;

            return static function (DoctrineMongodbConfig $config): void {
                $config->connection('default')
                    ->driverOptions([
                        'autoEncryption' => [
                            'encryptedFieldsMap' => [
                                'mydatabase.mycollection' => [
                                    'fields' => [
                                        [
                                            'path' => 'sensitive_field',
                                            'keyId' => '/dataKeyId',
                                            'bsonType' => 'string',
                                        ],
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
                        driver_options:
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
                <doctrine:driver-options>
                    <doctrine:autoEncryption>
                        <doctrine:tlsOptions>
                            <doctrine:tlsCAFile>/path/to/key-vault-ca.pem</doctrine:tlsCAFile>
                            <doctrine:tlsCertificateKeyFile>/path/to/key-vault-client.pem</doctrine:tlsCertificateKeyFile>
                            <doctrine:tlsCertificateKeyFilePassword>keyvaultclientpassword</doctrine:tlsCertificateKeyFilePassword>
                            <doctrine:tlsAllowInvalidCertificates>false</doctrine:tlsAllowInvalidCertificates>
                            <doctrine:tlsAllowInvalidHostnames>false</doctrine:tlsAllowInvalidHostnames>
                        </doctrine:tlsOptions>
                    </doctrine:autoEncryption>
                </doctrine:driver-options>
            </doctrine:connection>

    .. group-tab:: PHP

        .. code-block:: php

            use Symfony\Config\DoctrineMongodbConfig;

            return static function (DoctrineMongodbConfig $config): void {
                $config->connection('default')
                    ->driverOptions([
                        'autoEncryption' => [
                            'tlsOptions' => [
                                'tlsCAFile' => '/path/to/key-vault-ca.pem',
                                'tlsCertificateKeyFile' => '/path/to/key-vault-client.pem',
                                'tlsCertificateKeyFilePassword' => 'keyvaultclientpassword',
                                'tlsAllowInvalidCertificates' => false,
                                'tlsAllowInvalidHostnames' => false,
                            ],
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
- `MongoDB PHP driver ClientEncryption::__construct <https://www.php.net/mongodb-driver-clientencryption.construct>`_
- :doc:`config`

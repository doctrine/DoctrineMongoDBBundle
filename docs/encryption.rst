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
                            type: local
                            key: "YOUR_BASE64_KEY"
                        # See below for more optional configuration

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
                    // See below for more optional configuration
                ]);
        };

Supported KMS Providers
-----------------------

The ``kmsProvider`` option specifies a single `KMS provider`_ that will be used for encryption.
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


Encrypted Fields Configuration
------------------------------

The encrypted fields are configured per document class using the ``#[Encrypt]``
attribute or the equivalent XML mapping.

.. code-block:: php

    use Doctrine\ODM\MongoDB\Mapping\Annotations\Encrypt;
    use Doctrine\ODM\MongoDB\Mapping\Annotations\EncryptQuery;
    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

    #[ODM\Document]
    class User
    {
        #[ODM\Id]
        private $id;

        #[Encrypt(queryType: EncryptQuery::Equality)]
        #[ODM\Field(type: 'string')]
        private $sensitiveField;

        // ...
    }

Read more about it in the `MongoDB ODM documentation on Queryable Encryption`_

Encrypted Fields Map (optional)
-------------------------------

The encrypted fields are set to the collection when you create it, and the MongoDB
client will query the server for the collection schema before performing any
operations. For **additional security**, you **can** also specify the encrypted fields
in the connection configuration, which allows the client to use local rules
instead of downloading the remote schema from the server, that could potentially
be tampered with if an attacker compromises the server. Read more about it in the
`Security Considerations`_.

The Encrypted Fields Map is a list of all encrypted fields associated with all
the collection namespaces that has encryption enabled. To configure it, you
can run a command that extract the encrypted fields from the server and generate
the ``encryptedFieldsMap`` configuration.

.. code-block:: console

    php bin/console doctrine:mongodb:encryption:dump-fields-map --format yaml

The output of the command will be a YAML configuration for the
``autoEncryption.encryptedFieldsMap`` option in the connection configuration.

- If the connection ``encryptedFieldsMap`` object contains a key for the specified
  collection namespace, the client uses that object to perform automatic
  Queryable Encryption, rather than using the remote schema. At minimum, the
  local rules must encrypt all fields that the remote schema does.

- If the connection ``encryptedFieldsMap`` object doesn't contain a key for the
  specified collection namespace, the client downloads the server-side remote
  schema for the collection and uses it instead.

For more details, see the official MongoDB documentation:
`Encrypted Fields and Enabled Queries`_.

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            connections:
                default:
                    autoEncryption:
                        encryptedFieldsMap:
                            "app.users":
                                fields:
                                    - keyId: { $binary: { base64: 2CSosXLSTEKaYphcSnUuCw==, subType: '04' } }
                                      path: "sensitive_field"
                                      bsonType: "string"

    .. code-block:: xml

        <doctrine:connection>
            <doctrine:autoEncryption>
                <doctrine:encryptedFieldsMap>
                    <!-- Use JSON in a CDATA to avoid XML escaping issues -->
                    <![CDATA[
                        {
                            "app.users": {
                                fields: [
                                    "keyId": { "$binary": { "base64": "2CSosXLSTEKaYphcSnUuCw==", "subType": "04" } },
                                    "path": "sensitive_field",
                                    "bsonType": "string"
                                ]
                            }
                        }
                    ]]>
                </doctrine:encryptedFieldsMap>
            </doctrine:autoEncryption>
        </doctrine:connection>

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
                                    // Extended JSON representation of a BSON binary type
                                    // The MongoDB\BSON\Binary class cannot be used here
                                    'keyId' => ['$binary' => ['base64' => '2CSosXLSTEKaYphcSnUuCw==', 'subType' => '04' ] ],
                                    'bsonType' => 'string',
                                ],
                            ],
                        ],
                    ],
                ]);
        };

Automatic Encryption Shared Library
-----------------------------------

To use automatic encryption, the MongoDB PHP driver requires the `Automatic Encryption Shared Library`_.

If the driver is not able to find the library, you can specify its path using the ``cryptSharedLibPath`` extra option in your connection configuration.

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            connections:
                default:
                    autoEncryption:
                        extraOptions:
                            cryptSharedLibPath: '%kernel.project_dir%/bin/mongo_crypt_v1.so'

    .. code-block:: xml

        <doctrine:connection>
            <doctrine:autoEncryption>
                <doctrine:extraOptions cryptSharedLibPath="%kernel.project_dir%/bin/mongo_crypt_v1.so" />
            </doctrine:autoEncryption>
        </doctrine:connection>

    .. code-block:: php

        use Symfony\Config\DoctrineMongodbConfig;

        return static function (DoctrineMongodbConfig $config): void {
            $config->connection('default')
                ->autoEncryption([
                    'extraOptions' => [
                        'cryptSharedLibPath' => '%kernel.project_dir%/bin/mongo_crypt_v1.so',
                    ],
                ]);
        };

TLS Options
-----------

If you are not specifying a custom ``keyVaultClient`` service, you can configure
TLS settings for the internal key vault client using the ``tlsOptions`` key:

.. configuration-block::

    .. code-block:: yaml

        doctrine_mongodb:
            connections:
                default:
                    autoEncryption:
                        tlsOptions:
                            tlsCAFile: "/path/to/key-vault-ca.pem"
                            tlsCertificateKeyFile: "/path/to/key-vault-client.pem"
                            tlsCertificateKeyFilePassword: "keyvaultclientpassword"
                            tlsDisableOCSPEndpointCheck: false

    .. code-block:: xml

        <doctrine:connection>
            <doctrine:autoEncryption>
                <doctrine:tlsOptions
                    tlsCAFile="/path/to/key-vault-ca.pem"
                    tlsCertificateKeyFile="/path/to/key-vault-client.pem"
                    tlsCertificateKeyFilePassword="keyvaultclientpassword"
                    tlsDisableOCSPEndpointCheck="false"
                />
            </doctrine:autoEncryption>
        </doctrine:connection>

    .. code-block:: php

        use Symfony\Config\DoctrineMongodbConfig;

        return static function (DoctrineMongodbConfig $config): void {
            $config->connection('default')
                ->autoEncryption([
                    'tlsOptions' => [
                        'tlsCAFile' => '/path/to/key-vault-ca.pem',
                        'tlsCertificateKeyFile' => '/path/to/key-vault-client.pem',
                        'tlsCertificateKeyFilePassword' => 'keyvaultclientpassword',
                        'tlsDisableOCSPEndpointCheck' => false,
                    ],
                ]);
        };


Further Reading
---------------

- `MongoDB ODM documentation on Queryable Encryption`_
- `MongoDB CSFLE documentation <https://www.mongodb.com/docs/manual/core/csfle/>`_
- `MongoDB PHP driver Manager::__construct <https://www.php.net/manual/en/mongodb-driver-manager.construct.php>`_
- :doc:`config`

.. _`KMS provider`: https://www.mongodb.com/docs/manual/core/queryable-encryption/fundamentals/kms-providers/
.. _`Security Considerations`: https://www.mongodb.com/docs/manual/core/queryable-encryption/about-qe-csfle/#std-label-qe-csfle-security-considerations
.. _`Encrypted Fields and Enabled Queries`: https://www.mongodb.com/docs/manual/core/queryable-encryption/fundamentals/encrypt-and-query/
.. _`Automatic Encryption Shared Library`: https://www.mongodb.com/docs/manual/core/queryable-encryption/install-library/
.. _`MongoDB ODM documentation on Queryable Encryption`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/cookbook/queryable-encryption.html

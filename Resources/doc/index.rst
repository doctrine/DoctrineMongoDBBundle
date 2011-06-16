MongoDB Acl Provider
====================

To use the MongoDB Acl Provider, the minimal configuration is adding acl_provider to the MongoDb config in config.yml

     # app/config/config.yml
    doctrine_mongodb:
        acl_provider: ~

The next requirement is to add the provider to the security configuration

    # app/config/security.yml
    services:
        mongodb_acl_provider:
            parent: doctrine.odm.mongodb.security.acl.provider

    security:
        acl:
            provider: mongodb_acl_provider



The full acl provider configuration options are listed below::

    # app/config/config.yml
    doctrine_mongodb:
        acl_provider:
            default_database: ~
            collections:
                entry: ~
                object_identity: ~

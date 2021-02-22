SecurityBundle integration
==========================

A user provider is available for your MongoDB projects if you use
Symfony `SecurityBundle`_. It works exactly the same way as
the ``entity`` user provider described in `the documentation`_:

.. configuration-block::

    .. code-block:: yaml

        # config/packages/security.yaml
        security:
            providers:
                my_mongo_provider:
                    mongodb: {class: App\Document\User, property: username}

    .. code-block:: xml

        <!-- config/packages/security.xml -->
        <config>
            <provider name="my_mongo_provider">
                <mongodb class="App\Document\User" property="username" />
            </provider>
        </config>

.. _`SecurityBundle`: https://symfony.com/doc/current/security.html
.. _`the documentation`: https://symfony.com/doc/current/security/user_provider.html

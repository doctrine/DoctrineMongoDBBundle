SecurityBundle integration
==========================

A user provider is available for your MongoDB projects, working exactly the
same as the ``entity`` provider described in `the cookbook`_:

.. configuration-block::

    .. code-block:: yaml

        security:
            providers:
                my_mongo_provider:
                    mongodb: {class: Acme\DemoBundle\Document\User, property: username}

    .. code-block:: xml

        <!-- app/config/security.xml -->
        <config>
            <provider name="my_mongo_provider">
                <mongodb class="Acme\DemoBundle\Document\User" property="username" />
            </provider>
        </config>

.. _`the cookbook`: http://symfony.com/doc/current/cookbook/security/entity_provider.html

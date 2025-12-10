Registering Custom Field Types
==============================

Sometimes, the built-in field types provided by Doctrine ODM are not sufficient
for your application. In such cases, you can create `Custom Mapping Types`_ to handle
specific data formats or structures.

Enable the Type Registry
------------------------

The ``TypeRegistry`` is responsible for managing custom field types in Doctrine
ODM. To use it, you need to enable it in the configuration:

.. code-block:: yaml

    doctrine_mongodb:
        type_registry: true

Creating a Custom Field Type
----------------------------

In order to create a custom field type, you need to extend the
``Doctrine\ODM\MongoDB\Types\Type`` class and implement the required methods.

Mapping a Money Value Object
----------------------------

You can create a custom mapping type for your own value objects or classes. For
example, to map a ``Money`` value object using the `moneyphp/money library`_, you can
implement a type that converts between this class and a BSON embedded document format.

This approach works for any custom class by adapting the conversion logic to your needs.

Example Implementation (using ``Money\Money``):

.. code-block:: php

    <?php

    namespace App\MongoDB\Types;

    use Doctrine\Bundle\MongoDBBundle\Attribute\AsFieldType;
    use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
    use Doctrine\ODM\MongoDB\Types\Type;
    use InvalidArgumentException;
    use Money\Money;
    use Money\Currency;

    #[AsFieldType(Money::class)]
    final class MoneyType extends Type
    {
        // This trait provides a default closureToPHP() used to generate data hydratation classes
        use ClosureToPHP;

        public function convertToPHPValue(mixed $value): ?Money
        {
            if (null === $value) {
                return null;
            }

            if (is_array($value) && isset($value['amount'], $value['currency'])) {
                return new Money($value['amount'], new Currency($value['currency']));
            }

            throw new InvalidArgumentException(sprintf('Could not convert database value from "%s" to %s', get_debug_type($value), Money::class));
        }

        public function convertToDatabaseValue(mixed $value): ?array
        {
            if (null === $value) {
                return null;
            }

            if ($value instanceof Money) {
                return [
                    'amount' => $value->getAmount(),
                    'currency' => $value->getCurrency()->getCode(),
                ];
            }

            throw new InvalidArgumentException(sprintf('Could not convert database value from "%s" to array', get_debug_type($value)));
        }
    }

If you use multiple document managers, you can specify the document manager
where the type should be registered by passing the ``objectManager`` parameter.

.. code-block:: php

    #[AsFieldType(Money::class, objectManager: 'customer_manager')]
    final class MoneyType extends Type
    {
        // ...
    }

If the autoconfiguration of the services is not enabled on your project, or
if you build a reusable bundle with custom types, you must add the
``doctrine_mongodb.odm.field_type`` tag to your type service definition::

.. code-block:: yaml

    services:
        App\MongoDB\Types\MoneyType:
            tags:
                - name: doctrine_mongodb.odm.field_type
                  type: Money\Money
                  object_manager: customer_manager

Use the Custom Field Type
-------------------------

Once the custom field type is created and registered, you can use it in your
document mappings.

.. code-block:: php

    use Money\Money;
    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

    #[ODM\Document]
    class Product
    {
        #[ODM\Id]
        public ?string $id = null;

        #[ODM\Field]
        public Money $price;
    }

.. _`Custom Mapping Types`: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/current/reference/custom-mapping-types.html
.. _`moneyphp/money library`: https://github.com/moneyphp/money

Registering Custom Field Types
==============================

Sometimes, the built-in field types are not enough for your application. You can
create `Custom Mapping Types`_ to handle specific data formats or structures.

Enable the Type Registry
------------------------

The ``TypeRegistry`` is responsible for managing field types in Doctrine MongoDB
ODM. By default, a global type registry is shared across all document managers
for backward compatibility, which keeps projects that register their custom
types through the global ``Type::register()`` working unchanged. When you define
a custom type as a service, either by tagging the service or with the
``#[AsFieldType]`` attribute, a scoped type registry is created for each document
manager and the tagged services are injected into it: from then on, that document
manager stops using the global registry. Doing so requires Doctrine MongoDB ODM
2.18 or higher.

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

    namespace App\MongoDB\Types;

    use Doctrine\Bundle\MongoDBBundle\Attribute\AsFieldType;
    use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
    use Doctrine\ODM\MongoDB\Types\Type;
    use InvalidArgumentException;
    use Money\Currency;
    use Money\Money;

    #[AsFieldType(Money::class)]
    final class MoneyType extends Type
    {
        // This trait provides a default closureToPHP() method used during data hydration
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
where the type should be registered by passing the ``documentManager`` parameter.

.. code-block:: php

    #[AsFieldType(Money::class, documentManager: 'customer_manager')]
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
                  document_manager: customer_manager

Use the Custom Field Type
-------------------------

Once the custom field type is created and registered, you can use it in your
document mappings.

.. code-block:: php

    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
    use Money\Money;

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

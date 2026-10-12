Document Value Resolver Expressions
===================================

.. note::

    Registering expression language providers for the document value resolver, and
    the security functions described below, are new in DoctrineMongoDBBundle 5.7.

The ``#[MapDocument]`` attribute can fetch a document through an expression,
evaluated with the ExpressionLanguage component. Besides the request attributes and
the ``repository`` variable provided by Symfony, the bundle registers the security
functions ``current_user()``, ``is_granted()``, ``is_authenticated()``,
``is_fully_authenticated()`` and ``is_remember_me()``. ``current_user()`` returns the
currently authenticated user, or ``null`` when there is none:

.. code-block:: php

    // VisitController.php
    use App\Document\Visit;
    use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;

    class VisitController
    {
        public function index(
            #[MapDocument(class: Visit::class, expr: 'repository.findLatest(current_user())')]
            iterable $visits,
        ) {
            // ...
        }
    }

The functions come from the ``security.expression_language_provider`` service of
SecurityBundle, so they require Symfony 8.2 or higher, and they are only registered
when the Security component is in use. They read the security context of the current
request: in a console command there is no such context, so evaluating them throws a
``LogicException`` rather than reporting that nobody is authenticated.

Registering your own functions
------------------------------

Tag a service implementing ``Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface``
with ``doctrine_mongodb.odm.document_value_resolver.expression_language_provider`` to add
your own functions to those expressions:

.. code-block:: php

    // ExpressionLanguageProvider.php
    use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
    use Symfony\Component\ExpressionLanguage\ExpressionFunction;
    use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

    #[AutoconfigureTag('doctrine_mongodb.odm.document_value_resolver.expression_language_provider')]
    class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
    {
        public function getFunctions(): array
        {
            return [
                new ExpressionFunction(
                    'current_locale',
                    static fn () => throw new \LogicException('The "current_locale" function cannot be compiled.'),
                    fn (array $variables) => $variables['request']->getLocale(),
                ),
            ];
        }
    }

The function is then available in any ``#[MapDocument]`` expression:

.. code-block:: php

    #[MapDocument(class: Article::class, expr: 'repository.findTranslated(current_locale())')]

With Symfony 8.2 and higher, the expressions of ``#[MapDocument]`` are compiled while
the cache is warmed up. A function whose compiler throws is then called through its
evaluator instead, so declaring a throwing compiler is safe: it only keeps the
expression from being fully compiled.

Console commands
----------------

The console value resolver shares the same expression language service, so the
providers registered with the tag above are also available in the ``#[MapDocument]``
expressions of console command arguments. The variables differ from the controller
resolver: a command has no HTTP request, so the expression receives the command
arguments and options, an ``input`` variable holding the ``InputInterface``, and the
``repository`` variable. A function reading the ``request`` variable, like the
``current_locale()`` example above, cannot be used in a console command:

.. code-block:: php

    #[MapDocument(class: Product::class, expr: 'repository.findByLocale(input.getArgument("locale"))')]

See :doc:`first_steps` for how to use the value resolver with console commands.

Version compatibility
---------------------

The ``doctrine_mongodb.odm.document_value_resolver.expression_language_provider`` tag
requires ``symfony/expression-language``, which ``#[MapDocument]`` expressions already
need. It works with every version of the component the bundle supports (6.4 or higher).

The security functions are only registered when ``symfony/security-bundle`` 8.2 or
higher is installed, where the ``security.expression_language_provider`` service
exists, and when the Security component is in use. With older versions,
``#[MapDocument]`` expressions keep working, but the security functions are not
available in them.

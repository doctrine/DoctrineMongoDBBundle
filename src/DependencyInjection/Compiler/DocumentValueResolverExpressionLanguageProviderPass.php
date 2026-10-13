<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the expression language providers for the document value resolver,
 * so that applications can add their own functions to #[MapDocument] expressions.
 *
 * @internal
 */
final class DocumentValueResolverExpressionLanguageProviderPass implements CompilerPassInterface
{
    public const EXPRESSION_LANGUAGE_PROVIDER_TAG = 'doctrine_mongodb.odm.document_value_resolver.expression_language_provider';

    private const EXPRESSION_LANGUAGE_SERVICE = 'doctrine_mongodb.odm.document_value_resolver.expression_language';

    /**
     * Provides current_user(), is_granted() and the other security functions,
     * evaluated through the authorization checker and the token storage.
     * Registered by SecurityBundle since Symfony 8.2.
     */
    private const SECURITY_PROVIDER_SERVICE = 'security.expression_language_provider';

    public function process(ContainerBuilder $container): void
    {
        // The service is removed by the extension when the ExpressionLanguage
        // component is not installed, in which case expressions cannot be used.
        if (! $container->hasDefinition(self::EXPRESSION_LANGUAGE_SERVICE)) {
            return;
        }

        $definition = $container->getDefinition(self::EXPRESSION_LANGUAGE_SERVICE);

        if ($container->has(self::SECURITY_PROVIDER_SERVICE)) {
            $definition->addMethodCall('registerProvider', [new Reference(self::SECURITY_PROVIDER_SERVICE)]);
        }

        foreach ($container->findTaggedServiceIds(self::EXPRESSION_LANGUAGE_PROVIDER_TAG, true) as $id => $tags) {
            $definition->addMethodCall('registerProvider', [new Reference($id)]);
        }
    }
}

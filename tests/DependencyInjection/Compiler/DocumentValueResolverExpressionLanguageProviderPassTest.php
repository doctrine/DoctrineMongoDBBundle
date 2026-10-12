<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DocumentValueResolverExpressionLanguageProviderPass;
use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\TestExpressionLanguageProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class DocumentValueResolverExpressionLanguageProviderPassTest extends TestCase
{
    public function testTaggedProvidersAreRegistered(): void
    {
        $container = $this->createContainer();
        $container->register('app.expression_language_provider', TestExpressionLanguageProvider::class)
            ->addTag(DocumentValueResolverExpressionLanguageProviderPass::EXPRESSION_LANGUAGE_PROVIDER_TAG);
        $container->register('app.other_expression_language_provider', TestExpressionLanguageProvider::class)
            ->addTag(DocumentValueResolverExpressionLanguageProviderPass::EXPRESSION_LANGUAGE_PROVIDER_TAG);

        (new DocumentValueResolverExpressionLanguageProviderPass())->process($container);

        $this->assertEquals(
            [
                ['registerProvider', [new Reference('app.expression_language_provider')]],
                ['registerProvider', [new Reference('app.other_expression_language_provider')]],
            ],
            $container->getDefinition('doctrine_mongodb.odm.document_value_resolver.expression_language')->getMethodCalls(),
        );
    }

    public function testUntaggedProvidersAreNotRegistered(): void
    {
        $container = $this->createContainer();
        $container->register('app.expression_language_provider', TestExpressionLanguageProvider::class);

        (new DocumentValueResolverExpressionLanguageProviderPass())->process($container);

        $this->assertSame(
            [],
            $container->getDefinition('doctrine_mongodb.odm.document_value_resolver.expression_language')->getMethodCalls(),
        );
    }

    public function testTheSecurityProviderIsRegisteredWhenSecurityBundleIsInUse(): void
    {
        $container = $this->createContainer();
        $container->register('security.expression_language_provider', TestExpressionLanguageProvider::class);
        $container->register('app.expression_language_provider', TestExpressionLanguageProvider::class)
            ->addTag(DocumentValueResolverExpressionLanguageProviderPass::EXPRESSION_LANGUAGE_PROVIDER_TAG);

        (new DocumentValueResolverExpressionLanguageProviderPass())->process($container);

        $this->assertEquals(
            [
                ['registerProvider', [new Reference('security.expression_language_provider')]],
                ['registerProvider', [new Reference('app.expression_language_provider')]],
            ],
            $container->getDefinition('doctrine_mongodb.odm.document_value_resolver.expression_language')->getMethodCalls(),
        );
    }

    public function testNothingIsRegisteredWithoutSecurityBundle(): void
    {
        $container = $this->createContainer();

        (new DocumentValueResolverExpressionLanguageProviderPass())->process($container);

        $this->assertSame(
            [],
            $container->getDefinition('doctrine_mongodb.odm.document_value_resolver.expression_language')->getMethodCalls(),
        );
    }

    public function testPassIsSkippedWithoutExpressionLanguage(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.expression_language_provider', TestExpressionLanguageProvider::class)
            ->addTag(DocumentValueResolverExpressionLanguageProviderPass::EXPRESSION_LANGUAGE_PROVIDER_TAG);

        (new DocumentValueResolverExpressionLanguageProviderPass())->process($container);

        $this->assertFalse($container->hasDefinition('doctrine_mongodb.odm.document_value_resolver.expression_language'));
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register('doctrine_mongodb.odm.document_value_resolver.expression_language', ExpressionLanguage::class);

        return $container;
    }
}

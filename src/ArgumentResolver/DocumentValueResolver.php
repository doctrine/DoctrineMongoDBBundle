<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\ArgumentResolver;

use Symfony\Bridge\Doctrine\ArgumentResolver\EntityValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

use function trigger_deprecation;

/**
 * @internal
 * @deprecated since 5.7, the "doctrine_mongodb.odm.document_value_resolver" service
 *             uses the "EntityValueResolver" from the Doctrine bridge directly.
 */
final class DocumentValueResolver implements ValueResolverInterface
{
    public function __construct(
        private EntityValueResolver $entityValueResolver,
    ) {
        trigger_deprecation(
            'doctrine/mongodb-odm-bundle',
            '5.7',
            'The "%s" class is deprecated, the "doctrine_mongodb.odm.document_value_resolver" service uses "%s" directly.',
            self::class,
            EntityValueResolver::class,
        );
    }

    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        return $this->entityValueResolver->resolve($request, $argument);
    }
}

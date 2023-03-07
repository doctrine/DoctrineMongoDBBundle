<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\ArgumentResolver;

use Symfony\Bridge\Doctrine\ArgumentResolver\EntityValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** @internal */
final class DocumentValueResolver implements ValueResolverInterface
{
    public function __construct(
        private EntityValueResolver $entityValueResolver,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        return $this->entityValueResolver->resolve($request, $argument);
    }
}

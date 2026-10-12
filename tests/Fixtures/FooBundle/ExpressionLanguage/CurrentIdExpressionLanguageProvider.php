<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\ExpressionLanguage;

use LogicException;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CurrentIdExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /** @return ExpressionFunction[] */
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'current_id',
                static function (): string {
                    throw new LogicException('The "current_id" function cannot be compiled.');
                },
                static fn (): string => 'user-identifier',
            ),
        ];
    }
}

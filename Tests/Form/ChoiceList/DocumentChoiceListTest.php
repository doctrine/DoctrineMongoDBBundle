<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Form\ChoiceList;

require_once __DIR__.'/../../Fixtures/Form/Document.php';

use Symfony\Bundle\DoctrineMongoDBBundle\Tests\TestCase;
use Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form\Document;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\ChoiceList\DocumentChoiceList;

class DocumentChoiceListTest extends TestCase
{
    const DOCUMENT_CLASS = 'Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form\Document';

    private $documentManager;

    protected function setUp()
    {
        parent::setUp();

        $this->documentManager = $this->createTestDocumentManager();
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\FormException
     */
    public function testChoicesMustBeManaged()
    {
        $document1 = new Document(1, 'Foo');
        $document2 = new Document(2, 'Bar');

        // no persist here!

        $choiceList = new DocumentChoiceList(
            $this->documentManager,
            self::DOCUMENT_CLASS,
            'name',
            null,
            array(
                $document1,
                $document2,
            )
        );

        // triggers loading -> exception
        $choiceList->getChoices();
    }
}

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
require_once __DIR__.'/../../Fixtures/Form/ItemGroupDocument.php';

use Symfony\Bundle\DoctrineMongoDBBundle\Tests\TestCase;
use Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form\Document;
use Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form\ItemGroupDocument;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\ChoiceList\DocumentChoiceList;

class DocumentChoiceListTest extends TestCase
{
    const DOCUMENT_CLASS = 'Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form\Document';

    const ITEM_GROUP_CLASS = 'Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Form\ItemGroupDocument';

    private $documentManager;

    protected function setUp()
    {
        parent::setUp();

        $this->documentManager = $this->createTestDocumentManager();
    }

    protected function persist(array $documents)
    {
        foreach ($documents as $document) {
            $this->documentManager->persist($document);
        }

        $this->documentManager->flush();
        // no clear, because documents managed by the choice field must
        // be managed!
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

    public function testGroupBySupportsString()
    {
        $item1 = new ItemGroupDocument(1, 'Foo', 'Group1');
        $item2 = new ItemGroupDocument(2, 'Bar', 'Group1');
        $item3 = new ItemGroupDocument(3, 'Baz', 'Group2');
        $item4 = new ItemGroupDocument(4, 'Boo!', null);

        $this->persist(array($item1, $item2, $item3, $item4));

        $choiceList = new DocumentChoiceList(
            $this->documentManager,
            self::ITEM_GROUP_CLASS,
            'name',
            null,
            array(
                $item1,
                $item2,
                $item3,
                $item4,
            ),
            'groupName'
        );

        $this->assertEquals(array(
            'Group1' => array(1 => 'Foo', '2' => 'Bar'),
            'Group2' => array(3 => 'Baz'),
            '4' => 'Boo!'
        ), $choiceList->getChoices('choices'));
    }

    public function testGroupByInvalidPropertyPathReturnsFlatChoices()
    {
        $item1 = new ItemGroupDocument(1, 'Foo', 'Group1');
        $item2 = new ItemGroupDocument(2, 'Bar', 'Group1');

        $this->persist(array($item1, $item2));

        $choiceList = new DocumentChoiceList(
            $this->documentManager,
            self::ITEM_GROUP_CLASS,
            'name',
            null,
            array(
                $item1,
                $item2,
            ),
            'groupName.child.that.does.not.exist'
        );

        $this->assertEquals(array(
            1 => 'Foo',
            2 => 'Bar'
        ), $choiceList->getChoices('choices'));
    }
}

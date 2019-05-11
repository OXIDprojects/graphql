<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidCommunity\GraphQl\Tests\Unit\Type;

use OxidEsales\GraphQl\Framework\GenericFieldResolver;
use OxidEsales\GraphQl\Framework\SchemaFactory;
use OxidCommunity\GraphQl\Dao\CategoryDaoInterface;
use OxidCommunity\GraphQl\DataObject\Category;
use OxidCommunity\GraphQl\Type\ObjectType\CategoryType;
use OxidCommunity\GraphQl\Type\Provider\CategoryProvider;
use OxidEsales\GraphQl\Tests\Unit\Type\GraphQlTypeTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CategoryTypeTest extends GraphQlTypeTestCase
{
    /** @var  CategoryDaoInterface|MockObject */
    private $categoryDao;

    /** @var  array */
    private $names;

    /** @var  int */
    private $shopid;

    /** @var  string|null */
    private $parentid;

    public function setUp()
    {
        parent::setUp();

        $this->categoryDao = $this->getMockBuilder(CategoryDaoInterface::class)->getMock();
        $categoryProvider = new CategoryProvider(
            $this->categoryDao,
            $this->permissionsService,
            new CategoryType(new GenericFieldResolver()));

        $schemaFactory = new SchemaFactory();
        $schemaFactory->addQueryProvider($categoryProvider);
        $schemaFactory->addMutationProvider($categoryProvider);

        $this->schema = $schemaFactory->getSchema();
    }

    public function testGetCategory() {

        $this->addPermission($this::DEFAULTGROUP, 'mayreaddata');

        $category = new Category();
        $category->setId('someid');
        $category->setName('somename');
        $category->setParentId('someparentid');

        $this->categoryDao->method('getCategory')->with('someid', 'de')->willReturn($category);

        $query = <<<EOQ
query TestQuery {
    category (categoryid: "someid") {
        name
    }
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(0, sizeof($result->errors), $result->errors[0]);
        $this->assertEquals('somename', $result->data['category']['name']);

    }

    public function testGetNonexistingCategory() {

        $this->addPermission($this::DEFAULTGROUP, 'mayreaddata');

        $this->categoryDao->method('getCategory')->with('nonexistingid', 'de')
            ->willThrowException(new \Exception('Category not found.'));

        $query = <<<EOQ
query TestQuery {
    category (categoryid: "nonexistingid") {
        name
    }
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(1, sizeof($result->errors));
        $this->assertEquals('Category not found.', $result->errors[0]->message);

    }

    public function testGetCategoryWithoutPermission() {

        $query = <<<EOQ
query TestQuery {
    category (categoryid: "someid") {
        name
    }
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(1, sizeof($result->errors));
        $this->assertRegExp('/^Missing Permission:/', $result->errors[0]->message);

    }

    public function testGetCategories() {

        $this->addPermission($this::DEFAULTGROUP, 'mayreaddata');

        $category1 = new Category();
        $category1->setId('id1');
        $category1->setName('name1');
        $category1->setParentId('parentid');

        $category2 = new Category();
        $category2->setId('id2');
        $category2->setName('name2');
        $category2->setParentId('parentid');

        $this->categoryDao->method('getCategories')->with('de', 1, 'parentid')
            ->willReturn([$category1, $category2]);

        $query = <<<EOQ
query TestQuery {
    categories (parentid: "parentid") {
        id
    }
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(0, sizeof($result->errors), $result->errors[0]);
        $this->assertEquals('id1', $result->data['categories'][0]['id']);
        $this->assertEquals('id2', $result->data['categories'][1]['id']);

    }

    public function testGetCategoriesEmptyResult() {

        $this->addPermission($this::DEFAULTGROUP, 'mayreaddata');

        $this->categoryDao->method('getCategories')->with('de', 1, 'parentid')
            ->willReturn([]);

        $query = <<<EOQ
query TestQuery {
    categories (parentid: "parentid") {
        id
    }
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(0, sizeof($result->errors), $result->errors[0]);
        $this->assertEquals(0, sizeof($result->data['categories']));

    }

    public function testGetCategoriesNoPermission() {

        $query = <<<EOQ
query TestQuery {
    categories (parentid: "parentid") {
        id
    }
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(1, sizeof($result->errors));
        $this->assertRegExp('/^Missing Permission:/', $result->errors[0]->message);

    }

    public function addCategory($names, $shopid, $parentid=null) {

        $this->names = $names;
        $this->parentid = $parentid;
        $this->shopid = $shopid;

        return "someidstring";

    }

    public function testAddRootCategory() {

        $this->names = null;
        $this->parentid = null;
        $this->shopid = null;

        $this->addPermission($this::DEFAULTGROUP, 'mayaddcategory');

        $this->categoryDao->method('addCategory')->willReturnCallback([$this, 'addCategory']);

        $query = <<<EOQ
mutation TestQuery {
    addCategory (names: ["Name lang 1", "Name lang 2"])
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(0, sizeof($result->errors), $result->errors[0]);
        $this->assertEquals('someidstring', $result->data['addCategory']);
        $this->assertEquals('Name lang 1', $this->names[0]);
        $this->assertNull($this->parentid);
        $this->assertEquals(1, $this->shopid);

    }

    public function testAddCategoryMissingPermission() {

        $query = <<<EOQ
mutation TestQuery {
    addCategory (names: ["Name lang 1", "Name lang 2"])
}
EOQ;
        $result = $this->executeQuery($query);

        $this->assertEquals(1, sizeof($result->errors));
        $this->assertEquals('Missing Permission: User someuser with group somegroup has no permissions at all.',
            $result->errors[0]->message);
    }

    public function testAddSubCategory() {

        $this->names = null;
        $this->parentid = null;
        $this->shopid = null;

        $this->addPermission($this::DEFAULTGROUP, 'mayaddcategory');

        $this->categoryDao->method('addCategory')->willReturnCallback([$this, 'addCategory']);

        $query = <<<EOQ
mutation TestQuery {
    addCategory (names: ["Name lang 1", "Name lang 2"], parentid: "someparentid")
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(0, sizeof($result->errors), $result->errors[0]);
        $this->assertEquals('someidstring', $result->data['addCategory']);
        $this->assertEquals('Name lang 1', $this->names[0]);
        $this->assertEquals('someparentid', $this->parentid);
        $this->assertEquals(1, $this->shopid);

    }
}

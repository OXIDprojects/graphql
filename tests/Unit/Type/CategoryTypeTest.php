<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidCommunity\GraphQl\Tests\Unit\Type;

use OxidEsales\GraphQl\Framework\GenericFieldResolver;
use OxidEsales\GraphQl\Framework\SchemaFactory;
use OxidCommunity\GraphQl\Common\Dao\CategoryDaoInterface;
use OxidCommunity\GraphQl\Common\DataObject\Category;
use OxidCommunity\GraphQl\Common\Type\ObjectType\CategoryType;
use OxidCommunity\GraphQl\Common\Type\Provider\CategoryProvider;
use OxidEsales\GraphQl\Tests\Unit\Type\GraphQlTypeTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CategoryTypeTest extends GraphQlTypeTestCase
{
    /** @var  CategoryDaoInterface|MockObject */
    private $categoryDao;

    /** @var  array */
    private $names;

    /** @var  int */
    private $shopId;

    /** @var  string|null */
    private $parentId;

    /** @var Category $category */
    private $category;

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
        $category->setId('someId');
        $category->setName('someName');
        $category->setParentId('someparentId');

        $this->categoryDao->method('getCategory')->with('someId', 'de')->willReturn($category);

        $query = <<<EOQ
query TestQuery {
    category (categoryId: "someId") {
        name
    }
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(0, sizeof($result->errors), $result->errors[0]);
        $this->assertEquals('someName', $result->data['category']['name']);

    }

    public function testGetNonexistingCategory() {

        $this->addPermission($this::DEFAULTGROUP, 'mayreaddata');

        $this->categoryDao->method('getCategory')->with('nonexistingid', 'de')
            ->willThrowException(new \Exception('Category not found.'));

        $query = <<<EOQ
query TestQuery {
    category (categoryId: "nonexistingid") {
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
    category (categoryId: "someId") {
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
        $category1->setParentId('parentId');

        $category2 = new Category();
        $category2->setId('id2');
        $category2->setName('name2');
        $category2->setParentId('parentId');

        $this->categoryDao->method('getCategories')->with('de', 1, 'parentId')
            ->willReturn([$category1, $category2]);

        $query = <<<EOQ
query TestQuery {
    categories (parentId: "parentId") {
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

        $this->categoryDao->method('getCategories')->with('de', 1, 'parentId')
            ->willReturn([]);

        $query = <<<EOQ
query TestQuery {
    categories (parentId: "parentId") {
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
    categories (parentId: "parentId") {
        id
    }
}
EOQ;
        $result = $this->executeQuery($query);
        $this->assertEquals(1, sizeof($result->errors));
        $this->assertRegExp('/^Missing Permission:/', $result->errors[0]->message);

    }

    public function addCategory($names, $shopId, $parentId=null) {

        $this->category = new Category();
        $this->category->setId('someIdString');
        $this->category->setName($names[0]);
        $this->category->setParentId($parentId);

        return $this->category;

    }

    public function testAddRootCategory() {

        $this->category = null;
        $this->addPermission($this::DEFAULTGROUP, 'mayaddcategory');
        $this->categoryDao->method('addCategory')->willReturnCallback([$this, 'addCategory']);

        $query = <<<EOQ
mutation TestMutation {
    addCategory (names: ["Name lang 1", "Name lang 2"]){
        id 
        name 
        parentId
    }
}
EOQ;

        $result = $this->executeQuery($query);

        $this->assertEquals(0, sizeof($result->errors), $result->errors[0]);
        $this->assertEquals('someIdString', $result->data['addCategory']['id']);
        $this->assertEquals('Name lang 1', $result->data['addCategory']['name']);
        $this->assertEquals('oxrootid', $result->data['addCategory']['parentId']);

    }

    public function testAddCategoryMissingPermission() {

        $query = <<<EOQ
mutation TestMutation {
    addCategory (names: ["Name lang 1", "Name lang 2"]){
        id 
        name 
        parentId
    }
}
EOQ;

        $result = $this->executeQuery($query);

        $this->assertEquals(1, sizeof($result->errors));
        $this->assertEquals('Missing Permission: User someuser with group somegroup has no permissions at all.',
            $result->errors[0]->message);
    }

    public function testAddSubCategory() {

        $this->addPermission($this::DEFAULTGROUP, 'mayaddcategory');
        $this->categoryDao->method('addCategory')->willReturnCallback([$this, 'addCategory']);

        $query = <<<EOQ
mutation TestMutation {
    addCategory (names: ["Name lang 1", "Name lang 2"], parentId: "someParentId"){
        id 
        name 
        parentId
    }
}
EOQ;

        $result = $this->executeQuery($query);

        $this->assertEquals(0, sizeof($result->errors), $result->errors[0]);
        $this->assertEquals('someIdString', $result->data['addCategory']['id']);
        $this->assertEquals('Name lang 1', $result->data['addCategory']['name']);
        $this->assertEquals('someParentId', $result->data['addCategory']['parentId']);

    }
}

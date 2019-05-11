<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidCommunity\GraphQl\Tests\Acceptance;

use OxidCommunity\GraphQl\Common\Dao\CategoryDaoInterface;
use OxidEsales\GraphQl\Tests\Acceptance\BaseGraphQlAcceptanceTestCase;

class CategoryTest extends BaseGraphQlAcceptanceTestCase
{

    private $rootCategory;

    private $subCategory1;

    private $subCategory2;

    public function setUp()
    {
        parent::setUp();
        /** @var CategoryDaoInterface $categoryDao */
        $categoryDao = $this->container->get(CategoryDaoInterface::class);
        $this->rootCategory = $categoryDao->addCategory(["rootcategory"], $this->getShopId(), 'de');
        $this->subCategory1 = $categoryDao->addCategory(["subcategory1"], $this->getShopId(), 'de', $this->rootCategory->getId());
        $this->subCategory2 = $categoryDao->addCategory(["subcategory2"], $this->getShopId(), 'de', $this->rootCategory->getId());
    }

    public function testGetCategory()
    {
        $catId = $this->subCategory1->getId();

        $query = <<<EOQ
query TestQuery {
    category (categoryId: "$catId") {
        name
    }
}
EOQ;
        $this->executeQuery($query);

        $this->assertHttpStatusOK();
        $this->assertEquals('subcategory1', $this->queryResult['data']['category']['name']);
    }

    public function testNotFound()
    {

        $query = <<<EOQ
query TestQuery {
    category (categoryId: "nonexistingid") {
        name
    }
}
EOQ;
        $this->executeQuery($query);

        $this->assertHttpStatus(404);
        $this->assertErrorMessage('Category with id "nonexistingid" not found.');
        $this->assertLogMessageContains('Category with id "nonexistingid" not found.');
    }

    public function testGetRootCategories()
    {

        $query = <<<EOQ
query TestQuery {
    categories {
        name
    }
}
EOQ;
        $this->executeQuery($query);

        $this->assertHttpStatusOK();
        $found = false;
        foreach ($this->queryResult['data']['categories'] as $categoryArray) {
            if ($categoryArray['name'] == 'rootcategory') {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testGetCategories()
    {
        $catId = $this->rootCategory->getId();

        $query = <<<EOQ
query TestQuery {
    categories (parentId: "$catId") {
        name
    }
}
EOQ;
        $this->executeQuery($query);

        $this->assertHttpStatusOK();
        $this->assertEquals(2, sizeof($this->queryResult['data']['categories']));
    }

    public function testAddCategory()
    {
        $catId = $this->rootCategory->getId();

        $query = <<<EOQ
mutation TestMutation {
    addCategory (names: ["Neue Kategorie", "New category"]){
        id
        name
        parentId
    }
}
EOQ;
        $this->executeQuery($query, 'admin');

        $this->assertHttpStatusOK();
        $this->assertEquals(3, sizeof($this->queryResult['data']['addCategory']));
    }

    public function testAddCategoryNoPermission()
    {
        $catId = $this->rootCategory->getId();

        $query = <<<EOQ
mutation TestMutation {
    addCategory (names: ["Neue Kategorie", "New category"], parentId: "$catId"){
        id
        name
        parentId
    }
}
EOQ;
        $this->executeQuery($query, 'customer');

        $this->assertHttpStatus(403);
    }

}

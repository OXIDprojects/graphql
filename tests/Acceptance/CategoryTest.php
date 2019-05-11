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

    private $rootId;

    private $subId1;

    private $subId2;

    public function setUp()
    {
        parent::setUp();
        /** @var CategoryDaoInterface $categoryDao */
        $categoryDao = $this->container->get(CategoryDaoInterface::class);
        $this->rootId = $categoryDao->addCategory(["rootcategory"], $this->getShopId());
        $this->subId1 = $categoryDao->addCategory(["subcategory1"], $this->getShopId(), $this->rootId);
        $this->subId2 = $categoryDao->addCategory(["subcategory2"], $this->getShopId(), $this->rootId);
    }

    public function testGetCategory()
    {

        $query = <<<EOQ
query TestQuery {
    category (categoryid: "$this->subId1") {
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
    category (categoryid: "nonexistingid") {
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

        $query = <<<EOQ
query TestQuery {
    categories (parentid: "$this->rootId") {
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

        $query = <<<EOQ
mutation TestMutation {
    addCategory (names: ["Neue Kategorie", "New category"], parentid: "$this->rootId")
}
EOQ;
        $this->executeQuery($query, 'admin');

        $this->assertHttpStatusOK();
        $this->assertEquals(32, strlen($this->queryResult['data']['addCategory']));
    }

    public function testAddCategoryNoPermission()
    {

        $query = <<<EOQ
mutation TestMutation {
    addCategory (names: ["Neue Kategorie", "New category"], parentid: "$this->rootId")
}
EOQ;
        $this->executeQuery($query, 'customer');

        $this->assertHttpStatus(403);
    }

}

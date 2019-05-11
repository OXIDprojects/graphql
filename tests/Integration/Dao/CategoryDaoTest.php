<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidCommunity\GraphQl\Tests\Integration\Dao;

use OxidEsales\GraphQl\Exception\ObjectNotFoundException;
use OxidCommunity\GraphQl\Common\Dao\CategoryDao;
use OxidCommunity\GraphQl\Common\Dao\CategoryDaoInterface;
use OxidEsales\GraphQl\Tests\Integration\ContainerTrait;

class CategoryDaoTest extends \PHPUnit\Framework\TestCase
{

    use ContainerTrait;
    /** @var  CategoryDao $categoryDao */
    private $categoryDao;

    /** @var  string $categoryRoot */
    private $categoryRoot;

    /** @var  string $categorySub1 */
    private $categorySub1;

    /** @var  string $categorySub2 */
    private $categorySub2;

    public function setUp()
    {
        $container = $this->createUncompiledContainer();
        $container->compile();
        $this->categoryDao = $container->get(CategoryDaoInterface::class);

        $this->categoryRoot = $this->categoryDao->addCategory(['Test deutsch', 'Test english'], 1,'de');
        $this->categorySub1 = $this->categoryDao->addCategory(['Unterkategorie 1', 'sub category 1'], 1,
            'de',  $this->categoryRoot->getId());
        $this->categorySub2 = $this->categoryDao->addCategory(['Unterkategorie 2', 'sub category 2'], 1,
            'de',  $this->categoryRoot->getId());
    }

    public function testGetCategory()
    {
        $category = $this->categoryDao->getCategory($this->categoryRoot->getId(), 'de', 1);
        $this->assertEquals($this->categoryRoot->getId(), $category->getId());
        $this->assertEquals('Test deutsch', $category->getName());

    }

    public function testGetCategoryOtherShop()
    {
        $this->expectException(ObjectNotFoundException::class, 'Category with id "'. $this->categoryRoot->getId() .
            '" not found.');
        $this->categoryDao->getCategory($this->categoryRoot->getId(), 'de', 2);

    }

    public function testGetCategoryWithWrongId()
    {
        $this->expectException(\Exception::class);
        $this->categoryDao->getCategory('somenonexistingid', 'de', 1);
    }

    public function testGetRootCategories()
    {
        $rootCategories = $this->categoryDao->getCategories('de', 1);
        $found = false;
        foreach ($rootCategories as $rootCategory) {
            /** @var \OxidCommunity\GraphQl\Common\DataObject\Category $rootCategory */
            if ($rootCategory->getId() == $this->categoryRoot->getId()) {
                $found = true;
            }
            if ($rootCategory->getId() == $this->categorySub1->getId()) {
                $this->fail('This should not be in the list of root categories.');
            }
            if ($rootCategory->getId() == $this->categorySub2->getId()) {
                $this->fail('This should not be in the list of root categories.');
            }
        }
        $this->assertTrue($found);

    }

    public function testGetSubCategories()
    {
        $subCategories = $this->categoryDao->getCategories('de', 1, $this->categoryRoot->getId());
        $this->assertEquals(2, sizeof($subCategories));
    }

    public function testAddRootCategoryDe()
    {
        $cat = $this->categoryDao->addCategory(['Deutscher Titel', 'English title'], 1, 'de');
        $category = $this->categoryDao->getCategory($cat->getId(), 'de', 1);
        $this->assertEquals('Deutscher Titel', $category->getName());
        $this->assertEquals('oxrootid', $category->getParentId());
    }

    public function testAddRootCategoryDeOtherShop()
    {
        $cat = $this->categoryDao->addCategory(['Deutscher Titel', 'English title'], 2, 'de');
        $notAddedToShop1 = false;
        try {
            $category = $this->categoryDao->getCategory($cat->getId(), 'de', 1);
        } catch (\Exception $e) {
            $notAddedToShop1 = true;
        }
        $this->assertTrue($notAddedToShop1);

        $category = $this->categoryDao->getCategory($cat->getId(), 'de', 2);
        $this->assertEquals('Deutscher Titel', $category->getName());
        $this->assertEquals('oxrootid', $category->getParentId());
    }

    public function testAddRootCategoryEn()
    {
        $cat = $this->categoryDao->addCategory(['Deutscher Titel', 'English title'], 1, 'de');
        $category = $this->categoryDao->getCategory($cat->getId(), 'en', 1);
        $this->assertEquals('English title', $category->getName());
        $this->assertEquals('oxrootid', $category->getParentId());
    }

    public function testAddSubCategoryDe()
    {
        $cat = $this->categoryDao->addCategory(['Deutscher Titel', 'English title'], 1, 'de', '30e44ab834ea42417.86131097');
        $category = $this->categoryDao->getCategory($cat->getId(), 'de', 1);
        $this->assertEquals('Deutscher Titel', $category->getName());
        $this->assertEquals('30e44ab834ea42417.86131097', $category->getParentId());
    }

}

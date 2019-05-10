<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidProfessionalServices\GraphQl\Tests\Integration\Dao;

use OxidEsales\GraphQl\Exception\ObjectNotFoundException;
use OxidProfessionalServices\GraphQl\Dao\CategoryDao;
use OxidProfessionalServices\GraphQl\Dao\CategoryDaoInterface;
use OxidEsales\GraphQl\Tests\Integration\ContainerTrait;

class CategoryDaoTest extends \PHPUnit_Framework_TestCase
{

    use ContainerTrait;
    /** @var  CategoryDao $categoryDao */
    private $categoryDao;

    /** @var  string $categoryIdRoot */
    private $categoryIdRoot;

    /** @var  string $categoryIdSub1 */
    private $categoryIdSub1;

    /** @var  string $categoryIdSub2 */
    private $categoryIdSub2;

    public function setUp()
    {
        $container = $this->createUncompiledContainer();
        $container->compile();
        $this->categoryDao = $container->get(CategoryDaoInterface::class);

        $this->categoryIdRoot = $this->categoryDao->addCategory(['Test deutsch', 'Test english'], 1);
        $this->categoryIdSub1 = $this->categoryDao->addCategory(['Unterkategorie 1', 'sub category 1'], 1,
            $this->categoryIdRoot);
        $this->categoryIdSub2 = $this->categoryDao->addCategory(['Unterkategorie 2', 'sub category 2'], 1,
            $this->categoryIdRoot);
    }

    public function testGetCategory()
    {
        $category = $this->categoryDao->getCategory($this->categoryIdRoot, 'de', 1);
        $this->assertEquals($this->categoryIdRoot, $category->getId());
        $this->assertEquals('Test deutsch', $category->getName());

    }

    public function testGetCategoryOtherShop()
    {
        $this->setExpectedException(ObjectNotFoundException::class, 'Category with id "'. $this->categoryIdRoot .
                                                                    '" not found.');
        $this->categoryDao->getCategory($this->categoryIdRoot, 'de', 2);

    }

    public function testGetCategoryWithWrongId()
    {
        $this->setExpectedException(\Exception::class);
        $this->categoryDao->getCategory('somenonexistingid', 'de', 1);
    }

    public function testGetRootCategories()
    {
        $rootCategories = $this->categoryDao->getCategories('de', 1);
        $found = false;
        foreach ($rootCategories as $rootCategory) {
            /** @var \OxidProfessionalServices\GraphQl\DataObject\Category $rootCategory */
            if ($rootCategory->getId() == $this->categoryIdRoot) {
                $found = true;
            }
            if ($rootCategory->getId() == $this->categoryIdSub1) {
                $this->fail('This should not be in the list of root categories.');
            }
            if ($rootCategory->getId() == $this->categoryIdSub2) {
                $this->fail('This should not be in the list of root categories.');
            }
        }
        $this->assertTrue($found);

    }

    public function testGetSubCategories()
    {
        $subCategories = $this->categoryDao->getCategories('de', 1, $this->categoryIdRoot);
        $this->assertEquals(2, sizeof($subCategories));
    }

    public function testAddRootCategoryDe()
    {
        $id = $this->categoryDao->addCategory(['Deutscher Titel', 'English title'], 1);
        $category = $this->categoryDao->getCategory($id, 'de', 1);
        $this->assertEquals('Deutscher Titel', $category->getName());
        $this->assertNull($category->getParentid());
    }

    public function testAddRootCategoryDeOtherShop()
    {
        $id = $this->categoryDao->addCategory(['Deutscher Titel', 'English title'], 2);
        $notAddedToShop1 = false;
        try {
            $category = $this->categoryDao->getCategory($id, 'de', 1);
        } catch (\Exception $e) {
            $notAddedToShop1 = true;
        }
        $this->assertTrue($notAddedToShop1);

        $category = $this->categoryDao->getCategory($id, 'de', 2);
        $this->assertEquals('Deutscher Titel', $category->getName());
        $this->assertNull($category->getParentid());
    }

    public function testAddRootCategoryEn()
    {
        $id = $this->categoryDao->addCategory(['Deutscher Titel', 'English title'], 1);
        $category = $this->categoryDao->getCategory($id, 'en', 1);
        $this->assertEquals('English title', $category->getName());
        $this->assertNull($category->getParentid());
    }

    public function testAddSubCategoryDe()
    {
        $id = $this->categoryDao->addCategory(['Deutscher Titel', 'English title'], 1,'30e44ab834ea42417.86131097');
        $category = $this->categoryDao->getCategory($id, 'de', 1);
        $this->assertEquals('Deutscher Titel', $category->getName());
        $this->assertEquals('30e44ab834ea42417.86131097', $category->getParentid());
    }

}

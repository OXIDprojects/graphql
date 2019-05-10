<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace  OxidProfessionalServices\GraphQl\Common\Dao;

use OxidEsales\EshopCommunity\Internal\Common\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQl\Exception\ObjectNotFoundException;
use OxidEsales\GraphQl\Utility\LegacyWrapper;
use OxidEsales\GraphQl\Utility\LegacyWrapperInterface;
use OxidProfessionalServices\GraphQl\Common\DataObject\Category;

class CategoryDao implements CategoryDaoInterface
{

    /** @var  QueryBuilderFactoryInterface $queryBuilderFactory */
    private $queryBuilderFactory;

    /** @var  LegacyWrapperInterface */
    private $legacyWrapper;

    /**
     * CategoryDao constructor.
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     * @param LegacyWrapper $legacyWrapper
     */
    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        LegacyWrapper $legacyWrapper
    )
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->legacyWrapper = $legacyWrapper;
    }

    /**
     * @param string $categoryId
     * @param string $lang
     * @param int $shopId
     * @return Category
     * @throws ObjectNotFoundException
     */
    public function getCategory(string $categoryId, string $lang, int $shopId): Category
    {
        $viewName = 'oxv_oxcategories_' . $lang;
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select(['OXTITLE', 'OXID', 'OXPARENTID'])
            ->from($viewName)
            ->where($queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('OXID', ':oxid'),
                        $queryBuilder->expr()->eq('OXSHOPID', ':shopid')
                )
            )
            ->setParameter('oxid', $categoryId)
            ->setParameter('shopid', $shopId);
        $result = $queryBuilder->execute();
        $row = $result->fetch();
        if (! $row) {
            throw new ObjectNotFoundException("Category with id \"$categoryId\" not found.");
        }
        else {
            return $this->rowToCategory($row);
        }
    }

    /**
     * @param string $lang
     * @param int $shopId
     * @param null $parentId
     * @return array
     */
    public function getCategories(string $lang, int $shopId, $parentId=null)
    {
        $viewName = 'oxv_oxcategories_' . $lang;
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select(['OXTITLE', 'OXID', 'OXPARENTID'])
            ->from($viewName)
            ->where($queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('OXPARENTID', ':oxparentid'),
                        $queryBuilder->expr()->eq('OXSHOPID', ':shopid')
                    )
                )
            ->setParameter('shopid', $shopId)
            ->setParameter('oxparentid', $parentId);

        $result = $queryBuilder->execute();
        $categoryList = [];
        foreach($result as $row) {
            $categoryList[] = $this->rowToCategory($row);
        }
        return $categoryList;

    }

    /**
     * @param array $names
     * @param int $shopId
     * @param string|null $parentId
     * @param string $lang
     * @return Category
     * @throws ObjectNotFoundException
     */
    public function addCategory(array $names, int $shopId, string $parentId, string $lang): Category
    {

        $queryBuilder = $this->queryBuilderFactory->create();
        $values = ['OXSHOPID' => ':shopid', 'OXTITLE' => ':title', 'OXPARENTID' => ':parentId'];
        $queryBuilder->setParameter('title', $names[0]);

        for ($i = 1; $i < sizeof($names); $i++) {
            $values["OXTITLE_$i"] = ":title_$i";
            $queryBuilder->setParameter("title_$i", $names[$i]);
        }
        $queryBuilder->setParameter('parentId', $parentId);

        $values['OXID'] = ':oxid';
        $id = $this->legacyWrapper->createUid();

        $queryBuilder->setParameter('oxid', $id)
            ->setParameter('shopid', $shopId);

        $queryBuilder->insert('oxcategories')->values($values)->execute();

        return $this->getCategory($id, $lang, $shopId);
    }

    /**
     * @param $row
     * @return Category
     */
    private function rowToCategory($row)
    {
        $category = new Category();
        $category->setName($row['OXTITLE']);
        $category->setId($row['OXID']);
        $category->setParentid($row['OXPARENTID']);

        return $category;
    }
}

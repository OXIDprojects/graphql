<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace  OxidCommunity\GraphQl\Common\Dao;

interface CategoryDaoInterface
{
    public function getCategory(string $categoryId, string $lang, int $shopId);

    public function getCategories(string $lang, int $shopId, string $parentId = null);

    public function addCategory(array $names, int $shopId, string $lang, string $parentId = null);

}
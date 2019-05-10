<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidCommunity\GraphQl\DataObject;

class Category
{
    /** @var  string $id */
    private $id;
    /** @var  string $name */
    private $name;
    /** @var  string|null $parentId */
    private $parentId;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param null|string $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

}

<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace  OxidProfessionalServices\GraphQl\Custom\Dao;

use OxidEsales\EshopCommunity\Internal\Common\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQl\Exception\ObjectNotFoundException;
use OxidEsales\GraphQl\Utility\LegacyWrapper;
use OxidEsales\GraphQl\Utility\LegacyWrapperInterface;
use OxidProfessionalServices\GraphQl\Custom\DataObject\Location;

class LocationDao implements LocationDaoInterface
{

    /** @var  QueryBuilderFactoryInterface $queryBuilderFactory */
    private $queryBuilderFactory;

    /** @var  LegacyWrapperInterface */
    private $legacyWrapper;

    /**
     * LocationDao constructor.
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
     * @param string $id
     * @return Location
     * @throws ObjectNotFoundException
     */
    public function getLocation(int $id): Location
    {
        $viewName = 'oxps_location';
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select(['OXID', 'NAME', 'REGION'])
            ->from($viewName)
            ->where($queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('OXID', ':oxid')
                )
            )
            ->setParameter('oxid', $id);
        $result = $queryBuilder->execute();
        $row = $result->fetch();
        if (!$row) {
            throw new ObjectNotFoundException("Location with id \"$id\" not found.");
        }
        else {
            return $this->rowToLocation($row);
        }
    }

    /**
     * @param null $region
     * @return array
     */
    public function getLocations($region = null)
    {
        $viewName = 'oxps_location';
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select(['OXID', 'NAME', 'REGION'])
            ->from($viewName);
        if ($region !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq('REGION', ':region'))
            );
            $queryBuilder->setParameter('region', $region);
        }
        $result = $queryBuilder->execute();
        $locationList = [];
        foreach($result as $row) {
            $locationList[] = $this->rowToLocation($row);
        }
        return $locationList;

    }

    /**
     * @param int $id
     * @param string $name
     * @param string|null $region
     * @return Location
     * @throws ObjectNotFoundException
     */
    public function addLocation(int $id, string $name, string $region = null): Location
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $values = ['OXID' => ':oxid', 'NAME' => ':name', 'REGION' => ':region'];
        $queryBuilder->setParameter('oxid', $id);
        $queryBuilder->setParameter('name', $name);
        $queryBuilder->setParameter('region', $region);
        $queryBuilder->insert('oxps_location')->values($values)->execute();

        $lastInsertId = $queryBuilder->getConnection()->lastInsertId();
        return $this->getLocation((int)$lastInsertId);
    }

    /**
     * @param $row
     * @return Location
     */
    private function rowToLocation($row)
    {
        $location = new Location();
        $location->setId($row['OXID']);
        $location->setName($row['NAME']);
        $location->setRegion($row['REGION']);

        return $location;
    }
}

<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace  OxidProfessionalServices\GraphQl\Custom\Dao;

interface LocationDaoInterface
{
    public function getLocation(int $id);

    public function getLocations($region = null);

    public function addLocation( int $id, string $name, string $region = null);

}
<?php
declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace  OxidProfessionalServices\GraphQl\Custom\Type\ObjectType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OxidEsales\GraphQl\Framework\GenericFieldResolverInterface;

class LocationType extends ObjectType
{

    /**
     * @var GenericFieldResolverInterface
     */
    private $genericFieldResolver;

    /**
     * ZipCodeType constructor.
     * @param GenericFieldResolverInterface $genericFieldResolver
     */
    public function __construct(GenericFieldResolverInterface $genericFieldResolver)
    {
        $this->genericFieldResolver = $genericFieldResolver;

        $config = [
            'name'         => 'Location',
            'description'  => 'Primitive location object',
            'fields'       => [
                'id' => Type::int(),
                'name' => Type::string(),
                'region' => Type::string()
            ],
            'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
                return $this->genericFieldResolver->getField($info->fieldName, $value);
            }
        ];
        parent::__construct($config);
    }

}

<?php
declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace  OxidCommunity\GraphQl\Type\ObjectType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OxidEsales\GraphQl\Framework\GenericFieldResolverInterface;

class CategoryType extends ObjectType
{

    /**
     * @var GenericFieldResolverInterface
     */
    private $genericFieldResolver;

    /**
     * CategoryType constructor.
     * @param GenericFieldResolverInterface $genericFieldResolver
     */
    public function __construct(GenericFieldResolverInterface $genericFieldResolver)
    {
        $this->genericFieldResolver = $genericFieldResolver;

        $config = [
            'name'         => 'Category',
            'description'  => 'Primitive category object',
            'fields'       => [
                'id'       => Type::string(),
                'name'     => Type::string(),
                'parentId' => [
                    'type' => Type::string(),
                    'defaultValue' => 'oxrootid'
                ]
            ],
            'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
                return $this->genericFieldResolver->getField($info->fieldName, $value);
            }
        ];
        parent::__construct($config);
    }

}

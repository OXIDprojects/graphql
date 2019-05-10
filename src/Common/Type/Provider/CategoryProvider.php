<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace  OxidProfessionalServices\GraphQl\Common\Type\Provider;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OxidEsales\GraphQl\Service\PermissionsServiceInterface;
use OxidEsales\GraphQl\Type\Provider\MutationProviderInterface;
use OxidEsales\GraphQl\Type\Provider\QueryProviderInterface;
use OxidEsales\GraphQl\Framework\AppContext;
use OxidProfessionalServices\GraphQl\Common\Dao\CategoryDaoInterface;
use OxidProfessionalServices\GraphQl\Common\Type\ObjectType\CategoryType;

class CategoryProvider implements QueryProviderInterface, MutationProviderInterface
{
    /** @var  CategoryDaoInterface */
    private $categoryDao;

    /** @var  PermissionsServiceInterface */
    private $permissionsService;

    /** @var  CategoryType */
    private $categoryType;

    /**
     * CategoryProvider constructor.
     * @param CategoryDaoInterface $categoryDao
     * @param PermissionsServiceInterface $permissionsService
     * @param CategoryType $categoryType
     */
    public function __construct(CategoryDaoInterface $categoryDao,
                                PermissionsServiceInterface $permissionsService,
                                CategoryType $categoryType)
    {
        $this->categoryDao = $categoryDao;
        $this->permissionsService = $permissionsService;
        $this->categoryType = $categoryType;
    }

    public function getQueries()
    {
        return [
            'category' => [
                'type'        => $this->categoryType,
                'description' => 'Get a category object.',
                'args'        => [
                    'categoryId' => Type::nonNull(Type::string())
                ]
            ],
            'categories' => [
                'type'        => Type::listOf($this->categoryType),
                'description' => 'Get a list of child objects for a parent category. ' .
                                 'If no parentId is given, the root categories are returned.',
                'args'        => [
                    'parentId' => [
                        'type' => Type::string(),
                        'defaultValue' => 'oxrootid'
                    ]
                ]
            ]
        ];
    }

    public function getQueryResolvers()
    {
        return [
            'category' => function ($value, $args, $context, ResolveInfo $info) {
                /** @var AppContext $context */
                $token = $context->getAuthToken();
                $this->permissionsService->checkPermission($token, 'mayreaddata');
                return $this->categoryDao->getCategory(
                    $args['categoryId'],
                    $token->getLang(),
                    $token->getShopid()
                );
            },
            'categories' => function ($value, $args, $context, ResolveInfo $info) {
                /** @var AppContext $context */
                $token = $context->getAuthToken();
                $this->permissionsService->checkPermission($token, 'mayreaddata');

                return $this->categoryDao->getCategories($token->getLang(), $token->getShopid(), $args['parentId']);
            }
        ];
    }

    public function getMutations()
    {
        return [
            'addCategory' => [
                'type'        => $this->categoryType,
                'description' => 'Add a new category object.',
                'args'        => [
                    'names' => Type::nonNull(Type::listOf(Type::string())),
                    'parentId' => [
                        'type' => Type::string(),
                        'defaultValue' => 'oxrootid'
                    ]
                ]
            ]
        ];
    }

    public function getMutationResolvers()
    {
        return [
            'addCategory' => function ($value, $args, $context, ResolveInfo $info) {
                /** @var AppContext $context */
                $token = $context->getAuthToken();
                $this->permissionsService->checkPermission($token, 'mayaddcategory');

                return $this->categoryDao->addCategory($args['names'], $token->getShopid(), $args['parentId'], $token->getLang());
            }
        ];
    }

}

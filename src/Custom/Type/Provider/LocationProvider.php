<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace  OxidProfessionalServices\GraphQl\Custom\Type\Provider;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OxidEsales\GraphQl\Framework\AppContext;
use OxidEsales\GraphQl\Service\PermissionsServiceInterface;
use OxidEsales\GraphQl\Type\Provider\MutationProviderInterface;
use OxidEsales\GraphQl\Type\Provider\QueryProviderInterface;
use OxidProfessionalServices\GraphQl\Custom\Dao\LocationDaoInterface;
use OxidProfessionalServices\GraphQl\Custom\Type\ObjectType\LocationType;

class LocationProvider implements QueryProviderInterface, MutationProviderInterface
{
    /** @var  LocationDaoInterface */
    private $locationDao;

    /** @var  PermissionsServiceInterface */
    private $permissionsService;

    /** @var  LocationType */
    private $locationType;

    /**
     * LocationProvider constructor.
     * @param LocationDaoInterface $locationDao
     * @param PermissionsServiceInterface $permissionsService
     * @param LocationType $locationType
     */
    public function __construct(LocationDaoInterface $locationDao,
                                PermissionsServiceInterface $permissionsService,
                                LocationType $locationType)
    {
        $this->locationDao = $locationDao;
        $this->permissionsService = $permissionsService;
        $this->locationType = $locationType;
    }

    public function getQueries()
    {
        return [
            'location' => [
                'type'        => $this->locationType,
                'description' => 'Get a location object.',
                'args'        => [
                    'id' => Type::nonNull(Type::int())
                ]
            ],
            'locations' => [
                'type'        => Type::listOf($this->locationType),
                'description' => 'Get a list of child objects for a location. ' .
                                 'If no location is given, the zip codes are returned.',
                'args'        => [
                    'region' => Type::string()
                ]
            ]
        ];
    }

    public function getQueryResolvers()
    {
        return [
            'location' => function ($value, $args, $context, ResolveInfo $info) {
                /** @var AppContext $context */
                $token = $context->getAuthToken();
                $this->permissionsService->checkPermission($token, 'mayreaddata');
                return $this->locationDao->getLocation(
                    $args['id'],
                    $token->getLang(),
                    $token->getShopid()
                );
            },
            'locations' => function ($value, $args, $context, ResolveInfo $info) {
                /** @var AppContext $context */
                $token = $context->getAuthToken();
                $this->permissionsService->checkPermission($token, 'mayreaddata');
                    return $this->locationDao->getLocations( $args['region'], $token->getLang(), $token->getShopid());
            }
        ];
    }

    public function getMutations()
    {
        return [
            'addLocation' => [
                'type'        => $this->locationType,
                'description' => 'Add a new location object.',
                'args'        => [
                    'id'     => Type::nonNull(Type::int()),
                    'name'   => Type::nonNull(Type::string()),
                    'region' => Type::nonNull(Type::string())
                ]
            ]
        ];
    }

    public function getMutationResolvers()
    {
        return [
            'addLocation' => function ($value, $args, $context, ResolveInfo $info) {
                /** @var AppContext $context */
                $token = $context->getAuthToken();
                $this->permissionsService->checkPermission($token, 'mayaddlocation');

                return $this->locationDao->addLocation($args['id'], $args['name'], $args['region']);

            }
        ];
    }

}

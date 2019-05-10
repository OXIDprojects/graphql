<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

/**
 * Module information
 */
$aModule = [
    'id'            => 'oxps/graphql',
    'title'         => 'OXPS :: <strong style="color: #d64292">GraphQL</strong>',
    'description'   =>  [
        'de' => '<span>The OXID reference implementation for GraphQL, a query language for APIs.</span>',
        'en' => '<span>Die OXID-Referenzimplementierung für GraphQL, eine Abfragesprache für APIs.</span>',
    ],
    'thumbnail'   => 'out/pictures/graphql.png',
    'version'     => '1.0.2',
    'author'      => 'OXID eSales',
    'url'         => 'www.oxid-esales.com',
    'email'       => 'info@oxid-esales.com',
    'extend'      => [
    ],
    'controllers' => [
    ],
    'templates'   => [
    ],
    'blocks'      => [
    ],
    'settings'    => [
    ],
    'events'      => [
        'onActivate'   => 'OxidEsales\\GraphQl\\Framework\\ModuleSetup::onActivate',
        'onDeactivate' => 'OxidEsales\\GraphQl\\Framework\\ModuleSetup::onDeactivate'
    ]
];

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
    'id'            => 'oxcom/graphql-common-types',
    'title'         => 'OXPS :: <strong style="color: #d64292">GraphQL Common Types</strong>',
    'description'   =>  [
        'de' => '<span>The most Common Types of a GraphQL schema,
                which just represent a kind of object you can fetch from the service, and what fields it has</span>',
        'en' => '<span>Die Common Typen eines GraphQL-Schemas,
                die lediglich eine Art Objekt darstellen, das Sie vom Service abrufen können und welche Felder es enthält</span>',
    ],
    'thumbnail'   => 'out/pictures/graphql.png',
    'version'     => '0.0.1',
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

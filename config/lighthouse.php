<?php
return [
    'schema'=>base_path('auxfin/global/graphql/schema.graphql'),
    'namespaces' => [
        'models' => ['App', 'App\\Models','Auxfin\\Global\\Models'],
        'queries' => ['App\\GraphQL\\Queries','Auxfin\\Global\\GraphQL\\Queries'],
        'mutations' => ['App\\GraphQL\\Mutations','Auxfin\\Global\\GraphQL\\Mutations'],
    ],
];

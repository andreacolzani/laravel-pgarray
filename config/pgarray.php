<?php

// config for AndreaColzani/PgArray
return [

    /*
    |--------------------------------------------------------------------------
    | Element serializers
    |--------------------------------------------------------------------------
    |
    | Map element classes to external serializers implementing
    | PgArrayValueSerializer (or PgArrayJsonSerializer for json[] / jsonb[]
    | columns). The same serializer may be mapped to multiple classes.
    |
    | Classes are matched by exact name. A serializer mapped here takes
    | precedence over the #[PgArraySerializer] attribute, the
    | PgArraySerializable contract, PgArrayValue and backed enum support.
    |
    */

    'serializers' => [
        // App\Values\Money::class => App\Serializers\MoneySerializer::class,
    ],

];

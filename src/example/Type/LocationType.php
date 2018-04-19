<?php

namespace M1naret\GraphQL\Type\Location;

use GraphQL\Type\Definition\Type;
use M1naret\GraphQL\Support\Type as GraphQLType;
use Models\Location; // not included in this project

class LocationType extends GraphQLType {

    protected $attributes = [
        'name'          => 'Location',
        'description'   => 'A location on the map',
        'model'         => Location::class,
    ];

    public function fields()
    {
        return [
            'id' => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'Id of the location',
            ],
            'country_code' => [
                'type'          => Type::nonNull(Type::string()),
                'description'   => 'Country code of the location (e.g "EE")',
            ],
            'address' => [
                'type'          => Type::string(),
                'description'   => 'Location\'s address (street, house nr, etc)',
            ],
            'city' => [
                'type'          => Type::string(),
                'description'   => 'Location\'s city',
            ],
            'post_code' => [
                'type'          => Type::int(),
                'description'   => 'Post code of the location',
            ],
            'latitude' => [
                'type'          => Type::float(),
                'description'   => 'Latitude of the location',
            ],
            'longitude' => [
                'type'          => Type::float(),
                'description'   => 'Longitude of the location',
            ],
        ];
    }

}
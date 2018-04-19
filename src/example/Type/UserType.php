<?php

namespace M1naret\GraphQL\Type\User;

use M1naret\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
use GraphQL\GraphQL;
use GraphQL\Privacy\MePrivacy;
use Models\User; // not included in this project

class UserType extends GraphQLType {

    protected $attributes = [
        'name'          => 'User',
        'description'   => 'A user',
        'model'         => User::class,
    ];

    public function fields()
    {
        return [
            'id' => [
                'type'          => Type::nonNull(Type::int()),
                'description'   => 'ID of the user',
            ],
            'email' => [
                'type'          => Type::string(),
                'description'   => 'Email of the user',
                'privacy'       => MePrivacy::class,
            ],
            'avatar' => [
                'type'          => Type::string(),
                'description'   => 'Avatar (picture) of the user',
                'alias'         => 'display_picture', // Column name in database
            ],
            'cover' => [
                'type'          => Type::string(),
                'description'   => 'Cover (picture) of the user',
            ],
            'confirmed' => [
                'type'          => Type::boolean(),
                'description'   => 'Confirmed status of the user',
            ],
            'pin' => [
                'type'          => Type::string(),
                'description'   => 'Pin (ID code) of the user',
            ],

            /* RELATIONS */
            'profile' => [
                'type'          => GraphQL::type('user_profile'),
                'description'   => 'User profile',
            ],
        ];
    }

}
<?php

namespace M1naret\GraphQL\Query\User;

use M1naret\GraphQL\Support\Query;
use M1naret\GraphQL\Support\SelectFields;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use Models\User; // not included in this project

class UsersQuery extends Query {

    protected $attributes = [
        'name'  => 'Users',
    ];

    public function type()
    {
        return Type::listOf(GraphQL::type('user'));
    }

    public function args()
    {
        return [
            'ids'   => [
                'name' => 'ids',
                'type' => Type::listOf(Type::int()),
            ],
        ];
    }

    public function resolve($root, $args, SelectFields $fields)
    {
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        return User::where('id', '=', $args['id'])->with($with)->select($select)->get();
    }

}
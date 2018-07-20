<?php
/**
 * Created by PhpStorm.
 * User: I.Kapelyushny
 * Date: 12.07.2018
 * Time: 14:12
 */

namespace M1naret\GraphQL\Support;

use Illuminate\Support\Fluent as BaseFluent;

class Fluent extends BaseFluent
{
    /**
     * @param int $defaultPerPage
     *
     * @return array
     */
    public function getArgsForPagination(int $defaultPerPage = 15) : array
    {
        return [
            'page'     => [
                'type' => \GraphQL\Type\Definition\Type::int(),
                'defaultValue' => 1,
            ],
            'per_page' => [
                'type' => \GraphQL\Type\Definition\Type::int(),
                'defaultValue' => $defaultPerPage,
            ],
        ];
    }
}
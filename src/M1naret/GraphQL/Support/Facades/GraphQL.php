<?php namespace M1naret\GraphQL\Support\Facades;

use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Facades\Facade;
use M1naret\GraphQL\Support\PaginationType;

/**
 * Class GraphQL
 * @package M1naret\GraphQL\Support\Facades
 *
 * @method ObjectType objectType(string $name, $type, $opts = [])
 * @method ObjectType type(string $typeName, bool $fresh = false)
 * @method PaginationType paginate(string $typeName, string $customName = null)
 */
class GraphQL extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() : string
    {
        return 'graphql';
    }
}

# Advanced Usage

- [Authorization](#authorization)
- [Privacy](#privacy)
- [Query variables](#query-variables)
- [Custom field](#custom-field)
- [Eager loading relationships](#eager-loading-relationships)
- [Type relationship query](#type-relationship-query)
- [Pagination](#pagination)
- [Batching](#batching)
- [Enums](#enums)

### Authorization

For authorization similar to Laravel's Request (or middleware) functionality, we can override the `authorize()` function in a Query or Mutation.
An example of Laravel's `'auth'` middleware:

```php
use Auth;

class UsersQuery extends Query
{
    public function authorize(array $args)
    {
        // true, if logged in
        return ! Auth::guest();
    }
    
    ...
}
```

Or we can make use of arguments passed via the graph query:

```php
use Auth;

class UsersQuery extends Query
{
    public function authorize(array $args)
    {
        if(isset($args['id']))
        {
            return Auth::id() == $args['id'];
        }
        
        return true;
    }
    
    ...
}
```

### Privacy

You can set custom privacy attributes for every Type's Field. If a field is not allowed, `null` will be returned. For example, if you want the user's email to only be accessible to themselves:

```php
class UserType extends GraphQLType {
        
        ...
		
        public function fields()
        {
            return [
                'id' => [
                    'type'          => Type::nonNull(Type::string()),
                    'description'   => 'The id of the user'
                ],
                'email' => [
                    'type'          => Type::string(),
                    'description'   => 'The email of user',
                    'privacy'       => function(array $args)
                    {
                        return $args['id'] == Auth::id();
                    }
                ]
            ];
        }
            
        ...
        
    }
```

or you can create a class that extends the abstract GraphQL Privacy class:

```php
use M1naret\GraphQL\Support\Privacy;
use Auth;

class MePrivacy extends Privacy {

    public function validate(array $args)
    {
        return $args['id'] == Auth::id();
    }

}
```

```php
use MePrivacy;

class UserType extends GraphQLType {
        
        ...
		
        public function fields()
        {
            return [
                'id' => [
                    'type'          => Type::nonNull(Type::string()),
                    'description'   => 'The id of the user'
                ],
                'email' => [
                    'type'          => Type::string(),
                    'description'   => 'The email of user',
                    'privacy'       => MePrivacy::validate(),
                ]
            ];
        }
            
        ...
        
    }
```

### Query Variables

GraphQL offers you the possibility to use variables in your query so you don't need to "hardcode" value. This is done like that:

```
    query FetchUserByID($id: String) 
    {
        user(id: $id) {
            id
            email
        }
    }
```

When you query the GraphQL endpoint, you can pass a `params` (or whatever you define in the config) parameter.

```
http://homestead.app/graphql?query=query+FetchUserByID($id:Int){user(id:$id){id,email}}&params={"id":123}
```

Notice that your client side framework might use another parameter name than `params`. You can customize the parameter name to anything your client is using by adjusting the `params_key` in the `graphql.php` configuration file.

### Custom field

You can also define a field as a class if you want to reuse it in multiple types.

```php

namespace App\GraphQL\Fields;
	
use GraphQL\Type\Definition\Type;
use M1naret\GraphQL\Support\Field;

class PictureField extends Field {
        
        protected $attributes = [
            'description'   => 'A picture',
        ];
	
	public function type()
	{
		return Type::string();
	}
		
	public function args()
	{
		return [
			'width' => [
				'type' => Type::int(),
				'description' => 'The width of the picture'
			],
			'height' => [
				'type' => Type::int(),
				'description' => 'The height of the picture'
			]
		];
	}
	
	protected function resolve($root, $args)
	{
		$width = isset($args['width']) ? $args['width']:100;
		$height = isset($args['height']) ? $args['height']:100;
		return 'http://placehold.it/'.$width.'x'.$height;
	}
        
}

```

You can then use it in your type declaration

```php

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use M1naret\GraphQL\Support\Type as GraphQLType;

use App\GraphQL\Fields\PictureField;

class UserType extends GraphQLType {
        
        protected $attributes = [
            'name'          => 'User',
            'description'   => 'A user',
            'model'         => UserModel::class,
        ];
	
	public function fields()
	{
		return [
			'id' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'The id of the user'
			],
			'email' => [
				'type' => Type::string(),
				'description' => 'The email of user'
			],
			//Instead of passing an array, you pass a class path to your custom field
			'picture' => PictureField::class
		];
	}

}

```

### Eager loading relationships

The third argument passed to a query's resolve method is an instance of `M1naret\GraphQL\Support\SelectFields` which you can use to retrieve keys from the request. The following is an example of using this information to eager load related Eloquent models.
This way only the required fields will be queried from the database.

Your Query would look like

```php
	namespace App\GraphQL\Query;
	
	use GraphQL;
	use GraphQL\Type\Definition\Type;
	use GraphQL\Type\Definition\ResolveInfo;
	use M1naret\GraphQL\Support\SelectFields;
	use M1naret\GraphQL\Support\Query;
	
	use App\User;

	class UsersQuery extends Query
	{
		protected $attributes = [
			'name' => 'Users query'
		];

		public function type()
		{
			return Type::listOf(GraphQL::type('user'));
		}

		public function args()
		{
			return [
				'id' => ['name' => 'id', 'type' => Type::string()],
				'email' => ['name' => 'email', 'type' => Type::string()]
			];
		}
        
		public function resolve($root, $args, SelectFields $fields, ResolveInfo $info)
		{
		    // $info->getFieldSelection($depth = 3);
		    
			$select = $fields->getSelect();
			$with = $fields->getRelations();
			
			$users = User::select($select)->with($with);
			
			return $users->get();
		}
	}
```

Your Type for User would look like. The `profile` and `posts` relations must also exist in the UserModel's relations.
If some fields are required for the relation to load or validation etc, then you can define an `always` attribute that will add the given attributes to select.

```php
<?php

namespace App\GraphQL\Type;

use M1naret\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use M1naret\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    /**
     * @var array
     */
    protected $attributes = [
        'name'          => 'User',
        'description'   => 'A user',
        'model'         => UserModel::class,
    ];

    /**
     * @return array
     */
    public function fields()
    {
        return [
            'uuid' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The uuid of the user'
            ],
            'email' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The email of user'
            ],
            'profile' => [
                'type' => GraphQL::type('Profile'),
                'description' => 'The user profile',
            ],
            'posts' => [
                'type' => Type::listOf(GraphQL::type('Post')),
                'description' => 'The user posts',
                // Can also be defined as a string
                'always' => ['title', 'body'],
            ]
        ];
    }
}

```

At this point we have a profile and a post type as expected for any model

```php
class ProfileType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'Profile',
        'description'   => 'A user profile',
        'model'         => UserProfileModel::class,
    ];

    public function fields()
    {
        return [
            'name' => [
                'type' => Type::string(),
                'description' => 'The name of user'
            ]
        ];
    }
}
```

```php
class PostType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'Post',
        'description'   => 'A post',
        'model'         => PostModel::class,
    ];

    public function fields()
    {
        return [
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The title of the post'
            ],
            'body' => [
                'type' => Type::string(),
                'description' => 'The body the post'
            ]
        ];
    }
}
```

### Type relationship query

You can also specify the `query` that will be included with a relationship via Eloquent's query builder:

```php
class UserType extends GraphQLType {

    ...
    
    public function fields()
    {
        return [
            
            ...
            
            // Relation
            'posts' => [
                'type'          => Type::listOf(GraphQL::type('post')),
                'description'   => 'A list of posts written by the user',
                // The first args are the parameters passed to the query
                'query'         => function(array $args, $query) {
                    return $query->where('posts.created_at', '>', $args['date_from']);
                }
            ]
        ];
    }

}
```

### Pagination

Pagination will be used, if a query or mutation returns a `PaginationType`. Note that you have to manually handle the 
limit and page values:

```php
class PostsQuery extends Query {

    public function type()
    {
        return GraphQL::paginate('posts');
    }
    
    ...
    
    public function resolve($root, $args, SelectFields $fields)
    {
        return Post::with($fields->getRelations())->select($fields->getSelect())
            ->paginate($args['limit'], ['*'], 'page', $args['page']);
    }
}
```

Query `posts(limit:10,page:1){data{id},total,per_page}` might return

```
{
    "data": {
        "posts: [
            "data": [
                {"id": 3},
                {"id": 5},
                ...
            ],
            "total": 21,
            "per_page": 10"
        ]
    }
}
```

### Batching

You can send multiple queries (or mutations) at once by grouping them together. Therefore, instead of creating two HTTP requests:

```
POST
{
    query: "query postsQuery { posts { id, comment, author_id } }"
}

POST
{
    query: "mutation storePostMutation($comment: String!) { store_post(comment: $comment) { id } }",
    variables: { "comment": "Hi there!" }
}
```

you could batch it as one

```
POST
[
    {
        query: "query postsQuery { posts { id, comment, author_id } }"
    },
    {
        query: "mutation storePostMutation($comment: String!) { store_post(comment: $comment) { id } }",
        variables: { "comment": "Hi there!" }
    }
]
```

For systems sending multiple requests at once, this can really help performance by batching together queries that will be made
within a certain interval of time.

There are tools that help with this and can handle the batching for you, e.g [Apollo](http://www.apollodata.com/)


### Enums

Enumeration types are a special kind of scalar that is restricted to a particular set of allowed values.
Read more about Enums [here](http://graphql.org/learn/schema/#enumeration-types)

First create an Enum as an extension of the GraphQLType class:
```php
// app/GraphQL/Enums/EpisodeEnum.php
namespace App\GraphQL\Enums;

use M1naret\GraphQL\Support\Type as GraphQLType;

class EpisodeEnum extends GraphQLType {

    protected $enumObject = true;

    protected $attributes = [
        'name' => 'Episode',
        'description' => 'The types of demographic elements',
        'values' => [
            'NEWHOPE' => 'NEWHOPE',
            'EMPIRE' => 'EMPIRE',
            'JEDI' => 'JEDI',
        ],
    ];
    
}

```
Register the Enum in the 'types' array of the graphql.php config file:

```php
// config/graphql.php
'types' => [
    'EpisodeEnum' => EpisodeEnum::class
];
```

Then use it like:
```php
// app/GraphQL/Type/TestType.php
class TestType extends GraphQLType {

   public function fields()
   {
        return [
            'episode_type' => [
                'type' => GraphQL::type('EpisodeEnum')
            ]
        ]
   }
   
}
```

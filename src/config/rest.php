<?php

return [
    /**
     * The prefix of each API call. Ex:
     *  myserver.com/api/users => API call
     *  myserver.com/users => not API call
     *
     * Default: api
     */
    'route_prefix' => 'api',

    /**
     * The namespace of your application's controllers
     *
     * Warning: your namespace MUST terminate with '\\';
     *
     * Default: App\Http\Controllers\\Rest\\
     */
    'controller_namespace' => 'App\\Http\\Controllers\\Rest\\',

    /**
     * The namespace of your application's models
     *
     * Warning: your namespace MUST terminate with '\\';
     *
     * Default: App\\
     */
    'model_namespace' => 'App\\',

    /**
     * The directory that contains your rest controllers
     *
     * Warning: your directory MUST terminate with '/';
     *
     * Default: 'app/Http/Controllers/Rest'
     */
    'controller_directory' => 'app/Http/Controllers/Rest/',

    /**
     * The directory that contains your models
     *
     * Warning: your directory MUST terminate with '/';
     *
     * Default: 'app/'
     */
    'model_directory' => 'app/',

    /**
     * True if you controller names (before 'Controller') should be in plural form
     * Ex: 'UsersController' is plural, 'UserController' is singular
     *
     * Default: true
     */
    'controller_plural' => true,

    /**
     * Default temporal field if not overridden in your models
     * The temporal field is the field used with 'from' and 'to' request keywords (see request keywords from an to)
     *
     * Default: created_at
     */
    'default_temporal_field' => 'created_at',

    /**
     * HTTP methods
     */
    'http_methods' => [

        /**
         * HTTP method used to retrieve data
         *
         * Default: GET
         */
        'get' => 'GET',

        /**
         * HTTP method used to create data
         *
         * Default: POST
         */
        'create' => 'POST',

        /**
         * HTTP method used to update data
         *
         * Default: PUT
         */
        'update' => 'PUT',

        /**
         * HTTP method used to delete data
         *
         * Default: DELETE
         */
        'delete' => 'DELETE',
    ],

    /**
     * Keywords that can be used in an API call
     * Ex: .../api/users?where=...&orderby=...&limit=...&offset=...
     */
    'request_keywords' => [

        /**
         * Keyword used to load relations to the wanted data
         * Ex: (GET) .../api/users?with=posts
         *      => will load the users and their posts
         *
         * Default: with
         */
        'load_relations' => 'with',

        /**
         * Keyword used to limit the number of rows to retrieve
         * Ex: (GET) .../api/users?limit=5
         *      => will get only 5 users
         *
         * Default: limit
         */
        'limit' => 'limit',

        /**
         * Keyword used to skip a number of rows when 'limit' is used (like offset in SQL or skip() function with Eloquent)
         * Ex: (GET) .../api/users?limit=5&offset5
         *      => will get 5 users, skipping the first 5
         *
         * Default: offset
         */
        'offset' => 'offset',

        /**
         * Keyword used to order the result by a specific field
         * Ex: (GET) .../api/users?orderby=name
         *      => will retrieve users ordered by their name (ascending order by default)
         *
         * Default: orderby
         */
        'order_by' => 'orderby',

        /**
         * Keyword used to retrieve data that have been created (by default) after a specific date (included)
         * Ex: (GET) .../api/posts?from=2018-01-01
         *      => will retrieve all the posts created on the 2018-01-01 or after
         *
         * Default: from
         */
        'from' => 'from',

        /**
         * Keyword used to retrieve data that have been created (by default) before a specific date (included)
         * Ex: (GET) .../api/posts?from=2018-01-01
         *      => will retrieve all the posts created on the 2018-01-01 or before
         *
         * Default: to
         */
        'to' => 'to',

        /**
         * Keyword used if you want to select specific fields instead of all fields
         * Ex: (GET) .../api/users?select=id,name
         *      => will select only the id and the name.
         *
         * Warning: When using 'with' in your request, the id will automatically be selected,
         * since the relations can't be loaded without
         *
         * Default: select
         */
        'select_fields' => 'select',

        /**
         * Keyword used if you want to apply additional conditions to your request
         * Ex: (GET) .../api/users?where=email,LIKE,%gmail.com
         *      => will select every users that use a gmail address
         *
         * Default: where
         */
        'where' => 'where',

        /**
         * Keyword used if you want to perform a distinct request (like in SQL)
         * Ex: (GET) .../api/users?distinct=true
         *      => will perform a SELECT DISTINCT query (for SQL language)
         *
         * Default: distinct
         */
        'distinct' => 'distinct',

        /**
         * Keyword used to retrieve all the data from a table, even after a POST, PUT/PATCH and DELETE call
         * Ex: (DELETE) .../api/users/15?all=true&limit=10&offset=20
         *      => will delete user with id 15, then retrieve 10 users from the database
         *
         * Default: all
         */
        'get_all' => 'all',
    ],
];
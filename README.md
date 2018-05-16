

# Laravel Rest API

An easy to add a RESTful API to your Laravel's project

## Usage example
The REST API is easy to use since it respects Laravel's name  conventions and RESTful API's standards.

 For example, imagine you have a `posts` table in your database, then you'll want to get all the posts from your API, running on the server `myserver.com`. To do that, you'll simply have to request the following URL:
 ```http
 http://myserver.com/api/posts
```

If you only want the post with id 4:
```http
http://myserver.com/api/posts/4
```

If you want the comments of the 4th post:

```http
http://myserver.com/api/posts/4/comments
```

_Remark: In this case, you need to specify that the `posts` table (the `Post` model) has a relation named `comments`. We will speak about that later._

Finally, if you want the 3rd comment of the 4th post:

```http
http://myserver.com/api/posts/4/comments/3
```

Of course, it's also possible to add (`POST`), update (`PUT/PATCH`) and delete (`DELETE`) data. See the documentation.

## Installation
Download the package:
```bash
composer require robinmarechal/laravel-rest-api
```
#### Laravel >= 5.5
The package is using Laravel's auto discovery feature. The Service Provider is therefore automagically loaded.

#### Laravel <= 5.4
Add the `RestApiServiceProvider` to your app's provider list in the file `config/app.php`:

```php
'providers' => [
    //...
    RobinMarechal\RestApi\RestApiServiceProvider::class,
]
```

#### Final Step
Make your parent controller class (by default it's `app/Http/Controllers/Controller.php`) use the `RobinMarechal\RestApi\HandleRestRequest` trait.

```php
<?php

namespace App\Http\Controllers;

// ...
use RobinMarechal\RestApi\Rest\HandleRestRequest;

class Controller extends BaseController
{
	// ...
    use HandleRestRequest;
}
```


If you want to create custom routes with custom handlings, you may want to access the request in your child controllers. Therefore, you should add a constructor to the `Controller` parent class like this:

```php
<?php

namespace App\Http\Controllers;

// ...
use RobinMarechal\RestApi\Rest\HandleRestRequest;

class Controller extends BaseController
{
	// ...
    use HandleRestRequest;
    
    protected $request;

    function __construct(\Illuminate\Http\Request $request)
    {
        $this->request = $request;
    }
}
```

##  Prepare your(self) API
Once you've installed the package, you just need to create the required structure.
For each of your database's tables (except the pivots), you need to create a **Controller** and a **Model**.

The API controllers are located in `app/Http/Controllers/Rest/`, and the models are in `app/`.

The controller's names should be you your table's names, in camel case and in plural form.
The model's names should be your table's name, in camel case and in singular form.

For example, if you have a `posts` table, you should have a **controller** named `PostsController` in `app/Http/Controllers/Rest/`, and a **model** named `Post` in `app/`.

_Of course, all of these are customizable, you only need to publish the `rest` config using `php artisan vendor:publish` command to override defaults_

### But there's a command for this!

I've created an _Artisan_'s command that helps us to create these controllers and models.
To create the controller and the model for a database table, simply execute the following command:
```bash
php artisan api:table <table_name|model_name> 
                            [--F|fillable=]
                            [--H|hidden=]
                            [--D|dates=]
                            [--T|timestamps=] 
                            [--softDeletes] 
                            [--R|relations=]
                            [--M|migrations]
``` 

- `--fillable=` (or `-F`) option takes a list of fields, seperated by a comma (`,`), that represents the `fillable`' field value of your model.
- `--hidden=` (or `-H`) option takes a list of fields, seperated by a comma (`,`), that represents the `hidden`' field value of your model.
- `--dates=` (or `-D`) option takes a list of fields, seperated by a comma (`,`), that represents the `date`' field value of your model.
- `--timestamps=` (or `-T`) option is a boolean (`1|yes|true|` or `0|no|false`) that represents the `timestamps`' field value of your model.
- `--softDeletes=` option is a boolean (`1|yes|true|` or `0|no|false`). If the value is `1`, `yes` or `true`, you model will use softDeletes.
- `--relations=` (or `-R`) option is a list of relations, also seperated by a comma (`,`).
A "relation" is a string, that can take 2 forms:
    - `<hasMany|hasOne|belongsTo|belongsToMany> <related_model> [<function_name>]`, where `function_name` allows you to define a custom function name.
    - `<function_name>`. This form creates an empty function. This can be useful if you want, for example, to use another relation method than the supported ones here.
- `--migrations` (or `-M`) option to create a migration with your model controller.

_**Note**:  If you don't specify the function name option (`<function_name>`), the name will be your related model (`<related_model>`) in snake case, in plural form for `hasMany` and `belongsToMany` methods, and in singular form for `hasOne` and `belongsTo` methods._

#### Example:

```bash
php artisan api:table posts --fillable=title,content,user_id --relations="belongsTo User author, hasMany Comment"
```
<u>**Important**: Don't forget the quotes for the `--relations` options!</u>

This example will create the following files:

##### Controller:
`app/Http/Controllers/Rest/PostsController.php`
```php
<?php

namespace App\Http\Controllers\Rest;

class PostsController extends ApiController
{

}
```
##### Model:
`app/Post.php`

```php
<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    public $timestamps = true;
    protected $fillable = ['title', 'content', 'user_id'];

    
    public function author(){
        return $this->belongsTo('App\User');
    }


    public function comments(){
        return $this->hasMany('App\Comment');
    }
}
```

Once you've done the same for `users` and `comments` table, you're ready to use your REST API. For example, you can call these URLs:

```http
http://myserver.com/api/users
http://myserver.com/api/posts
http://myserver.com/api/comments

http://myserver.com/api/posts/4
http://myserver.com/api/posts/4/author
http://myserver.com/api/posts/4/comments
http://myserver.com/api/posts/4/comments/3
```

# But that's not the end!

The strenght of this package is that it allows you to write more complex API calls to retrieve specific data, even with a `POST`, `PUT`/`PATCH` and `DELETE` request. See the documentation.

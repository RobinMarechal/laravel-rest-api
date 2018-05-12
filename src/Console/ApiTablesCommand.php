<?php

namespace RobinMarechal\RestApi\Commands;

use Illuminate\Console\Command;
use League\Flysystem\FileExistsException;

/**
 * Class DeletePostsCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands\Api
 */
class ApiTablesCommand extends Command
{
    use GenerateFileTemplates;

    const CONSOLE_RED = "\e[31m";
    const CONSOLE_GREEN = "\e[32m";
    const CONSOLE_DEFAULT = "\e[0m";
    const CONSOLE_BOLD = "\e[1m";

    /**
     * The models' directory path
     * @var string
     */
    public $modelsBasePath;

    /**
     * The models' namespace
     * @var string
     */
    public $modelsNamespace;

    /**
     * The controllers' directory path
     * @var string
     */
    public $controllersBasePath;

    /**
     * The controllers' namespace
     * @var string
     */
    public $controllersNamespace;

    /**
     * If a migration file should be created as well
     * @var boolean
     */
    public $withMigration;

    /**
     * If the model should use timestamp fields
     * @var bool
     */
    public $timestamps = true;

    /**
     * Additional date fields
     * @var array
     */
    public $dates = [];

    /**
     * Hidden fields
     * @var array
     */
    public $hidden = [];

    /**
     * If the model should use soft deletes
     * @var bool
     */
    public $softDeletes = false;

    /**
     * the table's fillable fields
     * @var array
     */
    public $fillables = [];

    /**
     * The parsed relation functions
     * @var array
     */
    public $parsedRelations = [];

    /**
     * The model class' name
     * @var string
     */
    public $modelName;

    /**
     * The controller class' name
     * @var string
     */
    public $controllerName;

    /**
     * The table's name
     * @var string
     */
    protected $tableName;

    /**
     * The relation strings (not parsed)
     * @var array
     */
    protected $relations = [];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "api:table {table} 
                                    {--F|fillables=} 
                                    {--R|relations=} 
                                    {--T|timestamps=} 
                                    {--H|hidden=} 
                                    {--softDeletes} 
                                    {--D|dates=} 
                                    {--M|migrations}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Setup a table (and related model and controller) for REST API requests";

    /**
     * Relation methods that exists in Eloquent and that are allowed here
     * @var array [`method_name` => `bool`]
     *            where `bool` is `true` if the relation function (not the relation method) should be plural, `false`
     *            otherwise
     */
    protected $allowedRelationMethods = [
        'hasOne' => false,
        'belongsTo' => false,
        'hasMany' => true,
        'belongsToMany' => true,
    ];


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setUpPathAndNamespaces();
        $this->parseArgs();
        $this->createMigration();
        $this->parseRelations();
        $this->createModel();
        $this->createController();
    }


    protected function setUpPathAndNamespaces()
    {
        $this->controllersBasePath = base_path(config('rest.controller_directory'));
        $this->modelsBasePath = base_path(config('rest.model_directory'));

        $this->controllersNamespace = config('rest.controller_namespace');
        $this->modelsNamespace = config('rest.model_namespace');

        $this->controllersBasePath = substr($this->controllersBasePath, 0, -1);
        $this->modelsBasePath = substr($this->modelsBasePath, 0, -1);

        $this->controllersNamespace = substr($this->controllersNamespace, 0, -1);
        $this->modelsNamespace = substr($this->modelsNamespace, 0, -1);
    }


    protected function parseArgs()
    {
        // plural and snake in case the user entered a model name instead of a table name

        // Forced to use str_plural(str_singular(...)) because of a bug pluralizing 'menus' as 'menuses'
        $this->tableName = str_plural(str_singular(snake_case($this->argument('table'))));
        $this->tableName = preg_replace('/__+/', '_', $this->tableName);

        $tmpUpperTableName = substr(camel_case("a_$this->tableName"), 1);
        // Forced to use str_plural(str_singular(...)) because of a bug pluralizing 'menus' as 'menuses'
        $tmpControllerPrefix = config('rest.controller_plural') ? str_plural(str_singular($tmpUpperTableName)) : str_singular($tmpUpperTableName);

        // model's name is singular table's name with first letter uppercase
        $this->modelName = str_singular($tmpUpperTableName);

        // controller's name is table's name with first letter uppercase and followed by "Controller"
        $this->controllerName = "{$tmpControllerPrefix}Controller";
        
        // "field1,field2,..." => [field1, field2,...]
        $this->fillables = $this->parseArrayOption('fillables');

        // "relation str 1, relation str 2" => [relation str 1, relation str 2];
        $this->relations = $this->parseArrayOption('relations');

        // "field1,field2,..." => [field1, field2,...]
        $this->dates = $this->parseArrayOption('dates');

        // "field1,field2,..." => [field1, field2,...]
        $this->hidden = $this->parseArrayOption('hidden');

        // true = 'true|1|yes'
        $this->timestamps = $this->parseBooleanOption('timestamps');

        // true = 'true|1|yes'
        $this->softDeletes = $this->parseBooleanOption('softDeletes');

        // --M|migrations
        $this->withMigration = $this->option('migrations');
    }


    protected function createMigration()
    {
        if ($this->withMigration) {
            $migrationName = "create_{$this->tableName}_table";
            $this->call("make:migration", ['name' => $migrationName]);
        }
    }


    protected function parseRelations()
    {
        foreach ($this->relations as $relation) {
            $relationParts = explode(' ', trim($relation));
            $this->parsedRelations[] = $this->parseRelation($relationParts);
        }
    }


    protected function parseRelation(array $parts)
    {
        // --relations=hasMany Post
        // --relations=hasMany Post posts
        // --relations=posts

        $method = null;
        $relatedModel = null;
        $funcName = null;

        if (isset($parts[0])) {
            $method = $parts[0]; // hasMany, belongsTo...

            if (isset($parts[1])) {
                $relatedModel = $parts[1]; // User, Post...

                if (isset($parts[2])) {
                    $funcName = $parts[2]; // user, posts...
                }
                else {
                    $funcName = snake_case($relatedModel);

                    if (!array_key_exists($method, $this->allowedRelationMethods)) {
                        throw new \Exception("The relation method '$method' isn't supported.");
                    }
                    if ($this->allowedRelationMethods[$method] === true) {
                        $funcName = str_plural($funcName);
                    }
                }
            }
            else {
                // If only one param, then we only create then we only create an empty function
                $funcName = $method;
            }
        }

        return compact('method', 'relatedModel', 'funcName');
    }


    protected function createModel()
    {
        if (!is_dir($this->modelsBasePath)) {
            mkdir($this->modelsBasePath);
        }

        $modelFullPath = "$this->modelsBasePath/$this->modelName.php";
        $modelFileContent = $this->compileModelTemplate($this);

        if ($this->createFile($modelFullPath, $modelFileContent)) {
            $this->printSuccess("Created model: '$this->modelsNamespace\\$this->modelName' ('$modelFullPath')\n");
        }
    }


    protected function createFile($path, $content)
    {
        if (is_file($path)) {
            $this->printError("ERROR: The file '$path' already exists!\n");

            return false;
        }

        $resource = fopen($path, 'w');
        fputs($resource, $content);
        fclose($resource);

        return true;
    }


    protected function createController()
    {
        if (!is_dir($this->controllersBasePath)) {
            mkdir($this->controllersBasePath);
        }

        $controllerFullPath = "$this->controllersBasePath/$this->controllerName.php";
        $controllerFileContent = $this->compileControllerTemplate($this);

        if ($this->createFile($controllerFullPath, $controllerFileContent)) {
            $this->printSuccess("Created controller: '$this->controllersNamespace\\$this->controllerName' ('$controllerFullPath')\n");
        }
    }


    public function printError($msg)
    {
        print(self::CONSOLE_BOLD . self::CONSOLE_RED . $msg . self::CONSOLE_DEFAULT);
    }



    public function printSuccess($msg)
    {
        print(self::CONSOLE_BOLD . self::CONSOLE_GREEN . $msg . self::CONSOLE_DEFAULT);
    }

    protected function parseBooleanOption($optionName)
    {
        $value = strtolower($this->option($optionName));

        return $value === 'true' || $value == 1 || $value === 'yes';
    }


    protected function parseArrayOption($optionName)
    {
        $optionValue = $this->option($optionName);
        if (strlen($optionValue) === 0)
            return [];

        return array_map(function ($item) {
            return trim($item);
        }, explode(',', $optionValue));
    }
}
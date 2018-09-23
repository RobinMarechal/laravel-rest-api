<?php

namespace RobinMarechal\RestApi\Commands;

use Illuminate\Console\Command;

/**
 * Class DeletePostsCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands\Api
 */
class RestApiInitCommand extends Command
{
    use GenerateFileTemplates;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "rest:init";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Init the rest API project. Basically, create the parent rest controller.";

    /**
     * The name of the controllers' parent
     * @var string
     */
    public $parentControllerName;

    /**
     * The namespace of the controllers' parent
     * @var string
     */
    public $parentControllerNamespace;

    /**
     * The directory of the controllers' parent
     * @var string
     */
    public $parentControllerBasePath;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->registerConfig();
        $this->createParentController();
    }

    protected function registerConfig()
    {
        $this->parentControllerName = config('rest.rest_parent_controller_name');
        $this->parentControllerNamespace = config('rest.rest_parent_controller_namespace') ?: config('rest.rest_controllers_namespace');
        $this->parentControllerBasePath = config('rest.rest_parent_controller_directory') ?: config('rest.rest_controllers_directory');
        $this->parentControllerBasePath = base_path($this->parentControllerBasePath);

        CommandHelpers::removeLastChar(
            $this->parentControllerNamespace,
            $this->parentControllerBasePath
        );
    }

    protected function createParentController()
    {
        if (!is_dir($this->parentControllerBasePath)) {
            mkdir($this->parentControllerBasePath, 0777, true);
        }

        $parentControllerFullPath = "$this->parentControllerBasePath/$this->parentControllerName.php";
        $parentControllerFileContent = $this->compileParentControllerTemplate($this);

        if (CommandHelpers::createFile($parentControllerFullPath, $parentControllerFileContent)) {
            CommandHelpers::printSuccess("Created controller: '$this->parentControllerNamespace\\$this->parentControllerName' ('$parentControllerFullPath')\n");
        }
    }
}
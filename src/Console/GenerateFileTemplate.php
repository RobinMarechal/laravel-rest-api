<?php

namespace RobinMarechal\RestApi\Commands;

use RobinMarechal\RestApi\Http\Helper;

trait GenerateFileTemplates
{
    protected function compileControllerTemplate(RestApiTablesCommand $commandObj)
    {
        $template = $this->getControllerTemplate();
        $template = str_replace("{{rest_controllers_namespace}}", $commandObj->controllersNamespace, $template);
        $template = str_replace("{{controller_name}}", $commandObj->controllerName, $template);
        $template = str_replace("{{parent_controller_name}}", $commandObj->parentControllerName, $template);

        $import = "";
        if ($commandObj->parentControllerNamespace != $commandObj->controllersNamespace) {
            $import = "\n\nuse $commandObj->parentControllerNamespace;";
        }
        $template = str_replace("{{use_parent_controller_namespace}}", $import, $template);

        return $template;
    }

    protected function compileParentControllerTemplate(RestApiInitCommand $commandObj)
    {
        $template = $this->getParentControllerTemplate();
        $template = str_replace("{{rest_controllers_namespace}}", $commandObj->parentControllerNamespace, $template);
        $template = str_replace("{{parent_controller_name}}", $commandObj->parentControllerName, $template);

        return $template;
    }


    protected function compileModelTemplate(RestApiTablesCommand $commandObj)
    {
        $template = $this->getModelTemplate();
        $template = $this->compileModelNamespace($template, $commandObj);
        $template = $this->compileModelName($template, $commandObj);
        $template = $this->compileModelAttributes($template, $commandObj);
        $template = $this->compileModelRelations($template, $commandObj);

        return $template;
    }


    protected function compileModelAttributes($template, RestApiTablesCommand $commandObj)
    {
        $template = $this->compileModelFillable($template, $commandObj);
        $template = $this->compileModelHidden($template, $commandObj);
        $template = $this->compileModelDates($template, $commandObj);
        $template = $this->compileModelTimestamps($template, $commandObj);
        $template = $this->compileModelSoftDeletes($template, $commandObj);

        return $template;
    }


    protected function compileModelRelationTemplate($funcName, $relatedModel = null, $method = null)
    {
        $template = $this->getModelRelationTemplate();
        $template = str_replace('{{function_name}}', $funcName, $template);
        $template = str_replace('{{relation_return}}', $this->compileModelRelationReturn($relatedModel, $method), $template);

        return $template;
    }


    protected function compileModelRelationReturn($relatedModel = null, $method = null)
    {
        return $relatedModel && $method ? "return \$this->$method('App\\$relatedModel');" : '';
    }


    protected function compileModelNamespace($template, RestApiTablesCommand $commandObj)
    {
        return str_replace('{{model_namespace}}', $commandObj->modelsNamespace, $template);
    }


    protected function compileModelName($template, RestApiTablesCommand $commandObj)
    {
        return str_replace('{{model_name}}', $commandObj->modelName, $template);
    }


    protected function compileModelArrayAttribute($template, $toReplace, array $array, $varname, $modifier = 'public')
    {
        if (!isset($array[0])) {
            return str_replace($toReplace, '', $template);
        }
        $strArray = "['" . join("', '", $array) . "']";
        $line = "\t$modifier \$$varname = $strArray;";
        $template = str_replace($toReplace, $line, $template);

        return $template;
    }


    protected function compileModelFillable($template, RestApiTablesCommand $commandObj)
    {
        return $this->compileModelArrayAttribute($template, '{{fillable}}', $commandObj->fillable, 'fillable', 'protected');
    }


    protected function compileModelHidden($template, RestApiTablesCommand $commandObj)
    {
        return $this->compileModelArrayAttribute($template, '{{hidden}}', $commandObj->hidden, 'hidden', 'protected');
    }


    protected function compileModelDates($template, RestApiTablesCommand $commandObj)
    {
        return $this->compileModelArrayAttribute($template, '{{dates}}', $commandObj->dates, 'dates', 'protected');
    }


    protected function compileModelTimestamps($template, RestApiTablesCommand $commandObj)
    {
        if (!$commandObj->timestamps) {
            return str_replace('{{timestamps}}', "\tpublic \$timestamps = false;\n", $template);
        }

        return str_replace('{{timestamps}}', '', $template);
    }


    protected function compileModelSoftDeletes($template, RestApiTablesCommand $commandObj)
    {
        if (!$commandObj->softDeletes) {
            $template = str_replace('{{import_softdeletes}}', '', $template);

            return str_replace('{{softdeletes}}', '', $template);
        }
        $template = str_replace('{{import_softdeletes}}', "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n", $template);

        return str_replace('{{softdeletes}}', "\tuse SoftDeletes;\n", $template);
    }


    protected function compileModelRelations($template, RestApiTablesCommand $commandObj)
    {
        foreach ($commandObj->parsedRelations as $attrs) {
            $method = Helper::arrayGetOrNull($attrs, 'method');
            $relatedModel = Helper::arrayGetOrNull($attrs, 'relatedModel');
            $funcName = Helper::arrayGetOrNull($attrs, 'funcName');
            $compiledRelationFunction = $this->compileModelRelationTemplate($funcName, $relatedModel, $method);
            $template = str_replace('{{model_relation}}', $compiledRelationFunction . "\n\n{{model_relation}}", $template);
        }

        $template = str_replace("\n\n{{model_relation}}", '', $template);
        $template = str_replace("{{model_relation}}", '', $template);

        return $template;
    }


    private function getModelTemplate()
    {
        return
            '<?php
namespace {{model_namespace}};

use Illuminate\Database\Eloquent\Model;
{{import_softdeletes}}

class {{model_name}} extends Model
{
{{softdeletes}}{{timestamps}}{{dates}}{{fillable}}{{hidden}}
{{model_relation}}
}';
    }


    private function getControllerTemplate()
    {
        return
            "<?php
namespace {{rest_controllers_namespace}};{{use_parent_controller_namespace}}

class {{controller_name}} extends {{parent_controller_name}}
{

}";
    }


    private function getModelRelationTemplate()
    {
        return "
\tpublic function {{function_name}}(){
\t\t{{relation_return}}
\t}";
    }

    private function getParentControllerTemplate()
    {
        return '<?php
namespace {{rest_controllers_namespace}};

use App\Http\Controllers\Controller;
use RobinMarechal\RestApi\Rest\HandleRestRequest;

class {{parent_controller_name}} extends Controller
{
    use HandleRestRequest;

    protected $request;

    function __construct(\Illuminate\Http\Request $request)
    {
        $this->request = $request;
    }
}';
    }
}


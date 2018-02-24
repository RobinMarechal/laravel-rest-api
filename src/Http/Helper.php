<?php
namespace RobinMarechal\RestApi\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Helper
{
    public static function userWantsAll(Request $request)
    {
        $allStr = config('rest.request_rewords.get_all');
        return $request->has($allStr) && $request->get($allStr) == "true";
    }

    public static function getRelatedModelClassName(Controller $controller)
    {
        $fullName = get_class($controller);
        $reducedName = str_replace('Controller', '', array_last(explode('\\', $fullName)));
        return config('rest.model_namespace') . str_singular($reducedName);
    }

    public static function arrayGetOrNull(array $array, $key)
    {
        return is_numeric($key) && isset($array[$key]) || array_key_exists($key, $array) ? $array[$key] : null;
    }
}

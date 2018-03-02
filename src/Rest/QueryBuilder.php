<?php
/**
 * Created by PhpStorm.
 * User: robin
 * Date: 24/02/18
 * Time: 15:47
 */

namespace RobinMarechal\RestApi\Rest;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use function explode;
use function strpos;

/**
 * Created by PhpStorm.
 * User: Utilisateur
 * Date: 22/09/2017
 * Time: 19:35
 */
class QueryBuilder
{
    public $class;

    public $request;

    public $cfg;

    private $query;


    function __construct(&$query, $class)
    {
        $this->query = $query;
        $this->class = $class;
        $this->request = Request::instance();
    }


    public static function getPreparedQuery($class)
    {
        $query = $class::query();

        return self::buildQuery($query, $class);
    }


    public static function buildQuery(&$query, $class)
    {
        $instance = new QueryBuilder($query, $class);
        $instance->build();

        return $instance->getBuiltQuery();
    }


    protected function build()
    {
        $this->applyUrlParams();
    }


    // ?....&limit=..&offset=...

    protected function applyUrlParams()
    {
        $this->applyRelationsParameters();
        $this->applyLimitingParameters();
        $this->applyOrderingParameters();
        $this->applyTemporalParameters();
        $this->applyFieldSelectingParameters();
        $this->applyWhereParameter();
        $this->applyDistinct();
    }


    // ?....&orderby=..&order=...

    public function applyRelationsParameters()
    {
        $withKeyword = config('rest.request_keywords.load_relations');

        if ($this->request->filled($withKeyword)) {
            $with = $this->request->get($withKeyword);
            if ($with == "all" || $with == '*') {
                $this->query->withAll();
            }
            else {
                $withArr = explode(";", $this->request->get($withKeyword));
                $this->query->with($withArr);
            }
        }
    }


    // ?....&from=..&to=...

    public function applyLimitingParameters()
    {
        $limitKeyword = config('rest.request_keywords.limit');
        $offsetKeyword = config('rest.request_keywords.offset');

        if ($this->request->filled($limitKeyword)) {
            $limit = $this->request->get($limitKeyword);

            if (strpos($limit, ',')) {
                $arr = explode(',', $limit);
                $this->query->take($arr[0]);
                $this->query->skip($arr[1]);
            }
            else {
                $this->query->take($limit);
            }
        }

        if ($this->request->filled($offsetKeyword)) {
            $this->query->skip($this->request->get($offsetKeyword));
        }
    }


    // ?....&with=rel1,rel2,rel3.rel3rel...

    public function applyOrderingParameters()
    {
        $orderByKeyword = config('rest.request_keywords.order_by');
        $orderKeyword = config('rest.request_keywords.order');

        if ($this->request->filled($orderByKeyword)) {
            $orderByArray = $this->request->get($orderByKeyword);
            $orderArray = $this->request->get($orderKeyword);

            if(!is_array($orderByArray)){
                $orderByArray = [$orderByArray];
            }

            if (!$orderArray) {
                $orderArray = [];
            }
            else if (!is_array($orderArray)) {
                $orderArray = [$orderArray];
            }

            for ($i = 0; $i < count($orderByArray); $i++) {

                $orderBy = $orderByArray[$i];
                $order = isset($orderArray[$i]) ? $orderArray[$i] : 'ASC';

                if (str_contains($orderBy, ',')) {
                    $arr = explode(',', $orderBy);
                    $orderBy = $arr[0];
                    $order = $arr[1];
                }

                $this->query->orderBy($orderBy, $order);
            }
        }
    }


    public function applyTemporalParameters()
    {
        $defaultTempField = config('rest.default_temporal_field');
        $fromKeyword = config('rest.request_keywords.from');
        $toKeyword = config('rest.request_keywords.to');

        $modelClassName = '\\' . $this->class;
        $tmpModelInstance = new $modelClassName();
        $temporalField = ($tmpModelInstance->temporalField ?:
            ($tmpModelInstance->timestamps ? $defaultTempField :
                (isset($tmpModelInstance->dates[0]) ? $tmpModelInstance->dates[0] : null)));

        if ($temporalField) {
            $from = $this->request->filled($fromKeyword) ? Carbon::parse($this->request->get($fromKeyword)) : null;
            $to = $this->request->filled($toKeyword) ? Carbon::parse($this->request->get($toKeyword)) : null;

            if (isset($from) && isset($to)) {
                $this->query->whereBetween($temporalField, [$from, $to]);
            }
            else if ($this->request->filled($fromKeyword)) {
                $this->query->where($temporalField, '>=', $from);
            }
            else if ($this->request->filled($toKeyword)) {
                $this->query->where($temporalField, '<=', $to);
            }
        }
    }


    public function applyFieldSelectingParameters()
    {
        $selectKeyword = config('rest.request_keywords.select_fields');

        if ($this->request->filled($selectKeyword)) {
            $fields = $this->request->get($selectKeyword);
            $arr = $this->getRawArrayFromString($fields);
            $selectStr = join(', ', $arr);
            $this->query->selectRaw($selectStr);
        }
    }


    protected function getRawArrayFromString($str)
    {
        $withKeyword = config('rest.request_keywords.with');

        $sep = '+';
        $str = preg_replace('/\( \s+/', '', $str);
        $str = preg_replace('/\s+ \)/', '', $str);
        $str = preg_replace('/,\s+/', ',', $str);
        $str = preg_replace('/\s+,/', ',', $str);
        $str = preg_replace('/,\s+,/', ',', $str);
        $len = strlen($str);
        $quotes = 0;

        for ($i = 0; $i < $len; $i++) {
            $c = $str[$i];

            if ($c == ';' && $quotes == 0) {
                $str[$i] = $sep;
                continue;
            }
            else if ($c == '"' && $c == 0) {
                $quotes++;
                continue;
            }
            else if ($c == '"' && $c > 0) {
                $quotes--;
            }

            if ($quotes < 0) {
                throw new \Exception("Error in URL query parameter");
            }
        }

        $arr = explode($sep, $str);

        for ($i = 0; $i < count($arr); $i++) {
            if (strpos($arr[$i], '=')) {
                $tmp = explode('=', $arr[$i]);
                $f = $tmp[1];
                $as = $tmp[0];
                $arr[$i] = "$f AS $as";
            }
            if (preg_match('/[a-z\d_]+\(((\*|\w+)|[a-z\d_]+(,(([a-z\d_]+)|(".*")))*)\)(\s*as\s+\w+)?/i', $arr[$i])) {
                $arr[$i] = DB::raw($arr[$i]);
            }
        }

        // If 'with' is called, 'id' must be selected
        if ($this->request->has($withKeyword) && !array_has($arr, 'id')) {
            $arr[] = 'id';
        }

        return $arr;
    }


    public function applyWhereParameter()
    {
        $whereKeyword = config('rest.request_keywords.where');

        if ($this->request->filled($whereKeyword)) {
            $wheres = $this->request->get($whereKeyword);

            if(!is_array($wheres)){
                $wheres = [$wheres];
            }

            foreach ($wheres as $where) {
                $params = explode(',', $where);
                if (isset($params[2])) {
                    $this->query->where($params[0], $params[1], $params[2]);
                }
                else if (isset($params[1])) {
                    $this->query->where($params[0], $params[1]);
                }
                else if (!isset($params[0])) {
                    throw new \Exception("Error in 'where' parameter.");
                }
            }
        }
    }


    public function applyDistinct()
    {
        $distinctKeyword = config('rest.request_keywords.distinct');
        if ($this->request->filled($distinctKeyword) && $this->request->get($distinctKeyword) == true) {
            $this->query->distinct();
        }
    }


    protected function getBuiltQuery()
    {
        return $this->query;
    }
}
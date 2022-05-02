<?php

namespace App\Repository\Elasticsearch\Services;

use App\Repository\Elasticsearch\Traits\ElasticSearchTrait;
use Closure;
use Elasticsearch\ClientBuilder;

class ElasticSearchService
{
    use ElasticSearchTrait;

    /**
     * @var ClientBuilder
     */
    public $clientBuilder;

    /**
     * @var array $params
     */
    public $params;

    /**
     * @var $not bool
     */
    private $not = false;

    /**
     * ElasticSearchAbstract
     *
     * @param ClientBuilder $clientBuilder
     */
    public function __construct(ClientBuilder $clientBuilder)
    {
        $this->clientBuilder = $clientBuilder::create()
            ->setHosts(config('elasticquent.config.hosts'))
            ->build();

        $this->params = [
            'index' => config('elasticquent.default_index'),
            'type' => config('elasticquent.default_type'),
        ];
    }

    /**
     * @param $name
     * @param $arguments
     * @return Closure
     */
    public function __call($name, $arguments): object
    {
        $this->not = str_contains(strtolower($name), 'not');
        $methodName = str_replace('Not', '', $name);

        return $this->$methodName(...$arguments);
    }

    /**
     * Set index params
     *
     * @param string|null $index
     * @param string|null $type
     * @return ElasticSearchService
     */
    public function whereIndexType(string $index, string $type = null): object
    {
        $this->params['index'] = $index;
        $this->params['type'] = $type ?? $this->params['type'];

        return $this;
    }

    /**
     * @param Closure $method
     * @param bool $must
     * @return object
     */
    public function shouldWhere(Closure $method): object
    {
        $data = $method($this->model::elastic());

        if (!empty($data->params['body'])) {
            $params['bool'] = $data->params['body']['query']['bool'];

            $this->setParams('should', $params, $this->not ? 'must_not' : 'must');
        }

        return $this;
    }

    /**
     * Should data v1
     *
     * @param array $data
     * @return object
     */
    public function should(array $data): object
    {
        foreach ($data as $item) {
            if (!empty($item)) {
                $result = [];
                foreach ($item as $condition) {
                    if (!empty($condition)) {
                        $result['bool'][$condition[1][0] !== '!' ? 'must' : 'must_not'][] = $this->setShouldData($condition);
                    }
                }
                $this->setParams('should', $result);
            }
        }

        return $this;
    }

    /**
     * @param string $field
     * @param mixed $value
     * @param string|null $condition
     * @param string|null $format
     * @return object
     */
    public function whereDate(string $field, $value, ?string $condition = null, ?string $format = 'yyyy-MM-dd'): object
    {
       $this->setParams(
           $this->not ? 'must_not' : 'must',
           [
               "range" => [
                   $field => $this->setWhereDate($value, $condition, $format)
               ]
           ]);

       return $this;
    }

    /**
     * @param bool $bool
     * @param Closure $method
     * @return object
     */
    public function when(bool $bool, Closure $method): object
    {
        if ($bool) {
            $method($this);
        }

        return $this;
    }

    /**
     * @param mixed $field
     * @param string|null $value
     * @param string|null $condition
     * @return object
     */
    public function where($field, ?string $value = null, ?string $condition = null): object
    {
        if ($field instanceof Closure) {
            $data = $field($this->model::elastic());

            if (!empty($data->params['body'])) {
                $params['bool'] = $data->params['body']['query']['bool'];

                $this->setParams($this->not ? 'must_not' : 'must', $params);
            }
        } else {
            $this->setParams(
                $value != '' ? ($this->not ? 'must_not' : 'must') : ($this->not ? 'must' : 'must_not'),
                is_null($condition)
                    ? ($value
                        ? [
                            "match" => $this->setWhere($field, $value)
                        ] : [
                            "exists" => [
                                "field" => $field
                            ]
                        ]) : [
                        "range" => [
                            $field => $this->setWhereDate($value, $condition, 'yyyyyyyyyyyy')
                        ]
                    ]);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param array $values
     * @return $this
     */
    public function whereIncludes(string $field, array $values): object
    {
        $this->setParams(
            $this->not ? 'must_not' : 'must',
            [
                "terms" => [
                    $field => $values
                ]
            ]
        );

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function whereContains(string $field, string $value): object
    {
        $this->setParams(
            $this->not ? 'must_not' : 'must',
            [
                "query_string" => [
                    "default_field" => $field,
                    "query" => '*' . $value . '*'
                ]
            ]
        );

        return $this;
    }

    /**
     * TODO Not finished
     *
     * @param string $model
     * @param Closure $method
     * @return object
     */
    public function whereHas(string $model, Closure $method): object
    {
        $data = $method($model::elastic())->params;

        $whereHasData = $this->build($data)->pluck('_id')->toArray();

        !count($whereHasData)
            ? $this->setParams(
            'must',
            [
                "match" => [
                    'id' => 0
                ]
            ])
            : $this->setParams(
            'must',
            [
                "terms" => [
                    'id' => $whereHasData
                ]
            ]);

        return $this;
    }

    /**
     * @param array|null $localParams
     * @return object
     */
    public function build(?array $localParams = null): object
    {
        $params = $localParams ?? $this->params;

        $params['body']['size'] = config('elasticquent.size');

        return collect($this->clientBuilder->search($params)['hits']['hits'])->pluck('_source');
    }
}

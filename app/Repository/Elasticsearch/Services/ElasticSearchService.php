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
     * Should data v2
     *
     * @param Closure $method
     * @return object
     */
    public function shouldWhere(Closure $method): object
    {
        $data = $method($this->model::elastic());

        if (!empty($data->params['body'])) {
            $params['bool'] = $data->params['body']['query']['bool'];

            $this->setParams('should', $params);
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
       $data = !is_null($condition)
            ? $condition === '>' ? [
                'gte' => $value,
                'format' => $format
            ] : [
                'lte' => $value,
                'format' => $format
            ]
            : (is_array($value) ? [
                'gte' => $value[0],
                'lte' => $value[1],
                'format' => $format
            ] : [
               'gte' => $value,
               'lte' => $value,
               'format' => $format
           ]);

       $this->setParams(
           'must',
           [
               "range" => [
                   $field => $data
               ]
           ]);

       return $this;
    }

    /**
     * @param string $field
     * @param mixed $value
     * @param string|null $condition
     * @param string|null $format
     * @return object
     */
    public function whereNotDate(string $field, $value, ?string $condition = null, ?string $format = 'yyyy-MM-dd'): object
    {
        $data = !is_null($condition)
            ? $condition === '>' ? [
                'gte' => $value,
                'format' => $format
            ] : [
                'lte' => $value,
                'format' => $format
            ]
            : (is_array($value) ? [
                'gte' => $value[0],
                'lte' => $value[1],
                'format' => $format
            ] : [
                'gte' => $value,
                'lte' => $value,
                'format' => $format
            ]);

        $this->setParams(
            'must_not',
            [
                "range" => [
                    $field => $data
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
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function where(string $field, $value): object
    {
        $this->setParams(
            'must',
            [
                "match" => [
                    is_string($value) ? $field . '.keyword' : $field => $value
                ]
            ]);

        return $this;
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function whereNot(string $field, $value): object
    {
        $this->setParams(
            'must_not',
            [
                "match" => [
                    is_string($value) ? $field . '.keyword' : $field => $value
                ]
            ]
        );

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
            'must',
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
     * @param array $values
     * @return $this
     */
    public function whereNotIncludes(string $field, array $values): object
    {
        $this->setParams(
            'must_not',
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
            'must',
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
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function whereNotContains(string $field, string $value): object
    {
        $this->setParams(
            'must_not',
            [
                "query_string" => [
                    "default_field" => $field,
                    "query" => !ctype_digit($value) ? '*' . $value . '*' : $value
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

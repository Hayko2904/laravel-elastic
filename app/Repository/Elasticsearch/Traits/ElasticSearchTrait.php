<?php

namespace App\Repository\Elasticsearch\Traits;

trait ElasticSearchTrait
{
    /**
     * @param string $condition
     * @param array $search
     * @param string|null $conditionWhenExists
     * @return object
     */
    private function setParams(string $condition, array $search, ?string $conditionWhenExists = null): object
    {
        !is_null($conditionWhenExists)
            ? (!is_null(array_key_first($this->setWhenExistsData($condition, $conditionWhenExists)))
                ? $this->params['body']["query"]["bool"][$conditionWhenExists][array_key_first($this->setWhenExistsData($condition, $conditionWhenExists))]['bool'][$condition][] = $search
                : $this->params['body']["query"]["bool"][$conditionWhenExists][]['bool'][$condition][] = $search)
            : $this->params['body']["query"]["bool"][$condition][] = $search;

        return $this;
    }

    /**
     * @param string $condition
     * @param string $conditionWhenExists
     * @return array
     */
    private function setWhenExistsData(string $condition, string $conditionWhenExists): array
    {
        $key = [];
        if (!empty($this->params['body']["query"]["bool"][$conditionWhenExists])) {
            $key = array_filter($this->params['body']["query"]["bool"][$conditionWhenExists], function ($val) use ($condition) {
                if (!empty($val['bool'])) {

                    return array_search($condition, array_keys($val['bool'])) !== false;
                }

                return false;
            }, ARRAY_FILTER_USE_BOTH);
        }

        return $key;
    }

    /**
     * @param array $data
     * @return array|array[]
     */
    private function setShouldData(array $data): array
    {
        $separators = [
            '=',
            '!=',
            'in',
            '!in',
            'LIKE',
            '!LIKE',
            'date',
            '!date'
        ];
        if (!in_array($data[1], $separators)) {
            return [];
        }

        switch ($data[1]):
            case '=' || '!=':
                return [
                    "match" => [
                        !is_numeric($data[2]) ? $data[0] . '.keyword' : $data[0]  => $data[2]
                    ]
                ];
            case 'in' || '!in':
                return [
                    "terms" => [
                        !is_numeric($data[2]) ? $data[0] . '.keyword' : $data[0]  => $data[2]
                    ]
                ];
            case 'LIKE' || '!LIKE':
                return [
                    "query_string" => [
                        "default_field" => $data[0],
                        "query" => !ctype_digit($data[2]) ? '*' . $data[2] . '*' : $data[2]
                    ]
                ];
            case 'date' || '!date':
                return [
                    "range" => [
                        $data[0] => [
                            'gte' => $data[2],
                            'lte' => $data[2],
                            'format' => !empty($data[3]) ? $data[3] : 'yyyy-MM-dd'
                        ]
                    ]
                ];
            default:
                return [];
        endswitch;
    }

    /**
     * @param mixed $value
     * @param string|null $condition
     * @param string|null $format
     * @return array
     */
    private function setWhereDate($value, ?string $condition = null, ?string $format = 'yyyy-MM-dd'): array
    {
        return !is_null($condition)
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
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return array|string[]
     */
    private function setWhere(string $field, $value): array
    {
        return is_string($value) && !ctype_digit($value)
            ? (strtoupper($value) !== 'NULL'
                ? [
                    $field . '.keyword' => $value
                ] : [
                    $field => strtoupper($value)
                ])
            : [
                $field => $value
            ];
    }
}

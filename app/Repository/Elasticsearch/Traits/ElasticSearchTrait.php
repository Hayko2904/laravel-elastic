<?php

namespace App\Repository\Elasticsearch\Traits;

trait ElasticSearchTrait
{
    /**
     * @param string $condition
     * @param array $search
     * @return object
     */
    protected function setParams(string $condition, array $search): object
    {
        $this->params['body']["query"]["bool"][$condition][] = $search;

        return $this;
    }

    /**
     * @param array $data
     * @return array|array[]
     */
    public function setShouldData(array $data): array
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
}

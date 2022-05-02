<?php

namespace App\Repository\Elasticsearch\Services;

final class EsModelService extends ElasticSearchService
{
    /**
     * @var object $model
     */
    public $model;

    /**
     * @return string
     */
    public function setMapping(): string
    {
        try {
            $this->clientBuilder->indices()->delete(['index' => (new $this->model)->getIndexName()]);
        } catch (\Exception $e) {
            echo $e . PHP_EOL;
        }
        if (!empty((new $this->model)->mapping())) {
            $params = [
                'index' => (new $this->model)->getIndexName(),

            ];
            $params['body']['mappings'] = (new $this->model)->mapping();
            $this->clientBuilder->indices()->create($params);

            echo 'success' . PHP_EOL;
        }

        return 'mapping is set' . PHP_EOL;
    }

    /**
     * @return mixed
     */
    public function toBuilder(): object
    {
        $data = collect($this->clientBuilder->search($this->params)['hits']['hits'])->pluck('_source')->toArray();

        return (new $this->model)->query()
            ->whereIn('id', array_column($data, 'id'));
    }
}

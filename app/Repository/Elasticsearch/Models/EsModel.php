<?php

namespace App\Repository\Elasticsearch\Models;

use App\Repository\Elasticsearch\Services\EsModelService;
use Elasticquent\ElasticquentTrait;
use Illuminate\Database\Eloquent\Model;

abstract class EsModel extends Model
{
    use ElasticquentTrait;

    /**
     * @var EsModelService
     */
    public $esModelService;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->esModelService = app(EsModelService::class);
    }

    /**
     * @return EsModelService
     */
    public static function elastic(): object
    {
        return (new static)->esModelService();
    }

    /**
     * return EsModelService
     *
     * @return EsModelService
     */
    public function esModelService(): EsModelService
    {
        $this->esModelService->params = [
            'index' => $this->getIndexName(),
            'type' => $this->getTypeName(),
        ];
        $this->esModelService->model = $this->setThis();

        return $this->esModelService;
    }

    /**
     * Model boot
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $model->addToIndex();
            $model->refresh();
        });

        static::updated(function ($model) {
            $model->removeFromIndex();
            $model->addToIndex();
            $model->refresh();
        });

        static::deleted(function ($model) {
            $model->removeFromIndex();
            $model->refresh();
        });
    }

    /**
     * return index name
     *
     * @return string
     */
    abstract public function getIndexName(): string;

    /**
     * return type name
     *
     * @return string
     */
    abstract public function getTypeName(): string;

    /**
     * return path model string
     *
     * @return mixed
     */
    abstract public function setThis(): string;

    /**
     * return mapping
     *
     * @return array
     */
    abstract public function mapping(): array;
}

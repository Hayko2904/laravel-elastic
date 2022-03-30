<?php

namespace App\Models;

use Elasticquent\ElasticquentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory, ElasticquentTrait;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'address',
    ];

    protected $table = 'persons';

    protected $mappingProperties = array(
        'first_name' => [
            'type' => 'text',
            "analyzer" => "standard",
        ],
        'last_name' => [
            'type' => 'text',
            "analyzer" => "standard",
        ],
        'phone' => [
            'type' => 'text',
            "analyzer" => "standard",
        ],
        'address' => [
            'type' => 'text',
            "analyzer" => "standard",
        ],
    );
}

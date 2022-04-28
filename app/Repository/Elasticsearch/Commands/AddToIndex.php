<?php

namespace App\Repository\Elasticsearch\Commands;

use Illuminate\Console\Command;

class AddToIndex extends Command
{
    const REFRESH = 'refresh';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add-to:index {model} {param?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add data to Elasticsearch index';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $model = 'App\Models\\' . $this->argument('model');
        $indexModel = new $model();
        echo $indexModel::elastic()->setMapping();

        if (!is_null($this->argument('param')) && $this->argument('param') === self::REFRESH) {
            $indexModel->refresh();

            return 0;
        } elseif (!is_null($this->argument('param')) && is_array($this->argument('param'))) {
            $count = ceil($indexModel::withoutGlobalScopes()->count() / 500);

            for ($i = 0; $i < $count; $i++) {
                $indexModel->withoutGlobalScopes()->with($this->argument('param'))->offset(500 * $i)->limit(500)->get()->addToIndex();
                $indexModel->refresh();
            }

            return 0;
        }

        $count = ceil($indexModel::withoutGlobalScopes()->count() / 500);

        for ($i = 0; $i < $count; $i++) {
            $indexModel->withoutGlobalScopes()->offset(500 * $i)->limit(500)->get()->addToIndex();
            $indexModel->refresh();
        }

        return 0;
    }
}

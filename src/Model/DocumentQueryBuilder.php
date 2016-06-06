<?php

namespace KodiCMS\Datasource\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use KodiCMS\Datasource\Contracts\SectionInterface;

class DocumentQueryBuilder extends Builder
{
    /**
     * @var SectionInterface
     */
    protected $section;

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param SectionInterface                    $section
     *
     * @return void
     */
    public function __construct(QueryBuilder $query, SectionInterface $section)
    {
        parent::__construct($query);
        $this->section = $section;
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array $columns
     *
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function getModels($columns = ['*'])
    {
        $results = $this->query->get($columns);

        $instance = $this->model->newInstance()->setConnection($this->model->getConnectionName());

        $results = array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);

        return $instance->newCollection($results)->all();
    }

    /**
     * Eagerly load the relationship on a set of models.
     *
     * @param  array  $models
     * @param  string  $name
     * @param  \Closure  $constraints
     * @return array
     */
    protected function loadRelation(array $models, $name, \Closure $constraints)
    {
        // First we will "back up" the existing where conditions on the query so we can
        // add our eager constraints. Then we will merge the wheres that were on the
        // query back to it in order that any where conditions might be specified.
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($models);

        call_user_func($constraints, $relation);

        $models = $relation->initRelation($models, $name);

        // Once we have the results, we just match those back up to their parent models
        // using the relationship instance. Then we just return the finished arrays
        // of models which have been eagerly hydrated and are readied for return.
        $results = $relation->getEager();


        return $relation->match($models, $results, $name);
    }
}

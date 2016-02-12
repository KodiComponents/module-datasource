<?php

namespace KodiCMS\Datasource\Filter\Operators;

use KodiCMS\Datasource\Filter\Operator;
use Illuminate\Database\Eloquent\Builder;

class LessOperator extends Operator
{
    /**
     * @param Builder $query
     * @param string  $field
     * @param string  $condition
     */
    protected function _query(Builder $query, $field, $condition = 'and')
    {
        $query->where($field, '<', $this->getValue(), $condition);
    }
}

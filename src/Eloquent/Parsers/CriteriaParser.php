<?php

namespace Axn\Repository\Eloquent\Parsers;

use Exception;
use Illuminate\Database\Eloquent\Builder;

class CriteriaParser
{
    /**
     * Liste des opérateurs pour les critères.
     *
     * @var array
     */
    protected static $operators = [
        'EQUAL'         => '=',
        'NOT_EQUAL'     => '!=',
        'LIKE'          => 'like',
        'NOT_LIKE'      => 'not like',
        'GREATER_THAN'  => '>',
        'GREATER_EQUAL' => '>=',
        'LESS_THAN'     => '<',
        'LESS_EQUAL'    => '<=',
    ];

    /**
     * Applique les critères de filtrage sur la requête et ses relations.
     *
     * @param  Builder $query
     * @param  array   $criteria
     * @return void
     */
    public function apply(Builder $query, array $criteria)
    {
        if (empty($criteria)) {
            throw new Exception("Criteria cannot be empty.");
        }

        $criteriaByRel = $this->groupByRelations($criteria);

        foreach ($criteriaByRel as $relation => $criteria) {
            if (empty($relation)) {
                $this->where($query, $criteria);
            } else {
                $query->whereHas($relation, function($query) use ($criteria) {
                    $this->where($query, $criteria);
                });
            }
        }
    }

    /**
     * Applique des critères de filtrage sur une requête via la clause WHERE.
     *
     * @param  Builder $query
     * @param  array   $criteria
     * @return void
     */
    protected function where(Builder $query, array $criteria)
    {
        foreach ($criteria as $column => $value) {
            list($column, $operator) = array_merge(explode(' ', $column), ['EQUAL']);
            $column = $query->getModel()->getTable().'.'.$column;

            if ($operator === 'IN') {
                $query->whereIn($column, $value);
            }
            elseif ($operator === 'NOT_IN') {
                $query->whereNotIn($column, $value);
            }
            elseif ($operator === 'NOT_EQUAL' && is_null($value)) {
                $query->whereNotNull($column);
            }
            elseif (array_key_exists($operator, self::$operators)) {
                $query->where($column, self::$operators[$operator], $value);
            }
            else {
                throw new Exception("Incorrect operator: $operator");
            }
        }
    }

    /**
     * Met les colonnes par relations.
     *
     * Par exemple, si l'on a ceci :
     *   ['a' => 0, 'r1.b' => 1, 'r1.c' => 2, 'r1.r2.d' => 3]
     * on obtient alors :
     *   [''      => ['a' => 0],
     *    'r1'    => ['b' => 1, 'c' => 2],
     *    'r1.r2' => ['d' => 3]]
     *
     * @param  array $criteria
     * @return array
     */
    protected function groupByRelations(array $criteria)
    {
        $criteriaByRel = [];

        foreach ($criteria as $column => $value) {
            $segments = explode('.', $column);
            $column   = array_pop($segments);
            $relation = implode('.', $segments);

            if (!isset($criteriaByRel[$relation])) {
                $criteriaByRel[$relation] = [];
            }

            $criteriaByRel[$relation][$column] = $value;
        }

        return $criteriaByRel;
    }
}

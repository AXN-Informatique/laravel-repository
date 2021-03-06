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
        if (empty($criteria)) return;

        // On réorganise les critères par relations
        // Ex : ['a' => 0, 'r1.r2.b' => 1] devient ['' => ['a' => 0], 'r1.r2' => ['b' => 1]]
        $criteriaByRel = $this->groupByRelations($criteria);

        foreach ($criteriaByRel as $relation => $criteria) {
            if (empty($relation)) {
                $this->applyWhere($query, $criteria);
            } else {
                $query->whereHas($relation, function($query) use ($criteria) {
                    $this->applyWhere($query, $criteria);
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
    protected function applyWhere(Builder $query, array $criteria)
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
     * Réorganise les colonnes par relations.
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
            $segments = explode('.', $column);   // 'r1.r2.d' => ['r1', 'r2', 'd']
            $column   = array_pop($segments);    // $column = 'd' ; $segments = ['r1', 'r2']
            $relation = implode('.', $segments); // $relation = 'r1.r2'

            if (!isset($criteriaByRel[$relation])) {
                $criteriaByRel[$relation] = [];
            }

            $criteriaByRel[$relation][$column] = $value;
        }

        return $criteriaByRel;
    }
}

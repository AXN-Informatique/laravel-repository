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

        $criteriaByRelations = $this->groupCriteriaByRelations($criteria);

        foreach ($criteriaByRelations as $relation => $relCriteria) {
            if (empty($relation)) {
                $this->where($query, $relCriteria);
            } else {
                $query->whereHas($relation, function($query) use ($relCriteria) {
                    $this->where($query, $relCriteria);
                });
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
    protected function groupCriteriaByRelations(array $criteria)
    {
        $criteriaByRelations = [];

        foreach ($criteria as $column => $value) {
            $segments = explode('.', $column);
            $column   = array_splice($segments, -1)[0];
            $relation = implode('.', $segments);

            if (!isset($criteriaByRelations[$relation])) {
                $criteriaByRelations[$relation] = [];
            }

            $criteriaByRelations[$relation][$column] = $value;
        }

        return $criteriaByRelations;
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
            if (strpos($column, ' ')) {
                list($column, $operator) = explode(' ', $column);
            } else {
                $operator = 'EQUAL';
            }

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
}

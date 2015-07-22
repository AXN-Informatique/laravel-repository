<?php

namespace Axn\Repository\Eloquent\Parsers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ColumnsParser
{
    /**
     * Applique les sélections de colonnes sur la requête et ses relations.
     *
     * @param  Builder $query
     * @param  array   $columns
     * @return void
     */
    public function apply(Builder $query, array $columns)
    {
        $columnsByRel = $this->groupByRelations($columns);

        // On tri le tableau pour aller de la relation la plus profonde à la moins profonde
        // Ex : ['roles.permissions' => [...], 'roles' => [...], '' => [...]]
        uksort($columnsByRel, function($a, $b) {
            $aNbDots = substr_count($a, '.');
            $bNbDots = substr_count($b, '.');

            return ($aNbDots > $bNbDots ? -1 : ($aNbDots < $bNbDots ? 1 : 0));
        });

        foreach ($columnsByRel as $relation => $columns) {
            $this->addKeysForEagerLoading($columnsByRel, $relation, $query->getModel());
        }

        foreach ($columnsByRel as $relation => $columns) {
            if (empty($relation)) {
                $this->select($query, $columns);
            } else {
                $query->with([$relation => function($query) use ($columns) {
                    $this->select($query, $columns);
                }]);
            }
        }
    }

    /**
     * Applique une sélection de colonnes sur une requête via la clause SELECT.
     *
     * @param  Builder|Relation $query
     * @param  array            $columns
     * @return void
     */
    protected function select($query, array $columns)
    {
        $query->select(array_map(
            function($column) use ($query) {
                return $query->getModel()->getTable().'.'.$column;
            },
            $columns
        ));
    }

    /**
     * Met les colonnes par relations et crée aussi les emplacements des relations
     * pour lesquelles il n'y a pas de sélection de champs.
     *
     * Par exemple, si l'on a ceci :
     *   ['a', 'r1.r2.b']
     * on obtient alors :
     *   [''      => ['a'],
     *    'r1'    => [],
     *    'r1.r2' => ['b']]
     *
     * @param  array $columns
     * @return array
     */
    protected function groupByRelations(array $columns)
    {
        $columnsByRel = [];

        foreach ($columns as $column) {
            $segments = explode('.', $column);
            $column   = array_pop($segments);
            $relation = '';

            array_unshift($segments, $relation);

            foreach ($segments as $segment) {
                $relation .= (!empty($relation) ? '.' : '').$segment;

                if (!isset($columnsByRel[$relation])) {
                    $columnsByRel[$relation] = [];
                }
            }

            $columnsByRel[$relation][] = $column;
        }

        return $columnsByRel;
    }

    /**
     * Ajoute au tableau de colonnes les clés nécessaires à l'eager loading.
     *
     * @param  array   &$columnsByRel
     * @param  string  $current
     * @param  Model   $model
     * @return array
     */
    protected function addKeysForEagerLoading(&$columnsByRel, $current, Model $model)
    {
        $relationsNames = !empty($current) ? explode('.', $current) : [];
        $relation = null;

        foreach ($relationsNames as $relationName) {
            $relation = $model->$relationName();
            $model = $relation->getModel();
        }

        $this->push($columnsByRel[$current], $model->getKeyName());

        if ($relation instanceof HasOneOrMany) {
            if (!empty($columnsByRel[$current])) {
                $this->push($columnsByRel[$current], $relation->getPlainForeignKey());
            }
        }
        elseif ($relation instanceof BelongsTo) {
            $parent = implode('.', array_slice($relationsNames, 0, -1));

            if (!empty($columnsByRel[$parent])) {
                $this->push($columnsByRel[$parent], $relation->getForeignKey());

                if ($relation instanceof MorphTo) {
                    $this->push($columnsByRel[$parent], $relation->getMorphType());
                }
            }
        }
    }

    /**
     * Ajoute une valeur au tableau uniquement si celui-ci ne contient pas déjà
     * cette valeur et s'il ne contient pas non plus la valeur '*'.
     *
     * @param  array &$array
     * @param  mixed $value
     * @return void
     */
    protected function push(&$array, $value)
    {
        if (!in_array($value, $array) && !in_array('*', $array)) {
            $array[] = $value;
        }
    }
}

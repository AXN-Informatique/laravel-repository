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
        // On réorganise les colonnes par relations
        // Ex : ['a', 'r1.r2.b'] devient ['' => ['a'], 'r1' => [], 'r1.r2' => ['b']]
        $columnsByRel = $this->groupByRelations($columns);

        // On tri le tableau pour aller de la relation la plus profonde à la moins profonde
        // Ex : ['' => [...], 'r1' => [...], 'r1.r2' => [...]] devient ['r1.r2' => [...], 'r1' => [...], '' => [...]]
        // On a besoin que ce soit dans cet ordre pour que le "with" d'Eloquent fonctionne correctement...
        uksort($columnsByRel, function($a, $b) {
            $aNbDots = substr_count($a, '.');
            $bNbDots = substr_count($b, '.');

            return ($aNbDots > $bNbDots ? -1 : ($aNbDots < $bNbDots ? 1 : 0));
        });

        // On ajoute les colonnes nécessaires (clés étrangères) à l'eager-loading
        foreach ($columnsByRel as $relation => $columns) {
            $this->addKeysForEagerLoading($columnsByRel, $relation, $query->getModel());
        }

        foreach ($columnsByRel as $relation => $columns) {
            if (empty($relation)) {
                $this->applySelect($query, $columns);
            } else {
                $query->with([$relation => function($query) use ($columns) {
                    $this->applySelect($query, $columns);
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
    protected function applySelect($query, array $columns)
    {
        $query->select(array_map(
            function($column) use ($query) {
                return $query->getModel()->getTable().'.'.$column;
            },
            $columns
        ));
    }

    /**
     * Réorganise les colonnes par relations et crée aussi les emplacements des
     * relations pour lesquelles il n'y a pas de sélection de champs.
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
            $segments = explode('.', $column); // 'r1.r2.b' => ['r1', 'r2', 'b']
            $column   = array_pop($segments);  // $column = 'b' ; $segments = ['r1', 'r2']
            $relation = '';

            array_unshift($segments, $relation); // $segments = ['', 'r1', 'r2']

            // Boucle nécessaire pour avoir tous les niveaux de profondeur dans $columnsByRel
            foreach ($segments as $segment) {
                // D'abord '', puis 'r1', puis 'r1.r2'
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
        $relationsNames = !empty($current) ? explode('.', $current) : []; // 'r1.r2' => ['r1', 'r2']
        $relation = null;

        // Permet de retrouver l'instance de la relation courante ($current) ainsi que son modèle
        // Ex : si $relationsNames = ['r1', 'r2'] alors $relation = $model->r1()->getModel()->r2()
        //      et $model = $model->r1()->getModel()->r2()->getModel()
        foreach ($relationsNames as $relationName) {
            $relation = $model->$relationName();
            $model = $relation->getModel();
        }

        // On ajoute la clé primaire du modèle à la liste des colonnes
        $this->push($columnsByRel[$current], $model->getKeyName());

        // On ajoute la clé étrangère en fonction de la relation concernée
        if ($relation instanceof HasOneOrMany) {
            $this->push($columnsByRel[$current], $relation->getPlainForeignKey()); // $columnsByRel['r1.r2'] = 't1_id'
        }
        elseif ($relation instanceof BelongsTo) {
            $parent = implode('.', array_slice($relationsNames, 0, -1)); // ['r1', 'r2'] => 'r1'

            $this->push($columnsByRel[$parent], $relation->getForeignKey()); // $columnsByRel['r1'][] = 't2_id'

            if ($relation instanceof MorphTo) {
                $this->push($columnsByRel[$parent], $relation->getMorphType()); // $columnsByRel['r1'][] = 'morphable_type'
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

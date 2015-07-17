<?php

namespace Axn\Repository\Eloquent\Parsers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        $columnsByRelations = $this->addKeysForEagerLoading(
            $this->groupColumnsByRelations($columns), $query->getModel()
        );

        foreach ($columnsByRelations as $relation => $relColumns) {
            if (empty($relation)) {
                $query->select($relColumns);
            } else {
                $query->with([$relation => function($query) use ($relColumns) {
                    $query->select($relColumns);
                }]);
            }
        }
    }

    /**
     * Met les colonnes par relations.
     *
     * Par exemple, si l'on a ceci :
     *   ['a', 'r1.b', 'r1.c', 'r1.r2.d']
     * on obtient alors :
     *   [''      => ['a' => 0],
     *    'r1'    => ['b' => 1, 'c' => 2],
     *    'r1.r2' => ['d' => 3]]
     *
     * @param  array $columns
     * @return array
     */
    protected function groupColumnsByRelations(array $columns)
    {
        $columnsByRelations = [];

        foreach ($columns as $column) {
            $segments = explode('.', $column);
            $column   = array_splice($segments, -1)[0];
            $relation = implode('.', $segments);

            if (!isset($columnsByRelations[$relation])) {
                $columnsByRelations[$relation] = [];
            }

            $columnsByRelations[$relation][] = $column;
        }

        // On tri les relations de la plus profonde à la moins profonde
        // Ex : ['roles.permissions' => [...], 'roles' => [...], '' => [...]]
        uksort($columnsByRelations, function($a, $b) {
            $aNbDots = substr_count($a, '.');
            $bNbDots = substr_count($b, '.');

            return ($aNbDots > $bNbDots ? -1 : ($aNbDots < $bNbDots ? 1 : 0));
        });

        return $columnsByRelations;
    }

    /**
     * Ajoute au tableau de colonnes, pour chaque relation, les clés nécessaires
     * à l'eager loading. Pour les relations "n-n", le nom de la table est de plus
     * concaténé aux noms des colonnes afin d'éviter les erreurs de noms ambigues
     * dûes à la jointure effectuée.
     *
     * Par exemple, si l'on demandé les champs suivants sur le modèle User :
     *   [''            => ['username'],
     *    'userRoles'   => ['created_at'],
     *    'roles.perms' => ['display_name']]
     * on obtient alors :
     *   [''            => ['username', 'id'],
     *    'userRoles'   => ['created_at', 'user_id', 'id'],
     *    'roles.perms' => ['table_perms.display_name', 'table_perms.id]]
     *
     * @param  array   $columnsByRelations
     * @param  Model   $model
     * @return array
     */
    protected function addKeysForEagerLoading(array $columnsByRelations, Model $model)
    {
        foreach ($columnsByRelations as $relation => &$relColumns) {
            if (!in_array('id', $relColumns)) {
                $relColumns[] = 'id';
            }
            if (empty($relation)) {
                continue;
            }

            $relMethods = explode('.', $relation);
            $parent = '';

            foreach ($relMethods as $iMethod => $relMethod) {
                $relInstance = $model->$relMethod();
                $model = $relInstance->getModel();

                if ($iMethod > 0) {
                    $parent .= (!empty($parent) ? '.' : '').$relMethods[$iMethod - 1];
                }
            }

            if ($relInstance instanceof BelongsTo) {
                if (!empty($columnsByRelations[$parent])
                    && !in_array($relInstance->getForeignKey(), $columnsByRelations[$parent])) {

                    $columnsByRelations[$parent][] = $relInstance->getForeignKey();
                }
            }
            elseif ($relInstance instanceof BelongsToMany) {
                $relColumns = array_map(function($column) use ($model) {
                    return $model->getTable().'.'.$column;
                }, $relColumns);
            }
        }

        return $columnsByRelations;
    }
}

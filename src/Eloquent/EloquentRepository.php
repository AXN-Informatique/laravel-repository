<?php

namespace Axn\Repository\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Axn\Repository\Repository;

abstract class EloquentRepository implements Repository
{
    /**
     * Nom du trait utilisé par le modèle pour les soft deletes.
     */
    const SOFT_DELETES_TRAIT = 'Illuminate\Database\Eloquent\SoftDeletes';

    /**
     * Instance du modèle associé à cet entrepot.
     *
     * @var Model
     */
    private $model;

    /**
     * Options pour l'entrepôt.
     *
     * @var array
     */
    private $options;

    /**
     * Permet de criteria les colonnes avec relations.
     *
     * @var Parsers\ColumnsParser
     */
    private $columns;

    /**
     * Permet de criteria les critères de filtrage.
     *
     * @var Parsers\CriteriaParser
     */
    private $criteria;

    /**
     * Constructeur.
     *
     * @param  Model $model
     * @return void
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model    = $model;
        $this->options  = $options;
        $this->columns  = new Parsers\ColumnsParser;
        $this->criteria = new Parsers\CriteriaParser;
    }

    /**
     * Retourne une nouvelle instance de l'entrepôt, avec une instance du modèle
     * incluant les enregistrements supprimés.
     *
     * @return static
     */
    public function withTrashed()
    {
        $options = array_merge($this->options, ['trashed' => 'with']);

        return new static($this->model, $options);
    }

    /**
     * Retourne une nouvelle instance de l'entrepôt, avec une instance du modèle
     * contenant uniquement les enregistrements supprimés.
     *
     * @return static
     */
    public function onlyTrashed()
    {
        $options = array_merge($this->options, ['trashed' => 'only']);

        return new static($this->model, $options);
    }

    /**
     * Retrouve un enregistrement via son id.
     *
     * @param  int   $id
     * @param  array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getById($id, array $columns = [])
    {
        return $this->getBy([$this->model->getKeyName() => $id], $columns);
    }

    /**
     * Retrouve plusieurs enregistrements via leurs ids.
     *
     * @param  array $ids
     * @param  array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getManyByIds(array $ids, array $columns = [])
    {
        return $this->getAllBy([$this->model->getKeyName().' IN' => $ids], $columns);
    }

    /**
     * Retrouve tous les enregistrements.
     *
     * @param  array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getAll(array $columns = [])
    {
        $query = $this->newQuery();
        $this->columns->apply($query, $columns, $eager);

        return collect($this->all($query, $eager));
    }

    /**
     * Retrouve un enregistrement via des critères.
     *
     * @param  array $criteria
     * @param  array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getBy(array $criteria, array $columns = [])
    {
        $query = $this->newQuery();
        $this->columns->apply($query, $columns);
        $this->criteria->apply($query, $criteria);

        return collect($query->first());
    }

    /**
     * Retrouve plusieurs enregistrements via des critères.
     *
     * @param  array $criteria
     * @param  array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getAllBy(array $criteria, array $columns = [])
    {
        $query = $this->newQuery();
        $this->columns->apply($query, $columns, $eager);
        $this->criteria->apply($query, $criteria);

        return collect($this->all($query, $eager));
    }

    /**
     * Retrouve plusieurs enregistrements distincts via des critères.
     *
     * @param  array $criteria
     * @param  array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getAllDistinctBy(array $criteria, array $columns = [])
    {
        $query = $this->newQuery();
        $this->columns->apply($query, $columns, $eager);
        $this->criteria->apply($query, $criteria);

        return collect($this->all($query->distinct(), $eager));
    }

    /**
     * Retrouve plusieurs enregistrements via des critères et les pagine avec
     * le Paginator de Laravel.
     *
     * @param  int   $perPage
     * @param  array $criteria
     * @param  array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, array $criteria = [], array $columns = [])
    {
        $query = $this->newQuery();
        $this->columns->apply($query, $columns);

        if (!empty($criteria)) {
            $this->criteria->apply($query, $criteria);
        }

        return $query->paginate($perPage);
    }

    /**
     * Retourne le nombre d'enregistrements correspondant aux critères.
     *
     * @param  array $criteria
     * @return int
     */
    public function count(array $criteria = [])
    {
        $query = $this->newQuery();

        if (!empty($criteria)) {
            $this->criteria->apply($query, $criteria);
        }

        return $query->count();
    }

    /**
     * Crée un nouvel enregistrement.
     *
     * @param  array $data
     * @return int
     */
    public function create(array $data)
    {
        return $this->model->create($data)->getKey();
    }

    /**
     * Crée plusieurs nouveaux enregistrements.
     *
     * @param  array[array] $datalist
     * @return void
     */
    public function createMany(array $datalist)
    {
        if ($this->model->usesTimestamps()) {
            $now = $this->model->freshTimestampString();

            foreach ($datalist as &$data) {
                $data[$this->model->getCreatedAtColumn()] = $now;
                $data[$this->model->getUpdatedAtColumn()] = $now;
            }
        }

        $this->newQuery()->getQuery()->insert($datalist);
    }

    /**
     * Modifie les informations d'un enregistrement via son id.
     *
     * @param  int   $id
     * @param  array $data
     * @return int
     */
    public function updateById($id, array $data)
    {
        return $this->updateBy([$this->model->getKeyName() => $id], $data);
    }

    /**
     * Modifie les informations de plusieurs enregistrements via leurs ids.
     *
     * @param  array $ids
     * @param  array $data
     * @return int
     */
    public function updateManyByIds(array $ids, array $data)
    {
        return $this->updateBy([$this->model->getKeyName().' IN' => $ids], $data);
    }

    /**
     * Modifie les informations de plusieurs enregistrements via des critères.
     *
     * @param  array $criteria
     * @param  array $data
     * @return int
     */
    public function updateBy(array $criteria, array $data)
    {
        $query = $this->newQuery();
        $this->criteria->apply($query, $criteria);

        return $query->update($data);
    }

    /**
     * Tente de mettre à jour un enregistrement. Celui-ci est créé s'il n'existe pas.
     *
     * @param  array $attributes
     * @param  array $data
     * @return void
     */
    public function updateOrCreate(array $attributes, array $data)
    {
        $this->model->updateOrCreate($attributes, $data);
    }

    /**
     * Supprime un enregistrement via son id.
     *
     * @param  int     $id
     * @param  boolean $force
     * @return mixed
     */
    public function deleteById($id, $force = false)
    {
        return $this->deleteBy([$this->model->getKeyName() => $id], $force);
    }

    /**
     * Supprime plusieurs enregistrements via leurs ids.
     *
     * @param  array   $ids
     * @param  boolean $force
     * @return int
     */
    public function deleteManyByIds(array $ids, $force = false)
    {
        return $this->deleteBy([$this->model->getKeyName().' IN' => $ids], $force);
    }

    /**
     * Supprime un enregistrement via des critères.
     *
     * @param  array   $criteria
     * @param  boolean $force
     * @return mixed
     */
    public function deleteBy(array $criteria, $force = false)
    {
        $query = $this->newQuery();
        $this->criteria->apply($query, $criteria);

        if ($force) {
            return $query->forceDelete();
        } else {
            return $query->delete();
        }
    }

    /**
     * Retourne une nouvelle instance du builder d'Eloquent.
     *
     * @return Builder
     */
    protected function newQuery()
    {
        if (!empty($this->options['trashed'])
            && in_array(static::SOFT_DELETES_TRAIT, class_uses($this->model)))
        {
            if ($this->options['trashed'] == 'with') {
                return $this->model->withTrashed();
            }
            elseif ($this->options['trashed'] == 'only') {
                return $this->model->onlyTrashed();
            }
        }

        return $this->model->newQuery();
    }

    /**
     * Appèle la méthode get() :
     *   - Sur le builder d'Eloquent s'il y a besoin de faire de l'eager loading
     *     (méthode with() utilisée).
     *   - Ou directement sur le builder de base s'il n'y a pas besoin de faire
     *     de l'eager loading, ce qui augmente considérablement les performances
     *     lorsqu'il y a beaucoup de résultats.
     *
     * @param  Builder $builder
     * @param  boolean $eager
     * @return array
     */
    private function all(Builder $builder, $eager)
    {
        if ($eager) {
            return $builder->get()->toArray();
        } else {
            return $builder->getQuery()->get();
        }
    }
}

<?php

namespace Axn\Repository\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Axn\Repository\Repository;

abstract class EloquentRepository implements Repository
{
    /**
     * Nom du trait utilisé par le modèle pour les soft deletes.
     */
    const SOFT_DELETES_TRAIT = 'Illuminate\Database\Eloquent\SoftDeletes';

    /**
     * Instance du modèle associé à ce repository.
     *
     * @var Model
     */
    private $model;

    /**
     * Options du repository.
     *
     * @var array
     */
    private $options;

    /**
     * Constructeur.
     *
     * @param  Model $model
     * @return void
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model = $model;
        $this->options = $options;
    }

    /**
     * Retourne une nouvelle instance du repository, avec une instance du modèle
     * incluant les enregistrements supprimés.
     *
     * @return static
     */
    public function withTrashed()
    {
        return new static($this->model, array_merge($this->options, ['trashed' => 'with']));
    }

    /**
     * Retourne une nouvelle instance du repository, avec une instance du modèle
     * contenant uniquement les enregistrements supprimés.
     *
     * @return static
     */
    public function onlyTrashed()
    {
        return new static($this->model, array_merge($this->options, ['trashed' => 'only']));
    }

    /**
     * Retrouve un enregistrement via son id.
     *
     * @param  int               $id
     * @param  array|string|null $columns
     * @return Model|null
     */
    public function getById($id, $columns = null)
    {
        return $this->getBy([$this->model->getKeyName() => $id], $columns);
    }

    /**
     * Retrouve un enregistrement via des critères.
     *
     * @param  array             $criteria
     * @param  array|string|null $columns
     * @return Model|null
     */
    public function getBy(array $criteria, $columns = null)
    {
        return $this->newQuery($criteria, $columns)->first();
    }

    /**
     * Retrouve plusieurs enregistrements via leurs ids.
     *
     * @param  array             $ids
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return Collection
     */
    public function getManyByIds(array $ids, $columns = null, $order = null, $limit = null, $offset = null)
    {
        return $this->getAllBy([$this->model->getKeyName().' IN' => $ids], $columns, $order, $limit, $offset);
    }

    /**
     * Retrouve tous les enregistrements.
     *
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return Collection
     */
    public function getAll($columns = null, $order = null, $limit = null, $offset = null)
    {
        return $this->getAllBy([], $columns, $order, $limit, $offset);
    }

    /**
     * Retrouve plusieurs enregistrements via des critères.
     *
     * @param  array             $criteria
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return Collection
     */
    public function getAllBy(array $criteria, $columns = null, $order = null, $limit = null, $offset = null)
    {
        return $this->newQuery($criteria, $columns, $order, $limit, $offset)->get();
    }

    /**
     * Retrouve plusieurs enregistrements distincts via des critères.
     *
     * @param  array             $criteria
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return Collection
     */
    public function getAllDistinctBy(array $criteria, $columns = null, $order = null, $limit = null, $offset = null)
    {
        return $this->newQuery($criteria, $columns, $order, $limit, $offset)->distinct()->get();
    }

    /**
     * Retrouve plusieurs enregistrements via des critères et les pagine avec
     * le Paginator de Laravel.
     *
     * @param  int               $perPage
     * @param  array             $criteria
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, array $criteria = [], $columns = null, $order = null)
    {
        return $this->newQuery($criteria, $columns, $order)->paginate($perPage);
    }

    /**
     * Retourne le nombre d'enregistrements correspondant aux critères.
     *
     * @param  array $criteria
     * @return int
     */
    public function count(array $criteria = [])
    {
        return $this->newQuery($criteria)->count();
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
        return $this->newQuery($criteria)->update($data);
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
        $query = $this->newQuery($criteria);

        if ($force) {
            return $query->forceDelete();
        } else {
            return $query->delete();
        }
    }

    /**
     * Retourne une nouvelle instance du modèle.
     *
     * @param  array   $attributes
	 * @param  boolean $exists
     * @return Model
     */
    protected function newModel(array $attributes = [], $exists = false)
    {
        return $this->model->newInstance($attributes, $exists);
    }

    /**
     * Retourne une nouvelle instance du builder d'Eloquent.
     *
     * @param  array             $criteria
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return Builder
     */
    protected function newQuery(array $criteria = [], $columns = null, $order = null, $limit = null, $offset = null)
    {
        $model = $this->model->newInstance();

        // Sans, avec ou seulement avec les enregistrement supprimés (soft deletes)
        if (!empty($this->options['trashed'])
            && in_array(static::SOFT_DELETES_TRAIT, class_uses($model)))
        {
            if ($this->options['trashed'] == 'with') {
                return $model->withTrashed();
            }
            elseif ($this->options['trashed'] == 'only') {
                return $model->onlyTrashed();
            }
        }

        $query = $model->newQuery();

        // Filtrage de la requête en fonction des paramètres
        if (!empty($criteria)) {
            (new Parsers\CriteriaParser)->apply($query, $criteria);
        }
        if (!empty($columns)) {
            if (!is_array($columns)) {
                $columns = array_map('trim', explode(',', $columns));
            }
            (new Parsers\ColumnsParser)->apply($query, $columns);
        }
        if (!empty($order)) {
            foreach (explode(',', $order) as $orderBy) {
                $o = explode(' ', trim($orderBy));
                $query->orderBy($o[0], !empty($o[1]) ? $o[1] : 'asc');
            }
        }
        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }
        if ($offset !== null && $offset >= 0) {
            $query->offset($offset);
        }

        return $query;
    }
}

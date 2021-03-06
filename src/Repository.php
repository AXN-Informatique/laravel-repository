<?php

namespace Axn\Repository;

interface Repository
{
    /**
     * Retrouve un enregistrement via son id.
     *
     * @param  int               $id
     * @param  array|string|null $columns
     * @return \ArrayAccess|null
     */
    public function getById($id, $columns = null);

    /**
     * Retrouve un enregistrement via des critères.
     *
     * @param  array             $criteria
     * @param  array|string|null $columns
     * @return \ArrayAccess|null
     */
    public function getBy(array $criteria, $columns = null);

    /**
     * Retrouve tous les enregistrements.
     *
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return \Illuminate\Support\Collection
     */
    public function getAll($columns = null, $order = null, $limit = null, $offset = null);

    /**
     * Retrouve plusieurs enregistrements via leurs ids.
     *
     * @param  array             $ids
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return \Illuminate\Support\Collection
     */
    public function getAllByIds(array $ids, $columns = null, $order = null, $limit = null, $offset = null);

    /**
     * Retrouve plusieurs enregistrements via des critères.
     *
     * @param  array             $criteria
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return \Illuminate\Support\Collection
     */
    public function getAllBy(array $criteria, $columns = null, $order = null, $limit = null, $offset = null);

    /**
     * Retrouve plusieurs enregistrements distincts via des critères.
     *
     * @param  array             $criteria
     * @param  array|string|null $columns
     * @param  string|null       $order
     * @param  int|null          $limit
     * @param  int|null          $offset
     * @return \Illuminate\Support\Collection
     */
    public function getAllDistinctBy(array $criteria, $columns = null, $order = null, $limit = null, $offset = null);

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
    public function paginate($perPage, array $criteria = [], $columns = null, $order = null);

    /**
     * Vérifie l'existence d'un enregistrement via son id.
     *
     * @param  int $id
     * @return boolean
     */
    public function exists($id);

    /**
     * Retourne le nombre d'enregistrements correspondant aux critères.
     *
     * @param  array $criteria
     * @return int
     */
    public function count(array $criteria = []);

    /**
     * Crée un nouvel enregistrement.
     *
     * @param  array $data
     * @return \ArrayAccess
     */
    public function create(array $data);

    /**
     * Crée plusieurs nouveaux enregistrements.
     *
     * @param  array[array] $datalist
     * @return void
     */
    public function createMany(array $datalist);

    /**
     * Modifie les informations d'un enregistrement via son id.
     *
     * @param  int   $id
     * @param  array $data
     * @return int
     */
    public function updateById($id, array $data);

    /**
     * Modifie les informations de plusieurs enregistrements via leurs ids.
     *
     * @param  array $ids
     * @param  array $data
     * @return int
     */
    public function updateManyByIds(array $ids, array $data);

    /**
     * Modifie les informations de plusieurs enregistrements via des critères.
     *
     * @param  array $criteria
     * @param  array $data
     * @return int
     */
    public function updateBy(array $criteria, array $data);

    /**
     * Tente de mettre à jour un enregistrement. Celui-ci est créé s'il n'existe pas.
     *
     * @param  array $attributes
     * @param  array $data
     * @return void
     */
    public function updateOrCreate(array $attributes, array $data);

    /**
     * Supprime un enregistrement via son id.
     *
     * @param  int   $id
     * @return mixed
     */
    public function deleteById($id);

    /**
     * Supprime plusieurs enregistrements via leurs ids.
     *
     * @param  array $ids
     * @return int
     */
    public function deleteManyByIds(array $ids);

    /**
     * Supprime un enregistrement via des critères.
     *
     * @param  array $criteria
     * @return mixed
     */
    public function deleteBy(array $criteria);
}

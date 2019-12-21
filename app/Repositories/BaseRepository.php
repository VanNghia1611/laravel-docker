<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Prettus\Repository\Eloquent\BaseRepository as BaseRepo;

abstract class BaseRepository extends BaseRepo
{

    /**
     * Delete data with conditions
     *
     * @param array $where Conditions to delete
     *
     * @return mixed
     */
    public function deleteWhere(array $where)
    {
        return $this->model->where($where)->delete();
    }

    /**
     * Inserting records into the database table
     *
     * @param array $data      Rows data
     * @param bool  $timestamp Timestamp
     *
     * @return bool
     */
    public function insertMany($data, $timestamp = false)
    {
        if ($timestamp) {
            foreach ($data as $key => $value) {
                $value['updated_at'] = Carbon::now();
                $value['created_at'] = Carbon::now();
                $data[$key] = $value;
            }
        }
        return \DB::table($this->model->getTable())->insert($data);
    }

    /**
     * Delete a model
     *
     * @param Collection $model Model
     *
     * @return mixed
     */
    public function deleteModel($model)
    {
        return $model->delete();
    }

    /**
     * This method overide "update" method
     *
     * @param array $attributes Attribute to update
     * @param int   $id         Model's id
     *
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        $this->applyCriteria();
        return parent::update($attributes, $id);
    }

    /**
     * First or create
     *
     * @param array $attributes Attributes
     *
     * @return mixed
     */
    public function firstOrCreate(array $attributes)
    {
        return $this->model->firstOrCreate($attributes);
    }

    /**
     * Update a model
     *
     * @param Collection $model      Model
     * @param array      $attributes Attributes
     *
     * @return mixed
     */
    public function updateModel($model, $attributes)
    {
        return $model->update($attributes);
    }

    /**
     * Load relation from a model
     *
     * @param Collection $model    Model
     * @param string     $relation relationship
     * @param array      $select   Select column
     *
     * @return void
     */
    public function loadModel($model, $relation, array $select = ['*'])
    {
        $load[$relation] = function ($query) use ($select) {
            $query->select($select);
        };
        $model->load($load);
    }

    /**
     * Where with raw
     *
     * @param string $conditions Conditions
     * @param array  $params     Params to query
     *
     * @return mixed
     */
    public function whereRaw($conditions, $params)
    {
        return $this->model->whereRaw($conditions)->setBindings($params);
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param null    $limit    Limit Page
     * @param array   $columns  Select column
     * @param string  $pageName page custom display
     * @param integer $page     Number Page
     *
     * @return mixed
     */
    public function eloquentPaginate($limit = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->applyCriteria();
        $this->applyScope();
        $limit = is_null($limit) ? config('repository.pagination.limit', 15) : $limit;
        $results = $this->model->paginate($limit, $columns, $pageName, $page);
        $results->appends(app('request')->query());
        $this->resetModel();
        
        return $this->parserResult($results);
    }
}

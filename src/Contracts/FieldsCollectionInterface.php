<?php

namespace KodiCMS\Datasource\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

interface FieldsCollectionInterface extends Arrayable, \ArrayAccess
{
    /**
     * @param int $id
     *
     * @return FieldInterface|null
     */
    public function getById($id);

    /**
     * @param string $type
     *
     * @return FieldInterface[]|Collection
     */
    public function getByType($type);

    /**
     * @param string $key
     *
     * @return FieldInterface|null
     */
    public function getByKey($key);

    /**
     * @return array
     */
    public function getIds();

    /**
     * @return array
     */
    public function getKeys();

    /**
     * @return array
     */
    public function getNames();

    /**
     * @return FieldInterface[]|Collection
     */
    public function getFields();

    /**
     * @return \Illuminate\Database\Eloquent\Collection|FieldGroupInterface
     */
    public function getGroupedFields();

    /**
     * @param array| string $keys
     *
     * @return FieldInterface[]|Collection
     */
    public function getOnly($keys);

    /**
     * @return FieldsCollectionInterface
     */
    public function getEditable();

    /**
     * @param FieldInterface $field
     *
     * @return $this
     */
    public function add(FieldInterface $field);


}

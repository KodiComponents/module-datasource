<?php

namespace KodiCMS\Datasource\Fields;

use Illuminate\Support\Collection;
use KodiCMS\Datasource\Contracts\FieldGroupInterface;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\FieldsCollectionInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\FieldGroups\DefaultGroup;
use KodiCMS\Datasource\Model\FieldGroup;

class FieldsCollection implements FieldsCollectionInterface
{

    /**
     * @var FieldInterface[]|Collection
     */
    protected $fields;

    /**
     * @var SectionInterface
     */
    protected $section;

    /**
     * @param Collection|array $fields
     */
    public function __construct($fields)
    {
        $this->fields = new Collection();

        foreach ($fields as $field) {
            if ($field instanceof FieldInterface) {
                $this->add($field);
            } elseif ($field instanceof FieldGroupInterface) {
                foreach ($field->getFields() as $groupField) {
                    $this->add($groupField);
                }
            }
        }
    }

    /**
     * @param int $id
     *
     * @return FieldInterface|null
     */
    public function getById($id)
    {
        return $this->fields->filter(function (FieldInterface $field) use ($id) {
            return $field->getId() == $id;
        })->first();
    }

    /**
     * @param string $type
     *
     * @return FieldInterface[]|Collection
     */
    public function getByType($type)
    {
        return $this->fields->filter(function (FieldInterface $field) use ($type) {
            if (is_array($type)) {
                return in_array($field->type, $type);
            } else {
                return $field->type == $type;
            }
        });
    }

    /**
     * @param string $key
     *
     * @return FieldInterface|null
     */
    public function getByKey($key)
    {
        return $this->fields->get($key);
    }

    /**
     * @return array
     */
    public function getIds()
    {
        return $this->fields->keyBy(function (FieldInterface $field) {
            return $field->getId();
        })->keys()->all();
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return $this->fields->keys()->all();
    }

    /**
     * @return array
     */
    public function getNames()
    {
        return $this->fields->pluck('name', 'key')->all();
    }

    /**
     * @return FieldInterface[]|Collection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|FieldGroupInterface
     */
    public function getGroupedFields()
    {
        $groups = FieldGroup::all()->map(function ($field) {
            return $field->setFields([]);
        })->keyBy('id');

        $defaultGroup = (new DefaultGroup())->setFields([]);

        $this->fields->each(function (FieldInterface $field) use ($groups, $defaultGroup) {
            if ($groups->offsetExists($field->group_id) and ! is_null($group = $groups->offsetGet($field->group_id))) {
                $group->addField($field);
            } else {
                $defaultGroup->addField($field);
            }
        });

        return $groups->add($defaultGroup);
    }

    /**
     * @param array| string $keys
     *
     * @return FieldInterface[]|Collection
     */
    public function getOnly($keys)
    {
        if (! is_array($keys)) {
            $keys = func_get_args();
        }

        return $this->fields->only($keys);
    }

    /**
     * @return FieldsCollectionInterface
     */
    public function getEditable()
    {
        return $this->fields->filter(function (FieldInterface $field) {
            return $field->isEditable();
        });
    }

    /**
     * @param FieldInterface $field
     *
     * @return $this
     */
    public function add(FieldInterface $field)
    {
        if ($field->exists) {
            $field->initFilterType();
        }

        $this->fields->put($field->getDBKey(), $field);

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->fields->toArray();
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->fields->offsetExists($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->fields->offsetGet($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        $this->fields->offsetUnset($offset);
    }
}

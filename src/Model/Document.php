<?php

namespace KodiCMS\Datasource\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;
use KodiCMS\CMS\Http\Controllers\System\TemplateController;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\FieldsCollectionInterface;
use KodiCMS\Datasource\Contracts\FieldTypeDateInterface;
use KodiCMS\Datasource\Contracts\FieldTypeOnlySystemInterface;
use KodiCMS\Datasource\Contracts\FieldTypeRelationInterface;
use KodiCMS\Datasource\Contracts\SectionHeadlineInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Fields\File;
use KodiCMS\Datasource\Fields\Primitive\Primary;
use KodiCMS\Datasource\Observers\DocumentObserver;
use KodiCMS\Widgets\Contracts\Widget as WidgetInterface;

class Document extends Model implements DocumentInterface
{
    protected static function boot()
    {
        parent::boot();
        static::observe(new DocumentObserver);
    }

    /**
     * @var SectionInterface
     */
    protected $section;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = null;

    /**
     * @var string
     */
    protected $editTemplate = 'datasource::document.edit';

    /**
     * @var string
     */
    protected $createTemplate = 'datasource::document.create';

    /**
     * @var string
     */
    protected $formTemplate = 'datasource::document.partials.form';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['section'];

    /**
     * @var array
     */
    protected $relationsFields = [];

    /**
     * @param array                 $attributes
     * @param SectionInterface|null $section
     */
    public function __construct($attributes = [], SectionInterface $section = null)
    {
        $this->section = $section;

        if (! is_null($section)) {
            $this->table = $this->section->getSectionTableName();
            $this->primaryKey = $section->getDocumentPrimaryKey();

            if (! is_null($this->primaryKey) && $this->hasField($this->primaryKey)) {
                $field = $this->getFields()->get($this->primaryKey);
                if ($field instanceof Primary) {
                    $this->incrementing = true;
                }
            }

            foreach ($this->getFields() as $field) {
                if (! ($field instanceof FieldTypeOnlySystemInterface) and $field->hasDatabaseColumn()) {
                    $this->fillable[] = $field->getDBKey();
                    $this->setAttribute($field->getDBKey(), $field->getDefaultValue());
                }

                if ($field instanceof FieldTypeRelationInterface) {
                    $this->relationsFields[] = $field->getRelationName();
                }
            }
        }

        parent::__construct($attributes);
    }

    /**
     * @return string|int
     */
    public function getId()
    {
        return $this->getKey();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->{$this->section->getDocumentTitleKey()};
    }

    /**
     * @return string
     */
    public function getEditLink()
    {
        return route('backend.datasource.document.edit', [$this->section->getId(), $this->getKey()]);
    }

    /**
     * @return string
     */
    public function getCreateLink()
    {
        return route('backend.datasource.document.create', [$this->section->getId()]);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array $attributes
     *
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        if ($this->getSection()) {
            $this->getEditableFields()->getFields()->each(function (FieldInterface $field) use (& $attributes) {
                $field->onDocumentFill($this, array_get($attributes, $field->getDBKey()));
                unset($attributes[$field->getDBKey()]);
            });
        }

        parent::fill($attributes);

        return $this;
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return $this->hasField($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasField($key)
    {
        return $this->getFields()->has($key);
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        if ($this->hasField($key)) {
            return $this->getFields()->get($key)->onGetDocumentValue($this, $value);
        }

        return parent::mutateAttribute($key, $value);
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function getFormValue($key)
    {
        $value = parent::getAttributeValue($key);

        if (! is_null($field = $this->getFieldsCollection()->getByKey($key))) {
            $value = $field->onGetFormValue($this, $value);
        }

        return $value;
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function getHeadlineValue($key)
    {
        $value = parent::getAttributeValue($key);

        if (! is_null($field = $this->getFieldsCollection()->getByKey($key))) {
            $value = $field->onGetHeadlineValue($this, $value);
        }

        return $value;
    }

    /**
     * @param SectionHeadlineInterface $headline
     *
     * @return array
     */
    public function toHeadlineArray(SectionHeadlineInterface $headline)
    {
        $fields = $headline->getHeadlineFields();

        $attributes = [
            0 => null,
            'primaryKey' => $this->getKey(),
        ];

        foreach ($fields as $key => $params) {
            if (array_get($params, 'type') == 'link') {
                $attributes[$key] = link_to($this->getEditLink(), $this->getHeadlineValue($key));
            } else {
                $attributes[$key] = $this->getHeadlineValue($key);
            }
        }

        return $attributes;
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string          $key
     * @param  WidgetInterface $widget
     *
     * @return mixed
     */
    public function getWidgetValue($key, WidgetInterface $widget)
    {
        $value = parent::getAttributeValue($key);
        if (! is_null($field = $this->getFieldsCollection()->getByKey($key))) {
            $value = $field->onGetWidgetValue($this, $widget, $value);
        }

        return $value;
    }

    /**
     * @return FieldsCollectionInterface
     */
    public function getFieldsCollection()
    {
        return $this->getSection()->getFields();
    }

    /**
     * @return Collection|FieldInterface[]
     */
    public function getFields()
    {
        return $this->getFieldsCollection()->getFields();
    }

    /**
     * @return array
     */
    public function getFieldsNames()
    {
        return $this->getFieldsCollection()->getNames();
    }

    /**
     * @return FieldsCollectionInterface
     */
    public function getEditableFields()
    {
        return $this->getSection()->getEditableFields();
    }

    /**
     * @return SectionInterface
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @return SectionInterface
     */
    public function getSectionAttribute()
    {
        return $this->getSection();
    }

    /**
     * @return string
     */
    public function getEditTemplate()
    {
        return $this->editTemplate;
    }

    /**
     * @return string
     */
    public function getCreateTemplate()
    {
        return $this->createTemplate;
    }

    /**
     * @return string
     */
    public function getFormTemplate()
    {
        return $this->formTemplate;
    }

    /**
     * @param TemplateController $controller
     */
    public function onControllerLoad(TemplateController $controller)
    {
        $this->getFields()->each(function (FieldInterface $field) use ($controller) {
            $field->onControllerLoad($this, $controller);
        });
    }

    /**
     * @param Validator $validator
     *
     * @return array
     */
    public function getValidationRules(Validator $validator)
    {
        $rules = [];

        foreach ($this->getEditableFields()->getFields() as $field) {
            $rules[$field->getDBKey()] = $field->getValidationRules($this, $validator);
        }

        return $rules;
    }

    /**************************************************************************
     * Scopes
     **************************************************************************/

    /**
     * Scope a query to only include popular users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyPublished($query)
    {
        return $query->where('published', 1);
    }

    /**************************************************************************
     * Custom query builder by Headline/Widgets parameters
     **************************************************************************/

    /**
     * @param int|string      $id
     * @param array|null      $fields
     * @param string|int|null $primaryKeyField
     *
     * @return DocumentInterface|null
     */
    public function getDocumentById($id, array $fields = null, $primaryKeyField = null)
    {
        if (is_null($primaryKeyField)) {
            $primaryKeyField = $this->primaryKey;
        }

        $query = $this->buildQueryForWidget($fields);

        $result = $query->where($primaryKeyField, $id)->first();

        return is_null($result) ? new static([], $this->section) : $result;
    }

    /**
     * @param bool|array|null $fields
     * @param array           $orderRules
     * @param array           $filterRules
     *
     * @return Collection
     */
    public function getDocuments($fields = true, array $orderRules = [], array $filterRules = [])
    {
        return $this->buildQueryForWidget($fields, $orderRules, $filterRules);
    }

    /**
     * @param bool|array|null $fields
     * @param array           $orderRules
     * @param array           $filterRules
     *
     * @return Builder
     */
    protected function buildQueryForWidget($fields = true, array $orderRules = [], array $filterRules = [])
    {
        $query = $this->newQuery();

        $t = [$this->section->getId() => true];

        $selectFields = [];

        if (is_array($fields)) {
            foreach ($fields as $fieldKey) {
                if ($this->hasField($fieldKey)) {
                    continue;
                }

                $selectFields[] = $this->getFieldsCollection()->getByKey($fieldKey);
            }
        } elseif ($fields === true) {
            $selectFields = $this->getFieldsCollection();
        } elseif ($fields === false) {
            $query->selectRaw('COUNT(*) as total_docs');
        }

        // TODO: предусмотреть relation поля
        if ($fields !== false) {
            foreach ($selectFields as $field) {
                $field->querySelectColumn($query, $this);
            }
        }

        if (! empty($orderRules)) {
            $this->buildQueryOrdering($query, $orderRules, $t);
        }

        if (! empty($filterRules)) {
            $this->buildQueryFilters($query, $filterRules, $t);
        }

        return $query;
    }

    /**
     * @param DocumentQueryBuilder $query
     * @param array                $orderRules
     * @param array                $t
     */
    protected function buildQueryOrdering(DocumentQueryBuilder $query, array $orderRules, array &$t)
    {
        $j = 0;

        foreach ($orderRules as $rule) {
            $field = null;

            $fieldKey = key($rule);
            $dir = $rule[key($rule)];

            if (is_null($field = $this->getFieldsCollection()->getByKey($fieldKey))) {
                continue;
            }

            // TODO: предусмотреть relation поля
            $field->queryOrderBy($query, $dir);

            unset($field);

            $j++;
        }
    }

    /**
     * @return mixed
     */
    public function withFields()
    {
        return $this->newQuery()->with($this->relationsFields);
    }

    public function loadRelations()
    {
        foreach ($this->relationsFields as $key)
        {
            $this->getRelationValue($key);
        }
    }

    /**************************************************************************
     * Override methods
     **************************************************************************/

    /**
     * Create a new instance of the given model.
     *
     * @param  array $attributes
     * @param  bool  $exists
     *
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static($attributes, $this->section);
        $model->exists = $exists;

        return $model;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new DocumentQueryBuilder($query, $this->section);
    }

    /**
     * Get a relationship.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function getRelationValue($key)
    {
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }

        if ($relation = $this->getFieldRelation($key)) {
            $this->setRelation($key, $results = $relation->getResults());

            return $results;
        }
    }

    /**
     * @param string $name
     *
     * @return Relation|null
     */
    protected function getFieldRelation($name)
    {
        $field = $this->getFields()->filter(function ($field) use ($name) {
            return $field instanceof FieldTypeRelationInterface and $field->getRelationName() == $name;
        })->first();

        if (! $field) {
            return null;
        }

        $relatedSection = $field->getRelatedSection();
        $relatedField = $field->getRelatedField();

        return $field->getDocumentRelation($this, $relatedSection, $relatedField);
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        $this->getFields()->each(function ($field) {
            if ($field instanceof FieldTypeDateInterface) {
                $this->dates[] = $field->getDBKey();
            }
        });

        if ($this->hasField(static::CREATED_AT) and $this->hasField(static::UPDATED_AT)) {
            $this->timestamps = true;
        }

        return parent::getDates();
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->relationsFields) and $relation = $this->getFieldRelation($method)) {
            return $relation;
        }

        return parent::__call($method, $parameters);
    }
}

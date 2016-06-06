<?php

namespace KodiCMS\Datasource\Model;

use DatasourceManager;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use KodiCMS\Datasource\Document;
use KodiCMS\Datasource\Fields\FieldsCollection;
use KodiCMS\Datasource\Sections\SectionToolbar;
use KodiCMS\Datasource\Sections\SectionHeadline;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Exceptions\SectionException;
use KodiCMS\Datasource\Contracts\SectionToolbarInterface;
use KodiCMS\Datasource\Contracts\FieldsCollectionInterface;
use KodiCMS\Users\Facades\BackendGate;

class Section extends DatasourceModel implements SectionInterface
{
    /**
     * @var SectionHeadlineInterface
     */
    protected $headline;

    /**
     * @var SectionToolbarInterface
     */
    protected $toolbar;

    /**
     * @var string
     */
    protected $table = 'datasources';

    /**
     * @var bool
     */
    protected $initializedFields = false;

    /**
     * @var string
     */
    protected $sectionTablePrefix = 'ds_';

    /**
     * @var string
     */
    protected $documentPrimaryKey = 'id';

    /**
     * @var string
     */
    protected $documentTitleKey = 'header';

    /**
     * @var string
     */
    protected $sectionTableName = 'datasource';

    /**
     * @var FieldsCollection
     */
    protected $sectionFields;

    /**
     * @var FieldsCollection
     */
    protected $relatedFields;

    /**
     * @var array
     */
    protected $sectionSettings = [];

    /**
     * @var
     */
    protected $sectionType;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'folder_id',
        'type',
        'name',
        'description',
        'is_indexable',
        'created_by_id',
        'settings',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'folder_id'     => 'integer',
        'type'          => 'string',
        'name'          => 'string',
        'description'   => 'string',
        'is_indexable'  => 'boolean',
        'created_by_id' => 'integer',
        'settings'      => 'array',
    ];
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['documentPrimaryKey', 'documentTitleKey'];

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDocumentPrimaryKey()
    {
        return $this->documentPrimaryKey;
    }

    /**
     * @return string
     */
    public function getDocumentPrimaryKeyAttribute()
    {
        return $this->getDocumentPrimaryKey();
    }

    /**
     * @return string
     */
    public function getDocumentTitleKey()
    {
        return $this->documentTitleKey;
    }

    /**
     * @return string
     */
    public function getDocumentTitleKeyAttribute()
    {
        return $this->getDocumentTitleKey();
    }

    /**
     * @return int
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getSectionTableName()
    {
        return $this->sectionTablePrefix.$this->sectionTableName.$this->getId();
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return route('backend.datasource.list', [$this->id]);
    }

    /**
     * @return int
     */
    public function getIcon()
    {
        return $this->getSetting('icon', $this->getType()->getIcon());
    }

    /**
     * @return int
     */
    public function getMenuPosition()
    {
        return $this->getSetting('menu_position', 0);
    }

    /**
     * @return bool
     */
    public function showInRootMenu()
    {
        return $this->getSetting('show_in_root_menu', false);
    }

    /**************************************************************************
     * Fields
     **************************************************************************/

    /**
     * @return FieldsCollectionInterface
     */
    public function getFields()
    {
        $this->initializeFields();

        return $this->sectionFields;
    }
    /**
     * @return FieldsCollectionInterface
     */
    public function getEditableFields()
    {
        return new FieldsCollection($this->getFields()->getEditable(), $this);
    }

    /**
     * @return array
     */
    public function getRelatedFields()
    {
        $this->initializeFields();

        return $this->relatedFields;
    }

    /**
     * @return array
     */
    public function getSystemFields()
    {
        return [];
    }

    protected function initializeFields()
    {
        if (! $this->initializedFields) {
            $fields = Field::where(function($q) {
                $q->orWhere('section_id', $this->id)
                    ->orWhere('related_section_id', $this->id);
            })->with('relatedSection')->get();

            $this->sectionFields = new FieldsCollection($fields->filter(function($field) {
                return $field->section_id == $this->id;
            }), $this);
            
            $this->relatedFields = new FieldsCollection($fields->filter(function($field) {
                return $field->related_section_id == $this->id;
            }), $this);

            $this->initializedFields = true;
        }
    }

    /**************************************************************************
     * Headline
     **************************************************************************/

    /**
     * @return SectionHeadlineInterface
     */
    public function getHeadline()
    {
        if (is_null($this->headline)) {
            $headlineClass  = $this->getHeadlineClass();
            $this->headline = new $headlineClass($this);
        }

        return $this->headline;
    }

    /**
     * @return string
     */
    public function getHeadlineClass()
    {
        return SectionHeadline::class;
    }

    /**
     * @return array
     */
    public function getHeadlineOrdering()
    {
        return $this->getSetting('ordering', []);
    }

    /**************************************************************************
     * Toolbar
     **************************************************************************/

    public function getToolbar()
    {
        if (is_null($this->toolbar)) {
            $toolbarClass = $this->getToolbarClass();
            $this->toolbar = new $toolbarClass($this);
        }

        return $this->toolbar;
    }

    /**
     * @return string
     */
    public function getToolbarClass()
    {
        return SectionToolbar::class;
    }

    /**************************************************************************
     * Type
     **************************************************************************/

    /**
     * @return \KodiCMS\Datasource\SectionType|null
     * @throws SectionException
     */
    public function getType()
    {
        if ($this->sectionType) {
            return $this->sectionType;
        }

        if (is_null($typeObject = DatasourceManager::getTypeObject($this->type))) {
            throw new SectionException("Datasource type {$this->type} not found");
        }

        return $this->sectionType = $typeObject;
    }

    /**************************************************************************
     * Documents
     **************************************************************************/

    /**
     * @param array $attributes
     *
     * @return DocumentInterface
     */
    public function getEmptyDocument(array $attributes = [])
    {
        return $this->newDocumentQuery($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return DocumentInterface
     */
    public function newDocumentQuery(array $attributes = [])
    {
        $documentClass = $this->getDocumentClass();

        return new $documentClass($attributes, $this);
    }

    /**
     * @param int   $id
     * @param array $attributes
     *
     * @return Document
     */
    public function getDocumentById($id, array $attributes = [])
    {
        return $this->newDocumentQuery($attributes)->findOrFail($id);
    }

    /**
     * @return string
     */
    public function getDocumentClass()
    {
        return;
    }

    /**************************************************************************
     * ACL
     **************************************************************************/

    /**
     * @return array
     */
    public function getAclActions()
    {
        return [
            [
                'action'      => 'section.view',
                'description' => 'View section',
            ],
            [
                'action'      => 'section.edit',
                'description' => 'Edit section',
            ],
            [
                'action'      => 'section.remove',
                'description' => 'Remove section',
            ],
            [
                'action'      => 'document.view',
                'description' => 'View documents',
            ],
            [
                'action'      => 'document.create',
                'description' => 'Create documents',
            ],
            [
                'action'      => 'document.edit',
                'description' => 'Edit documents',
            ],
            [
                'action'      => 'document.remove',
                'description' => 'Remove documents',
            ],
        ];
    }

    /**
     * Пользователь - создатель раздела.
     *
     * @return bool
     */
    public function userIsCreator()
    {
        return $this->userHasAccess('is_owner');
    }

    /**
     * Проверка прав доступа.
     *
     * @param string       $acl
     * @return bool
     */
    public function userHasAccess($acl = 'section.edit')
    {
        return BackendGate::allows('datasource.'.$acl, $this);
    }

    /**
     * Проверка прав на редактирование.
     *
     * @return bool
     */
    public function userHasAccessEdit()
    {
        return $this->userHasAccess('section.edit');
    }

    /**
     * Проверка прав на редактирование.
     *
     * @return bool
     */
    public function userHasAccessCreate()
    {
        return $this->userHasAccess('section.create');
    }

    /**
     * Проверка прав на просмотр.
     *
     * @return bool
     */
    public function userHasAccessView()
    {
        return $this->userHasAccess('section.view');
    }

    /**
     * Проверка прав на удаление.
     *
     * @return bool
     */
    public function userHasAccessRemove()
    {
        return $this->userHasAccess('section.remove');
    }

    /**************************************************************************
     * Relations
     **************************************************************************/

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fields()
    {
        return $this->hasMany(\KodiCMS\Datasource\Model\Field::class, 'section_id')->orderBy('position');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function relatedFields()
    {
        return $this->hasMany(\KodiCMS\Datasource\Model\Field::class, 'related_section_id')->orderBy('position');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function folder()
    {
        return $this->belongsTo(\KodiCMS\Datasource\Model\SectionFolder::class, 'folder_id')->orderBy('position');
    }

    /**************************************************************************
     * Titles
     **************************************************************************/

    /**
     * @return string
     */
    public function getCreateDocumentTitle()
    {
        return trans('datasource::core.title.create_document');
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getEditDocumentTitle($name = '')
    {
        return trans('datasource::core.title.edit_document', ['name' => $name]);
    }

    /**
     * @return string
     */
    public function getCreateDocumentButtonTitle()
    {
        return trans('datasource::core.toolbar.create_document');
    }

    /**************************************************************************
     * Other
     **************************************************************************/

    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        parent::initialize();
    }

    /**
     * @return DatasourceManagerInterface
     */
    public static function getManagerClass()
    {
        return DatasourceManager::getFacadeRoot();
    }
}

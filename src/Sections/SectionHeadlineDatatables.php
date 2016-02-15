<?php

namespace KodiCMS\Datasource\Sections;

use Meta;
use Datatables;
use Illuminate\Http\JsonResponse;
use KodiCMS\Datasource\DatatablesTable;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Contracts\SectionHeadlineInterface;

class SectionHeadlineDatatables extends DatatablesTable implements SectionHeadlineInterface
{

    /**
     * @var SectionInterface
     */
    protected $section;

    /**
     * @var array
     */
    protected $fields = null;

    /**
     * @var string
     */
    protected $template = 'datasource::section.headline_datatables';

    /**
     * @param SectionInterface $section
     */
    public function __construct(SectionInterface $section)
    {
        $this->section = $section;

        Meta::loadPackage('datatables');

        parent::__construct();
    }

    /**
     * @return array
     */
    public function getHeadlineFields()
    {
        if (! is_null($this->fields)) {
            return $this->fields;
        }

        $this->fields = [];

        foreach ($this->section->getFields() as $field) {
            if (! $field->isVisible()) {
                continue;
            }

            $this->fields[$field->getKey()] = $field->getHeadlineParameters($this);

            if ($this->section->getDocumentTitleKey() == $field->getDBKey()) {
                $this->fields[$field->getKey()]['type'] = 'link';
            }
        }

        return $this->fields;
    }

    /**
     * @return array
     */
    public function getActiveFieldIds()
    {
    }

    /**
     * @return array
     */
    public function getSearchableFields()
    {
    }

    /**
     * @return array
     */
    public function getOrderingRules()
    {
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function prepareQuery()
    {
        $query = $this->section->getEmptyDocument();

        foreach ($this->getJoins() as $join) {
            call_user_func_array([$query, array_shift($join)], $join);
        }

        return $query->getQuery();
    }

    /**
     * @return array
     */
    public function getDocuments()
    {
        return $this->addColumns(
            $this->morphColumns(
                Datatables::of($this->prepareQuery())
            )
        )->make(true);
    }

    /**
     * @return JsonResponse
     */
    public function JsonResponse()
    {
        return $this->getDocuments();
    }

    /**
     * @param string|null $template
     *
     * @return \Illuminate\View\View
     */
    public function render($template = null)
    {
        if (is_null($template)) {
            if (method_exists($this->section, 'getHeadlineTemplate')) {
                $template = $this->section->getHeadlineTemplate();
            } else {
                $template = $this->template;
            }
        }

        return view($template, [
            'fieldParams' => $this->getHeadlineFields(),
            'section'     => $this->section
        ]);
    }

    /**
     * @return \Illuminate\View\View|null
     */
    public function renderOrderSettings()
    {
        return;
    }

    /**
     * @return array
     */
    protected function getTableColumns()
    {
        $columns = [
            [
                'data'           => null,
                'orderable'      => false,
                'defaultContent' => \Form::checkbox('document[]', null, null, ['class' => 'doc-checkbox'])
            ]
        ];

        foreach ($this->getHeadlineFields() as $key => $params) {
            $columns[] = [
                'data'       => $key,
                'name'       => $key,
                'title'      => $params['name'],
                'className'  => array_get($params, 'class'),
                'type'       => array_get($params, 'type', 'string'),
                'orderable'  => array_get($params, 'orderable', 'true'),
                'searchable' => array_get($params, 'searchable', 'true')
            ];
        }

        return $columns;
    }
}

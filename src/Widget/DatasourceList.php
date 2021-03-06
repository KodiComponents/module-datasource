<?php

namespace KodiCMS\Datasource\Widget;

use Assets;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use KodiCMS\Datasource\Filter\Parser;
use KodiCMS\Widgets\Contracts\WidgetManager;
use KodiCMS\Widgets\Widget\WidgetAbstract;
use KodiCMS\Widgets\Traits\WidgetCache;
use KodiCMS\Widgets\Contracts\WidgetCacheable;
use KodiCMS\Datasource\Traits\WidgetDatasource;
use KodiCMS\Datasource\Traits\WidgetDatasourceFields;

class DatasourceList extends WidgetAbstract implements WidgetCacheable
{
    use WidgetCache, WidgetDatasource, WidgetDatasourceFields;

    /**
     * @var SectionRepository
     */
    protected $sectionRepository;

    /**
     * @var array|null
     */
    protected $documents = null;

    /**
     * @var string
     */
    protected $settingsTemplate = 'datasource::widgets.list.settings';

    /**
     * @param WidgetManager $widgetManager
     * @param string        $name
     * @param string        $description
     */
    public function __construct(WidgetManager $widgetManager, $name, $description = '')
    {
        parent::__construct($widgetManager, $name, $description);
        Assets::loadPackage('query-builder');
    }

    /**
     * @return array
     */
    public function booleanSettings()
    {
        return ['order_by_rand'];
    }

    /**
     * @return array
     */
    public function defaultSettings()
    {
        return [
            'order_by_rand' => false,
            'document_uri'  => '/document/:id',
            'count'         => 15,
        ];
    }

    /**
     * @return array
     */
    public function prepareSettingsData()
    {
        $fields = ! $this->getSection() ? [] : $this->getFields();

        $ordering = (array) $this->ordering;

        $queryBuilderFields = [];

        foreach ($fields as $field) {
            $queryBuilderFields[] = $field->getFilterType()->toArray();
        }

        return compact('fields', 'ordering', 'queryBuilderFields');
    }

    /**
     * @return array [[Collection] $documents, [Collection] $documentsRaw,
     *               [KodiCMS\Datasource\Contracts\SectionInterface] $section,
     *               [Illuminate\Pagination\LengthAwarePaginator] $pagination]
     */
    public function prepareData()
    {
        if (is_null($this->getSection())) {
            return [];
        }

        $result = $this->getDocuments();

        $visibleFields = [];

        foreach ($this->getFields() as $field) {
            if (in_array($field->getDBKey(), $this->getSelectedFields())) {
                $visibleFields[] = $field;
            }
        }

        $documents = [];

        foreach ($result as $document) {
            $doc = [];

            foreach ($visibleFields as $field) {
                $doc[$field->getDBKey()] = $document->getWidgetValue($field->getDBKey(), $this);
            }

            $doc['href'] = strtr($this->document_uri, $this->buildUrlParams($doc));

            $documents[$document->getId()] = $doc;
        }

        return [
            'section'      => $this->getSection(),
            'documentsRaw' => $result->items(),
            '$doc'   => $result,
            'documents'    => new Collection($documents),
        ];
    }

    /**
     * @param int $recurse
     *
     * @return array|null
     */
    protected function getDocuments($recurse = 3)
    {
        if (! is_null($this->documents)) {
            return $this->documents;
        }

        if ($this->order_By_rand) {
            $this->ordering = [];
        }

        $queryBuilderFields = [];

        /** @var Builder $documents */
        $documents = $this->getSection()
            ->getEmptyDocument()
            ->getDocuments($this->selected_fields, (array) $this->ordering);

        $rulesParser = new Parser($this->getSettingRules(), $this->getSection()->getFields());

        $rulesParser->compile($documents);

        if ($this->order_By_rand) {
            $documents->orderByRaw('RAND()');
        }

        return $this->documents = $documents->paginate($this->count);
    }

    /**
     * @param array  $data
     * @param string $preffix
     *
     * @return array
     */
    protected function buildUrlParams(array $data, $preffix = null)
    {
        $params = [];

        foreach ($data as $field => $value) {
            if (is_array($value)) {
                $params += $this->buildUrlParams($value, $field);
            } else {
                $field = $preffix === null ? $field : $preffix.'.'.$field;

                $params[':'.$field] = $value;
            }
        }

        return $params;
    }

    /****************************************************************************************************************
     * Settings
     ****************************************************************************************************************/

    /**
     * @param array
     */
    public function getSettingRules()
    {
        $rules = array_get($this->settings, 'rules');

        if (empty($rules)) {
            return 'null';
        }

        return $rules;
    }
}

<?php

namespace KodiCMS\Datasource;

use Datatables;
use Yajra\Datatables\Html\Builder;

abstract class DatatablesTable
{

    /**
     * @var Builder
     */
    protected $html;

    /**
     * @var bool
     */
    protected $isBuilt;

    /**
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    public function __construct()
    {
        $this->html = new Builder(app('config'), app('view'), app('html'), app('url'), app('form'));

        $this->html->setTemplate('datasource::section.datatables_scripts');
    }

    /**
     * Возвращает html-код таблицы.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function show(array $attributes = [])
    {
        $this->buildIfNotBuilt();

        return $this->html->table($attributes);
    }

    /**
     * Устанавливает url источника данных для таблицы.
     * (по умолчанию, это текущий url).
     *
     * @param string $attr
     *
     * @return $this
     */
    public function ajax($attr)
    {
        $this->html->ajax($attr);

        return $this;
    }

    /**
     * Возвращет javascript-код инициализации таблицы.
     *
     * @return string
     */
    public function script()
    {
        $this->buildIfNotBuilt();

        return $this->html->scripts();
    }

    /**
     * Строит таблицу, если она еще не построена.
     *
     * @return void
     */
    public function buildIfNotBuilt()
    {
        if (! $this->isBuilt()) {
            $this->build();
        }
    }

    /**
     * Проверяет построена ли данная таблица.
     *
     * @return bool
     */
    private function isBuilt()
    {
        return $this->isBuilt;
    }

    /**
     * Устанавливет флаг "построен" в истинное значение.
     *
     */
    private function setBuilt()
    {
        $this->isBuilt = true;
    }

    /**
     * Строит таблицу.
     *
     * @return void
     */
    private function build()
    {
        foreach ($this->getTableColumns() as $column) {
            $this->html->addColumn($column);
        }
        $this->setBuilt();
    }

    /**
     * Возвращает данные для наполнения таблицы.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function data()
    {
        return $this->addColumns($this->morphColumns(Datatables::of($this->prepareQuery())))->make(true);
    }

    /**
     * Запускает все модификаторы колонок.
     *
     * @param object $table
     *
     * @return object
     */
    protected function morphColumns($table)
    {
        foreach ($this->getColumnMutators() as $column => $editor) {
            $table->editColumn($column, $editor);
        }

        return $table;
    }

    /**
     * Добавляет дополнительные колнки.
     *
     * @param object $table
     *
     * @return  object
     */
    protected function addColumns($table)
    {
        foreach ($this->getColumnCreators() as $title => $creator) {
            $table->addColumn($title, $creator);
        }

        return $table;
    }

    /**
     * Устанавливает поля таблицы бд для выборки.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function prepareQuery()
    {
        $query = $this->query()->select($this->getDatabaseFields());

        foreach ($this->getJoins() as $join) {

            call_user_func_array([$query, array_shift($join)], $join);
        }

        return $query;
    }

    /**
     * Возвращает массив модификаторов join.
     *
     * ['join'|'leftJoin'|'rightJoin', 'table_name', 'первый.операнд', 'оператор сопоставления', 'второй.операнд']
     * и/или
     * ['join'|'leftJoin'|'rightJoin', 'table_name', $замыкание($join)]
     *
     * @see https://laravel.com/docs/5.2/queries#joins
     */
    protected function getJoins()
    {
        return [];
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return $this->query
            ?: $this->query = $this->getQuery();
    }

    /**
     * Возвращает список полей для получения из бд.
     * ['поле_1', 'поле_2', DB::raw('выражение поля 3'), 'поле_4']
     *
     * @return array
     */
    protected function getDatabaseFields()
    {
        return ['*'];
    }

    /**
     * Возвращает список создатедей (замыканий) дополнительных колонок.
     *
     * @return array|callable[];
     */
    protected function getColumnCreators()
    {
        return [];
    }

    /**
     * Возвращает список замыканий конфигурирующих поля.
     * ['название_поля' => $замыкание]
     *
     * @return array|callable[]
     */
    protected function getColumnMutators()
    {
        return [];
    }

    /**
     * @return array
     */
    abstract protected function getTableColumns();
}
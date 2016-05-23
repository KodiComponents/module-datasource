<?php

namespace KodiCMS\Datasource\Filter;

use KodiCMS\Pages\Contracts\BehaviorPageInterface;
use stdClass;
use KodiCMS\Datasource\Filter\Operators\InOperator;
use KodiCMS\Datasource\Contracts\FilterRuleInterface;
use KodiCMS\Datasource\Filter\Operators\LessOperator;
use KodiCMS\Datasource\Filter\Operators\EqualOperator;
use KodiCMS\Datasource\Contracts\FilterFieldInterface;
use KodiCMS\Datasource\Filter\Operators\NotInOperator;
use KodiCMS\Datasource\Filter\Operators\GreaterOperator;
use KodiCMS\Datasource\Filter\Operators\IsEmptyOperator;
use KodiCMS\Datasource\Filter\Operators\BetweenOperator;
use KodiCMS\Datasource\Filter\Operators\ContainsOperator;
use KodiCMS\Datasource\Filter\Operators\EndsWithOperator;
use KodiCMS\Datasource\Filter\Operators\NotEqualOperator;
use KodiCMS\Datasource\Filter\Operators\BeginsWithOperator;
use KodiCMS\Datasource\Filter\Operators\IsNotEmptyOperator;
use KodiCMS\Datasource\Filter\Operators\NotBetweenOperator;
use KodiCMS\Datasource\Filter\Operators\LessOrEqualOperator;
use KodiCMS\Datasource\Filter\Operators\NotContainsOperator;
use KodiCMS\Datasource\Filter\Operators\NotEndsWithOperator;
use KodiCMS\Datasource\Filter\Operators\NotBeginsWithOperator;
use KodiCMS\Datasource\Filter\Operators\GreaterOrEqualOperator;

class Rule implements FilterRuleInterface
{

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var FilterFieldInterface
	 */
	protected $field;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $input;

	/**
	 * @var Operator
	 */
	protected $operator;

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * @var string
	 */
	protected $condition;

	/**
	 * @var array
	 */
	protected $sqlOperators = [
		'equal'            => EqualOperator::class,
		'not_equal'        => NotEqualOperator::class,
		'less'             => LessOperator::class,
		'less_or_equal'    => LessOrEqualOperator::class,
		'greater'          => GreaterOperator::class,
		'greater_or_equal' => GreaterOrEqualOperator::class,
		'begins_with'      => BeginsWithOperator::class,
		'not_begins_with'  => NotBeginsWithOperator::class,
		'contains'         => ContainsOperator::class,
		'not_contains'     => NotContainsOperator::class,
		'ends_with'        => EndsWithOperator::class,
		'not_ends_with'    => NotEndsWithOperator::class,
		'is_empty'         => IsEmptyOperator::class,
		'is_not_empty'     => IsNotEmptyOperator::class,
		'between'          => BetweenOperator::class,
		'not_between'      => NotBetweenOperator::class,
		'in'               => InOperator::class,
		'not_in'           => NotInOperator::class,
	];

	/**
	 * @param stdClass $rule
	 * @param string   $condition
	 */
	public function __construct(stdClass $rule, $condition)
	{
		foreach (get_object_vars($rule) as $key => $value) {
			$this->{$key} = $value;
		}

		$operatorClass = array_get($this->sqlOperators, $this->getOperator());

		if (is_null($operatorClass)) {
			$operatorClass = EqualOperator::class;
		}

		$this->operator = new $operatorClass($this);
		$this->condition = $this->parseCondition($condition);
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param FilterFieldInterface $field
	 */
	public function setField($field)
	{
		$this->field = $field;

		foreach ((array) $this->value as $key => $value) {
			$this->value[$key] = $this->getField()->parseValue($value);
		}

	}

	/**
	 * @return FilterFieldInterface
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getInput()
	{
		return $this->input;
	}

	/**
	 * @return Operator
	 */
	public function getOperator()
	{
		return $this->operator;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		$value = $this->value;

		if (is_array($value)) {
			foreach ($value as $i => $v) {
				$value[$i] = $this->parseValue($v);
			}

			if (count($value) === 1) {
				$value = $value[0];
			}
		}

		if (method_exists($this->getField(), 'prepareValue')) {
			$value = $this->getField()->prepareValue($value);
		}

		dd($value);
		return $value;
	}

	/**
	 * @return string
	 */
	public function getCondition()
	{
		return $this->condition;
	}

	/**
	 * @param string $condition
	 *
	 * @return string
	 */
	protected function parseCondition($condition)
	{
		return strtolower($condition) == 'or' ? 'or' : 'and';
	}

	/**
	 * @param string $param
	 *
	 * @return mixed
	 */
	private function parseValue($param)
	{
        if (strpos($param, '$route.') !== false) {
            list($type, $path) = explode('.', $param, 2);

            return \Route::getCurrentRoute()->getParameter($path);
        } else if (strpos($param, '$get.') !== false) {
            list($type, $path) = explode('.', $param, 2);

            return \Request::get($path);
        } else if (strpos($param, '$page.') !== false) {
            list($type, $path) = explode('.', $param, 2);
            $page = \Frontpage::getFacadeRoot();

            if (method_exists($page, $method = 'get'.ucfirst($path))) {
                return $page->$method();
            }
        } else if (strpos($param, '$behavior.') !== false) {
            list($type, $path) = explode('.', $param, 2);

            /** @var BehaviorPageInterface $behavior */
            if (! is_null($behavior = \Frontpage::getBehaviorObject())) {
                return $behavior->getRouter()->getParameter($path);
            }
        }
	}
}
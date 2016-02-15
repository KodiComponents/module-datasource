<?php

namespace KodiCMS\Datasource\Filter\Type;

use KodiCMS\Datasource\Model\Field;
use KodiCMS\Datasource\Filter\Type;

class Select extends Type
{
    /**
     * @var array
     */
    protected $operators = ['equal', 'not_equal', 'in', 'not_in'];

    /**
     * @var string
     */
    protected $type = 'integer';

    /**
     * @var string
     */
    protected $input = 'select';

    /**
     * @var bool
     */
    protected $isMultiple = true;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * Select constructor.
     *
     * @param Field $field
     */
    public function __construct(Field $field)
    {
        parent::__construct($field);

        $this->values = function() use($field) {
            return $field->getOptionsList();
        };
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'input'    => $this->input,
            'multiple' => $this->isMultiple,
            'values'   => call_user_func($this->values),
        ]);
    }
}

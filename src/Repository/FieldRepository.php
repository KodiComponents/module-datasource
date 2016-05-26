<?php

namespace KodiCMS\Datasource\Repository;

use DatasourceManager;
use FieldManager;
use Illuminate\Http\Request;
use KodiCMS\CMS\Repository\BaseRepository;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\FieldTypeRelationInterface;
use KodiCMS\Datasource\Exceptions\FieldException;
use KodiCMS\Datasource\Model\Field;

class FieldRepository extends BaseRepository
{
    /**
     * @param Field $model
     */
    public function __construct(Field $model)
    {
        parent::__construct($model);
    }

    /**
     * @return array
     */
    public function validationAttributes()
    {
        return trans('datasource::core.field');
    }

    /**
     * @param         $sectionId
     * @param Request $request
     */
    public function validateOnCreate($sectionId, Request $request)
    {
        $validator = $this->getValidationFactory()->make($request->all(), [
            'key' => "required|unique:datasource_fields,key,NULL,id,section_id,{$sectionId}",
            'type' => 'required',
            'name' => 'required',
        ]);

        $validator->sometimes('related_section_id', 'required|numeric|min:1', function ($input) {
            if ($typeObject = FieldManager::getFieldTypeBy('type', $input->type)) {
                return $typeObject->getFieldObject() instanceof FieldTypeRelationInterface;
            }
        });

        $this->validateWith($validator, $request);
    }

    /**
     * @param int     $id
     * @param Request $request
     */
    public function validateOnUpdate($id, Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);
    }

    /**
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws FieldException
     */
    public function create(array $data = [])
    {
        if (is_null($type = array_get($data, 'type'))) {
            throw new FieldException('Type not set');
        }

        if (is_null($typeObject = FieldManager::getFieldTypeBy('type', $type))) {
            throw new FieldException("Datasource field type {$type} not found");
        }

        /** @var FieldInterface $field */
        $field = parent::create($data);
        FieldManager::addFieldToSectionTable($field->getSection(), $field);

        return $field;
    }

    /**
     * @param array $ids
     */
    public function deleteByIds(array $ids)
    {
        $fields = $this->instance()->whereIn('id', $ids)->get();

        foreach ($fields as $field) {
            $field->delete();
        }
    }

    /**
     * @param int  $fieldId
     * @param bool $status
     */
    public function updateVisible($fieldId, $status)
    {
        $field = $this->findOrFail($fieldId);

        $field->setVisibleStatus($status);
        $field->update();
    }

    /**
     * @return array
     */
    public function getSectionsForSelect()
    {
        $sections = [];

        foreach (DatasourceManager::getSections() as $id => $section) {
            $sections[$section->type][$id] = $section->getName();
        }

        return $sections;
    }
}

<?php

namespace KodiCMS\Datasource\Repository;

use DatasourceManager;
use Illuminate\Http\Request;
use KodiCMS\CMS\Repository\BaseRepository;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use KodiCMS\Datasource\Exceptions\SectionException;
use KodiCMS\Datasource\Model\Section;

class SectionRepository extends BaseRepository
{
    /**
     * @param Section $model
     */
    public function __construct(Section $model)
    {
        parent::__construct($model);
    }

    /**
     * @param string $type
     * @param array  $attributes
     *
     * @return Section
     * @throws SectionException
     */
    public function instanceByType($type, array $attributes = [])
    {
        $attributes['type'] = $type;

        return $this->model->newInstance($attributes);
    }

    /**
     * @{@inheritdoc}
     */
    public function validationAttributes()
    {
        return trans('datasource::core.information');
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function validateOnCreate(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);
    }

    /**
     * @param int     $id
     * @param Request $request
     *
     * @return mixed
     */
    public function validateOnUpdate($id, Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);
    }

    /**
     * @param int $sectionId
     * @param int $folderId
     *
     * @return bool
     */
    public function moveToFolder($sectionId, $folderId)
    {
        $this->findOrFail($sectionId)->update([
            'folder_id' => $folderId,
        ]);

        return true;
    }

    /**
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws SectionException
     */
    public function create(array $data = [])
    {
        if (is_null($type = array_get($data, 'type'))) {
            throw new SectionException('Type not set');
        }

        if (is_null($typeObject = DatasourceManager::getTypeObject($type))) {
            throw new SectionException("Datasource type {$type} not found");
        }

        DatasourceManager::createTableSection($section = parent::create($data));

        return $section;
    }

    /**
     * @param int $id
     *
     * @return Model
     * @throws \Exception
     */
    public function delete($id)
    {
        $model = parent::delete($id);

        $model->fields()->delete();
        DatasourceManager::dropSectionTable($model);

        return $model;
    }
}

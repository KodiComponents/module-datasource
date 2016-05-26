<?php

namespace KodiCMS\Datasource\Repository;

use Illuminate\Http\Request;
use KodiCMS\CMS\Repository\BaseRepository;
use KodiCMS\Datasource\Model\FieldGroup;

class FieldGroupRepository extends BaseRepository
{
    /**
     * @param FieldGroup $model
     */
    public function __construct(FieldGroup $model)
    {
        parent::__construct($model);
    }

    /**
     * @param Request $request
     */
    public function validateOnCreate(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'section_id' => 'required',
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
}

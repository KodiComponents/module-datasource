<?php

namespace KodiCMS\Datasource\Http\Controllers;

use KodiCMS\CMS\Http\Controllers\System\BackendController;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Repository\FieldRepository;
use KodiCMS\Datasource\Repository\SectionRepository;

class FieldController extends BackendController
{
    /**
     * @param SectionRepository $sectionRepository
     * @param FieldRepository   $repository
     * @param int           $dsId
     */
    public function getCreate(SectionRepository $sectionRepository, FieldRepository $repository, $dsId)
    {
        $section = $sectionRepository->findOrFail($dsId);

        $this->breadcrumbs
            ->add($section->getName(), route('backend.datasource.list', $section->getId()))
            ->add('Edit section', route('backend.datasource.edit', $section->getId()));

        $this->setTitle('Create field');

        $this->templateScripts['SECTION_ID'] = $dsId;

        $this->setContent('field.create', [
            'field'    => $repository->instance(),
            'section'  => $section,
            'sections' => $repository->getSectionsForSelect(),
        ]);
    }

    /**
     * @param FieldRepository $repository
     * @param int             $sectionId
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \KodiCMS\Datasource\Exceptions\FieldException
     */
    public function postCreate(FieldRepository $repository, $sectionId)
    {
        $this->request->offsetSet('section_id', $sectionId);
        $repository->validateOnCreate($sectionId, $this->request);

        /** @var FieldInterface $field */
        $field = $repository->create($this->request->all());

        return redirect()
            ->route('backend.datasource.field.edit', $field->getId())
            ->with('success', trans($this->wrapNamespace('core.messages.field.created'), [
                'title' => $field->getName(),
            ]));
    }

    /**
     * @param FieldRepository $repository
     * @param int         $fieldId
     */
    public function getEdit(FieldRepository $repository, $fieldId)
    {
        /** @var FieldInterface $field */
        $field = $repository->findOrFail($fieldId);

        $section = $field->getSection();

        $this->breadcrumbs
            ->add($section->getName(), route('backend.datasource.list', $section->getId()))
            ->add('Edit section', route('backend.datasource.edit', $section->getId()));

        $this->setTitle("Edit field [{$field->getTypeTitle()}::{$field->getName()}]");

        $this->templateScripts['SECTION_ID'] = $section->getId();
        $this->templateScripts['FIELD_ID'] = $field->getId();

        $this->setContent('field.edit', [
            'field'    => $field,
            'section'  => $section,
            'sections' => $repository->getSectionsForSelect(),
        ]);
    }

    /**
     * @param FieldRepository $repository
     * @param int         $fieldId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postEdit(FieldRepository $repository, $fieldId)
    {
        $repository->validateOnUpdate($fieldId, $this->request);

        /** @var FieldInterface $field */
        $field = $repository->update($fieldId, $this->request->all());

        return $this->smartRedirect([$field->getId()])
            ->with('success', trans($this->wrapNamespace('core.messages.field.updated'), [
                'title' => $field->getName(),
            ]));
    }
}

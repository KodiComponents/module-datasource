<?php

namespace KodiCMS\Datasource\Http\Controllers;

use WYSIWYG;
use KodiCMS\Datasource\Repository\DocumentRepository;
use KodiCMS\CMS\Http\Controllers\System\BackendController;

class DocumentController extends BackendController
{
    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getIndex()
    {
        return redirect()->route('backend.datasource.list', $this->request->cookie('currentDS'));
    }

    /**
     * @param DocumentRepository $repository
     * @param int           $sectionId
     */
    public function getCreate(DocumentRepository $repository, $sectionId)
    {
        WYSIWYG::loadAllEditors();

        $document = $repository->getEmptyDocument($sectionId);
        $section = $document->getSection();

        $this->breadcrumbs->add($section->getName(), route('backend.datasource.list', $section->getId()));

        $this->setTitle($section->getCreateDocumentTitle());

        $this->templateScripts['SECTION'] = $section;
        $this->templateScripts['DOCUMENT'] = $document;
        $document->onControllerLoad($this);

        $this->setContent($document->getCreateTemplate(), [
            'document' => $document,
            'section'  => $section,
            'fields'   => $document->getEditableFields(),
        ]);
    }

    /**
     * @param DocumentRepository $repository
     * @param                   $sectionId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postCreate(DocumentRepository $repository, $sectionId)
    {
        $repository->validateOnCreate($sectionId, $this->request);
        $document = $repository->createBySectionId($sectionId, $this->request->all());

        return $this->smartRedirect([
            $sectionId,
            $document->getId(),
        ])->with('success', trans($this->wrapNamespace('core.messages.document_updated'), [
            'title' => $document->getTitle(),
        ]));
    }

    /**
     * @param DocumentRepository $repository
     * @param int           $sectionId
     * @param int|string    $documentId
     */
    public function getEdit(DocumentRepository $repository, $sectionId, $documentId)
    {
        WYSIWYG::loadAllEditors();
        
        $document = $repository->getDocumentById($sectionId, $documentId);
        $section = $document->getSection();

        $document->onControllerLoad($this);
        $this->breadcrumbs->add($section->getName(), route('backend.datasource.list', $section->getId()));

        $this->setTitle($section->getEditDocumentTitle($document->getTitle()));

        $this->templateScripts['SECTION'] = $section;
        $this->templateScripts['DOCUMENT'] = $document;

        $this->setContent($document->getEditTemplate(), [
            'document' => $document,
            'section'  => $section,
            'fields'   => $document->getEditableFields(),
        ]);
    }

    /**
     * @param DocumentRepository $repository
     * @param int               $sectionId
     * @param int               $documentId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postEdit(DocumentRepository $repository, $sectionId, $documentId)
    {
        $repository->validateOnUpdate($sectionId, $documentId, $this->request);
        $document = $repository->updateBySectionId($sectionId, $documentId, $this->request->all());

        return $this->smartRedirect([
            $sectionId,
            $document->getId(),
        ])->with('success', trans($this->wrapNamespace('core.messages.document_updated'), [
            'title' => $document->getTitle(),
        ]));
    }
}

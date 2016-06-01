<?php

namespace KodiCMS\Datasource\Repository;

use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use KodiCMS\CMS\Repository\BaseRepository;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Model\Document;

class DocumentRepository extends BaseRepository
{
    /**
     * @var SectionRepository
     */
    private $section;

    /**
     * @param Document          $model
     * @param SectionRepository $repository
     */
    public function __construct(Document $model, SectionRepository $repository)
    {
        parent::__construct($model);

        $this->section = $repository;
    }

    /**
     * @param int     $sectionId
     * @param Request $request
     */
    public function validateOnCreate($sectionId, Request $request)
    {
        /** @var DocumentInterface $document */
        $document = $this->section->findOrFail($sectionId)->getEmptyDocument();

        /** @var Validator $validator */
        $validator = $this->getValidationFactory()->make($request->all(), []);

        $validator->setRules($document->getValidationRules($validator));
        $validator->setAttributeNames($document->getFieldsNames());

        $this->validateWith($validator, $request);
    }

    /**
     * @param int     $sectionId
     * @param int     $documentId
     * @param Request $request
     */
    public function validateOnUpdate($sectionId, $documentId, Request $request)
    {
        $document = $this->getDocumentById($sectionId, $documentId);

        /** @var Validator $validator */
        $validator = $this->getValidationFactory()->make($request->all(), []);

        $validator->setRules($document->getValidationRules($validator));

        $this->validateWith($validator, $request);
    }

    /**
     * @param int $sectionId
     *
     * @return DocumentInterface
     */
    public function getEmptyDocument($sectionId)
    {
        return $this->section->findOrFail($sectionId)->getEmptyDocument();
    }

    /**
     * @param int $sectionId
     * @param int $documentId
     *
     * @return DocumentInterface
     */
    public function getDocumentById($sectionId, $documentId)
    {
        return $this->section->findOrFail($sectionId)->getDocumentById($documentId);
    }

    /**
     * @param int   $sectionId
     * @param array $data
     *
     * @return mixed
     */
    public function createBySectionId($sectionId, array $data)
    {
        $document = $this->getEmptyDocument($sectionId);

        $document->fill(array_only($data, $document->getEditableFields()->getKeys()))->save();

        return $document;
    }

    /**
     * @param int   $sectionId
     * @param int   $documentId
     * @param array $data
     *
     * @return mixed
     */
    public function updateBySectionId($sectionId, $documentId, array $data)
    {
        $document = $this->getDocumentById($sectionId, $documentId);
        $document->update(array_only($data, $document->getEditableFields()->getKeys()));

        return $document;
    }

    /**
     * @param int       $sectionId
     * @param array|int $ids
     */
    public function deleteBySectionId($sectionId, $ids)
    {
        if (! is_array($ids)) {
            $ids = [$ids];
        }

        /** @var SectionInterface $section */
        $section = $this->section->findOrFail($sectionId);

        $documents = $section->getEmptyDocument()->whereIn($section->getDocumentPrimaryKey(), $ids);

        foreach ($documents->get() as $document) {
            $document->delete();
        }
    }

    /**
     * @param int         $sectionId
     * @param string|null $keyword
     * @param array       $exclude
     *
     * @return array|static[]
     */
    public function findByKeyword($sectionId, $keyword = null, array $exclude = [])
    {
        /** @var SectionInterface $section */
        $section = $this->section->findOrFail($sectionId);

        return \DB::table($section->getSectionTableName())
            ->select('*')
            ->selectRaw("{$section->getDocumentPrimaryKey()} as id")
            ->selectRaw("{$section->getDocumentTitleKey()} as text")
            ->where($section->getDocumentTitleKey(), 'like', '%'.$keyword.'%')
            ->whereNotIn($section->getDocumentPrimaryKey(), $exclude)
            ->limit(10)
            ->get();
    }
}

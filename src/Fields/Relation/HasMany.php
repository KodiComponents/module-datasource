<?php

namespace KodiCMS\Datasource\Fields\Relation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany as HasManyRelation;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Fields\Relation;
use KodiCMS\Datasource\Repository\FieldRepository;

class HasMany extends Relation
{

    /**
     * @var bool
     */
    protected $hasDatabaseColumn = false;

    /**
     * @var array
     */
    protected $selectedDocuments = [];

    /**
     * @param DocumentInterface $document
     * @param mixed $value
     *
     * @return mixed
     */
    public function onGetHeadlineValue(DocumentInterface $document, $value)
    {
        $documents = $document->getAttribute($this->getRelationName())->map(function (DocumentInterface $doc) {
            return \HTML::link($doc->getEditLink(), $doc->getTitle(), ['class' => 'popup']);
        })->all();

        return ! empty($documents)
            ? implode(', ', $documents)
            : null;
    }

    /**
     * @param Builder $query
     * @param DocumentInterface $document
     */
    public function querySelectColumn(Builder $query, DocumentInterface $document)
    {
        $query->with($this->getRelationName());
    }

    /**
     * @param DocumentInterface $document
     * @param mixed $value
     *
     * @return mixed
     */
    public function onSetDocumentAttribute(DocumentInterface $document, $value)
    {
        $this->selectedDocuments = $value;
    }

    /**
     * @param DocumentInterface $document
     */
    public function onUpdateDocumentRelations(DocumentInterface $document)
    {
        $currentDocuments = $this->getRelatedDocuments($document);
        $documents = $this->getRelatedField()
              ->getSection()
              ->newDocumentQuery()
              ->whereIn('id', (array) $this->selectedDocuments)
              ->get();

        $newDocuments = $documents->diff($currentDocuments);

        $document->{$this->getRelationName()}()->saveMany($newDocuments);

        $currentDocuments->diff($documents)->each(function (DocumentInterface $d) {
            $d->{$this->getRelatedField()->getRelationName()}()->dissociate();
            $d->save();
        });
    }

    ///**
    // * @param DocumentInterface $document
    // * @param mixed             $value
    // */
    //public function onDocumentSaved(DocumentInterface $document, $value)
    //{
    //    if (! is_null($relatedField = $this->getRelatedField())) {
    //        $section = $relatedField->getSection();
    //
    //        $documents = $section->newDocumentQuery()
    //            ->whereIn($section->getDocumentPrimaryKey(), $value)
    //            ->get();
    //
    //        $currentDocuments = $this->getRelatedDocumentValues($document);
    //
    //        $newIds = $documents->diff($currentDocuments);
    //
    //        if ($newIds->count() > 0) {
    //            $document->{$this->getRelationName()}()->saveMany($newIds);
    //        }
    //    }
    //}

    /**
     * @param DocumentInterface $document
     *
     * @return array
     */
    public function getRelatedDocumentValues(DocumentInterface $document)
    {
        $section = $this->getRelatedSection();
        return $this->getDocumentRelation($document, $section)
            ->pluck(
                $section->getDocumentTitleKey(), $section->getDocumentPrimaryKey()
            );
    }

    /**
     * @param DocumentInterface $document
     *
     * @return array
     */
    public function getRelatedDocuments(DocumentInterface $document)
    {
        $section = $this->getRelatedSection();
        return $this->getDocumentRelation($document, $section)->get();
    }

    /**
     * @param DocumentInterface     $document
     * @param SectionInterface|null $relatedSection
     * @param FieldInterface|null   $relatedField
     *
     * @return HasManyRelation
     */
    public function getDocumentRelation(DocumentInterface $document, SectionInterface $relatedSection = null, FieldInterface $relatedField = null) {
        $instance = $relatedSection->getEmptyDocument()->newQuery();

        $foreignKey = $this->getRelatedField()->getDBKey();
        $localKey = $relatedSection->getDocumentPrimaryKey();

        return new HasManyRelation($instance, $document, $foreignKey, $localKey);
    }

    /**
     * @param FieldRepository $repository
     *
     * @throws \KodiCMS\Datasource\Exceptions\FieldException
     */
    public function onCreated(FieldRepository $repository)
    {
        if (! is_null($this->getRelatedFieldId())) {
            return;
        }

        $relatedField = $repository->create([
            'type'               => 'belongs_to',
            'section_id'         => $this->getRelatedSectionId(),
            'is_system'          => 1,
            'key'                => $this->getRelatedDBKey(),
            'name'               => $this->getSection()->getName(),
            'related_section_id' => $this->getSection()->getId(),
            'related_field_id'   => $this->getId(),
        ]);

        if (! is_null($relatedField)) {
            $this->update(['related_field_id' => $relatedField->getId()]);
        }
    }

    /**
     * @return string
     */
    public function getRelatedDBKey()
    {
        return $this->getDBKey().'_has_many';
    }

    /**
     * @param DocumentInterface $document
     *
     * @return array
     */
    protected function fetchDocumentTemplateValues(DocumentInterface $document)
    {
        return array_merge(parent::fetchDocumentTemplateValues($document), [
            'value' => $this->getRelatedDocumentValues($document),
        ]);
    }
}

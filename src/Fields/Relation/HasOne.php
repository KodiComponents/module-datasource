<?php

namespace KodiCMS\Datasource\Fields\Relation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne as HasOneRelation;
use Illuminate\Database\Schema\Blueprint;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Fields\Relation;
use KodiCMS\Datasource\Repository\FieldRepository;

class HasOne extends Relation
{
    /**
     * @var bool
     */
    protected $hasDatabaseColumn = false;

    /**
     * @var string
     */
    protected $selectedDocument;

    /**
     * @param DocumentInterface $document
     * @param SectionInterface|null $relatedSection
     * @param FieldInterface|null $relatedField
     *
     * @return HasOneRelation
     */
    public function getDocumentRelation(DocumentInterface $document, SectionInterface $relatedSection = null, FieldInterface $relatedField = null)
    {
        return new HasOneRelation(
            $relatedSection->getEmptyDocument()->newQuery(),
            $document,
            $relatedSection->getSectionTableName().'.'.$this->getRelatedField()->getDBKey(),
            $this->getSection()->getDocumentPrimaryKey()
        );
    }
    
    /**************************************************************************
     * Database
     **************************************************************************/

    /**
     * @param Blueprint $table
     *
     * @return \Illuminate\Support\Fluent
     */
    public function setDatabaseFieldType(Blueprint $table)
    {
        return $table->integer($this->getDBKey())->nullable();
    }

    /**
     * @param Builder $query
     * @param DocumentInterface $document
     */
    public function querySelectColumn(Builder $query, DocumentInterface $document)
    {
        $query->addSelect($this->getDBKey())->with($this->getRelationName());
    }

    /**
     * @param DocumentInterface $document
     *
     * @return array
     */
    public function getRelatedDocumentValue(DocumentInterface $document)
    {
        $section = $this->relatedSection()->first();

        return \DB::table($section->getSectionTableName())
            ->where($section->getDocumentPrimaryKey(), $document->getAttribute($this->getDBKey()))
            ->pluck($section->getDocumentTitleKey(), $section->getDocumentPrimaryKey());
    }

    /**************************************************************************
     * Events
     **************************************************************************/

    /**
     * @param DocumentInterface $document
     * @param mixed $value
     *
     * @return mixed
     */
    public function onSetDocumentAttribute(DocumentInterface $document, $value)
    {
        $this->selectedDocument = $value;
    }

    /**
     * @param DocumentInterface $document
     */
    public function onUpdateDocumentRelations(DocumentInterface $document)
    {
        if ($relatedDocument = $this->getRelatedSection()->newDocumentQuery()->find($this->selectedDocument)) {
            $relation = $document->{$this->getRelationName()}();
            $relation->save($relatedDocument);
        }
    }

    /**
     * @param DocumentInterface $document
     * @param mixed $value
     *
     * @return mixed
     */
    public function onGetHeadlineValue(DocumentInterface $document, $value)
    {
        return ! is_null($relatedDocument = $document->getAttribute($this->getRelationName()))
            ? \HTML::link($relatedDocument->getEditLink(), $relatedDocument->getTitle(), ['class' => 'popup'])
            : null;
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

        $data = [
            'section_id'         => $this->getRelatedSectionId(),
            'is_system'          => 1,
            'name'               => $this->getSection()->getName(),
            'related_section_id' => $this->getSection()->getId(),
            'related_field_id'   => $this->getId(),
        ];

        if ($this->getRelatedSectionId() == $this->section_id) {
            $data['settings']['is_editable'] = false;
        }

        $relatedField = $repository->create(array_merge([
            'type' => 'belongs_to',
            'key'  => $this->getDBKey().'_belongs_to',
        ], $data));

        if (! is_null($relatedField)) {
            $this->update(['related_field_id' => $relatedField->getId()]);
        }
    }

    /**
     * @return string
     */
    public function getRelatedDBKey()
    {
        return $this->getDBKey().'_has_one';
    }

    /**
     * @param FieldRepository $repository
     */
    public function onDeleted(FieldRepository $repository)
    {
        $this->relatedField->delete();
    }
}

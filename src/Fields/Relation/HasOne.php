<?php

namespace KodiCMS\Datasource\Fields\Relation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne as HasOneRelation;
use Illuminate\Database\Schema\Blueprint;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\FieldTypeOnlySystemInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Fields\Relation;
use KodiCMS\Datasource\Repository\FieldRepository;

class HasOne extends Relation implements FieldTypeOnlySystemInterface
{
    /**
     * @var bool
     */
    protected $hasDatabaseColumn = false;

    /**
     * @param DocumentInterface $document
     * @param SectionInterface|null $relatedSection
     * @param FieldInterface|null $relatedField
     *
     * @return HasOneRelation
     */
    public function getDocumentRelation(DocumentInterface $document, SectionInterface $relatedSection = null, FieldInterface $relatedField = null)
    {
        $instance = $relatedSection->getEmptyDocument()->newQuery();

        $foreignKey = $relatedSection->getSectionTableName().'.'.$relatedSection->getDocumentPrimaryKey();
        $otherKey   = $this->getDBKey();
        $relation   = $this->getRelationName();

        return new HasOneRelation($instance, $relatedSection->getEmptyDocument(), $foreignKey, $otherKey, $relation);
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
                  ->addSelect($section->getDocumentPrimaryKey())
                  ->addSelect($section->getDocumentTitleKey())
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
    public function onGetHeadlineValue(DocumentInterface $document, $value)
    {
        return ! is_null($relatedDocument = $document->getAttribute($this->getRelationName()))
            ? \HTML::link($relatedDocument->getEditLink(), $relatedDocument->getTitle(), ['class' => 'popup'])
            : null;
    }

    /**
     * @param FieldRepository $repository
     */
    public function onDeleted(FieldRepository $repository)
    {
        $this->relatedField->delete();
    }
}

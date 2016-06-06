<?php

namespace KodiCMS\Datasource\Fields;

use DatasourceManager;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\FieldTypeRelationInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Model\Field;
use KodiCMS\Widgets\Contracts\Widget as WidgetInterface;

abstract class Relation extends Field implements FieldTypeRelationInterface
{
    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['relatedSection'];

    /**
     * @var bool
     */
    protected $isOrderable = false;

    /**
     * @return array
     */
    public function getSectionList()
    {
        return DatasourceManager::getSectionsFormHTML();
    }

    /**
     * @return int
     */
    public function getRelatedSectionId()
    {
        if ($this->related_section_id === null) {
            $this->related_section_id = $this->section_id;
        }

        return $this->related_section_id;
    }

    /**
     * @return string
     */
    public function getRelatedDBKey()
    {
        return $this->getDBKey().'_related_'.$this->getId();
    }

    /**
     * @return string
     */
    public function getRelationName()
    {
        return camel_case($this->getDBKey().'_relation');
    }

    /**
     * @return string
     */
    public function getRelationFieldName()
    {
        return snake_case($this->getRelationName());
    }

    /**
     * @return SectionInterface
     */
    public function getRelatedSection()
    {
        return $this->relatedSection;
    }

    /**
     * @return FieldInterface
     */
    public function getRelatedField()
    {
        return $this->relatedField;
    }

    /**
     * @param DocumentInterface $document
     *
     * @return array
     */
    protected function fetchDocumentTemplateValues(DocumentInterface $document)
    {
        $relatedSection = $this->getRelatedSection();

        return array_merge(parent::fetchDocumentTemplateValues($document), [
            'relatedDocument' => $this->getDocumentRelation($document, $relatedSection)->first(),
            'relatedSection' => $relatedSection,
            'relatedField' => $this->getRelatedField(),
        ]);
    }

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
     * @param DocumentInterface $document
     * @param WidgetInterface $widget
     * @param mixed $value
     *
     * @return mixed
     */
    public function onGetWidgetValue(DocumentInterface $document, WidgetInterface $widget, $value)
    {
        return ! is_null($related = $document->getAttribute($this->getRelationName()))
            ? $related->toArray()
            : $value;
    }

    /**
     * @param DocumentInterface $document
     */
    public function onRelatedDocumentDeleting(DocumentInterface $document)
    {
    }

    /**
     * @param DocumentInterface $document
     */
    public function onRelatedDocumentDeleted(DocumentInterface $document)
    {
    }

    /**
     * @param DocumentInterface $document
     */
    public function onUpdateDocumentRelations(DocumentInterface $document)
    {
        
    }
}

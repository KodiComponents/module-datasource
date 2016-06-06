<?php

namespace KodiCMS\Datasource\Fields\Relation;

use KodiCMS\Datasource\Fields\Relation;
use Illuminate\Database\Eloquent\Builder;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToRelation;
use KodiCMS\Datasource\Repository\FieldRepository;

class BelongsTo extends Relation
{

    const ONE_TO_ONE  = 'one_to_one';
    const ONE_TO_MANY = 'one_to_many';

    /**
     * @return array
     */
    public function getRelationTypes()
    {
        return [
            static::ONE_TO_ONE => trans('datasource::fields.has_one.one_to_one'),
            static::ONE_TO_MANY => trans('datasource::fields.has_one.one_to_many'),
        ];
    }

    /**
     * @return array
     */
    public function getRelationType()
    {
        return $this->getSetting('relation_type', static::ONE_TO_ONE);
    }


    /**
     * @param DocumentInterface $document
     * @param mixed             $value
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
     * @param Builder           $query
     * @param DocumentInterface $document
     */
    public function querySelectColumn(Builder $query, DocumentInterface $document)
    {
        $query->with($this->getRelationName());
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

        $relatedField = null;

        switch ($this->getRelationType()) {
            case static::ONE_TO_ONE:
                $relatedField = $repository->create(array_merge([
                    'type' => 'belongs_to',
                    'key'  => $this->getRelatedDBKey(),
                ], $data));

                break;
            case static::ONE_TO_MANY:
                $relatedField = $repository->create(array_merge([
                    'type' => 'has_many',
                    'key'  => $this->getDBKey().'_has_many',
                ], $data));

                break;
        }

        if (! is_null($relatedField)) {
            $this->update(['related_field_id' => $relatedField->getId()]);
        }
    }

    /**
     * @return string
     */
    public function getRelatedDBKey()
    {
        return $this->getDBKey().'_belongs_to';
    }

    /**
     * @param DocumentInterface     $document
     * @param SectionInterface|null $relatedSection
     * @param FieldInterface|null   $relatedField
     *
     * @return BelongsToRelation
     */
    public function getDocumentRelation(DocumentInterface $document, SectionInterface $relatedSection = null, FieldInterface $relatedField = null)
    {
        return new BelongsToRelation(
            $relatedSection->getEmptyDocument()->newQuery(),
            $document,
            $this->getRelatedField()->getRelatedDBKey(),
            $this->getSection()->getDocumentPrimaryKey(),
            $this->getRelationName()
        );
    }
}

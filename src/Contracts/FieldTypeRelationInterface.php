<?php

namespace KodiCMS\Datasource\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

interface FieldTypeRelationInterface
{
    /**
     * @param DocumentInterface $document
     * @param SectionInterface $relatedSection
     * @param FieldInterface|null $relatedField
     *
     * @return Relation
     */
    public function getDocumentRelation(DocumentInterface $document, SectionInterface $relatedSection = null, FieldInterface $relatedField = null);

    /**
     * @param DocumentInterface $document
     */
    public function onRelatedDocumentDeleting(DocumentInterface $document);

    /**
     * @param DocumentInterface $document
     */
    public function onRelatedDocumentDeleted(DocumentInterface $document);

    /**
     * @param DocumentInterface $document
     */
    public function onUpdateDocumentRelations(DocumentInterface $document);

    /**
     * @return string
     */
    public function getRelationName();

    /**
     * @return SectionInterface
     */
    public function getRelatedSection();

    /**
     * @return FieldInterface
     */
    public function getRelatedField();
}

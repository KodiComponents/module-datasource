<?php

namespace KodiCMS\Datasource\Fields\Relation;

use Schema;
use KodiCMS\Datasource\Fields\Relation;
use Illuminate\Database\Eloquent\Builder;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;
use KodiCMS\Datasource\Repository\FieldRepository;
use KodiCMS\Datasource\Contracts\DocumentInterface;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyRelation;

class ManyToMany extends Relation
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
     * @var string|null
     */
    protected $relatedFieldKey = null;

    /**
     * @var string
     */
    protected $relatedFieldType = 'many_to_many';

    /**
     * @param DocumentInterface $document
     * @param mixed             $value
     *
     * @return mixed
     */
    public function onGetHeadlineValue(DocumentInterface $document, $value)
    {
        $documents = $document->getAttribute($this->getRelationName())->map(function ($doc) {
            return link_to($doc->getEditLink(), $doc->getTitle(), ['class' => 'popup']);
        })->all();

        return ! empty($documents) ? implode(', ', $documents) : null;
    }

    /**
     * @param DocumentInterface   $document
     * @param SectionInterface    $relatedSection
     * @param FieldInterface|null $relatedField
     *
     * @return \Illuminate\Database\Eloquent\Relations\ManyToMany
     */
    public function getDocumentRelation(
        DocumentInterface $document, SectionInterface $relatedSection = null, FieldInterface $relatedField = null
    ) {
        $relatedDocument = $relatedSection->getEmptyDocument();
        $builder = $relatedDocument->newQuery();

        return new BelongsToManyRelation(
            $builder,
            $document,
            $this->getRelatedTable(),
            $this->getDBKey(),
            $relatedField->getDBKey(),
            $this->getRelationName()
        );
    }

    /**
     * @param DocumentInterface $document
     *
     * @return array
     */
    public function getRelatedDocumentValues(DocumentInterface $document)
    {
        if (! is_null($relatedField = $this->getRelatedField())) {
            $section = $relatedField->getSection();

            return $this->getDocumentRelation($document, $section, $relatedField)
                ->pluck($section->getDocumentTitleKey(), $section->getDocumentPrimaryKey());
        }

        return new Collection();
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
     * @param DocumentInterface $document
     * @param mixed             $value
     *
     * @return mixed
     */
    public function onSetDocumentAttribute(DocumentInterface $document, $value)
    {
        $this->selectedDocuments = $value;
    }

    /**
     * @param DocumentInterface $document
     * @param mixed             $value
     */
    public function onDocumentSaved(DocumentInterface $document, $value)
    {
        $document->{$this->getRelationName()}()->sync((array) $this->selectedDocuments);
        parent::onDocumentSaved($document, $value);
    }

    /**
     * @param DocumentInterface $document
     */
    public function onDocumentDeleted(DocumentInterface $document)
    {
        $document->{$this->getRelationName()}()->detach($document->getId());
    }

    /**
     * @param DocumentInterface $document
     */
    public function onRelatedDocumentDeleted(DocumentInterface $document)
    {
        $document->{$this->getRelatedField()->getRelationName()}()->detach($document->getId());
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

        $relatedTable = 'ds_mtm_'.uniqid();
        $relatedField = null;

        if (is_null($this->relatedFieldKey) or is_null($relatedSection = $this->getRelatedSection()) or is_null($relatedField = $relatedSection->getFields()->getByKey($this->relatedFieldKey))) {
            $relatedField = $repository->create([
                'type'               => $this->relatedFieldType,
                'section_id'         => $this->getRelatedSectionId(),
                'is_system'          => 1,
                'key'                => $this->getRelatedDBKey(),
                'name'               => $this->getSection()->getName(),
                'related_section_id' => $this->getSection()->getId(),
                'related_field_id'   => $this->getId(),
                'related_table'      => $relatedTable,
                'settings'           => $this->getSettings(),
            ]);
        }

        if (! is_null($relatedField)) {
            Schema::create($relatedTable, function ($table) use ($relatedField, $relatedTable) {
                $table->integer($this->getDBKey());
                $table->integer($relatedField->getDBKey());

                $table->primary([$this->getDBKey(), $relatedField->getDBKey()], $relatedTable);
            });

            $this->update([
                'related_field_id' => $relatedField->getId(),
                'related_table'    => $relatedTable,
            ]);
        }
    }

    /**
     * @param FieldRepository $repository
     */
    public function onDeleted(FieldRepository $repository)
    {
        if (! is_null($relatedField = $this->getRelatedField())) {
            $relatedField->delete();
        }

        if (! is_null($this->getRelatedTable())) {
            Schema::dropIfExists($this->getRelatedTable());
        }
    }

    /**
     * @param DocumentInterface $document
     *
     * @return array
     */
    protected function fetchDocumentTemplateValues(DocumentInterface $document)
    {
        $relatedSection = $this->getRelatedSection();
        $relatedField = $this->getRelatedField();

        return [
            'value'            => $this->getRelatedDocumentValues($document),
            'document'         => $document,
            'section'          => $document->getSection(),
            'relatedDocuments' => $this->getDocumentRelation($document, $relatedSection, $relatedField)->get(),
            'relatedSection'   => $relatedSection,
            'relatedField'     => $relatedField,
        ];
    }
}

<?php

namespace KodiCMS\Datasource\Observers;

use KodiCMS\Datasource\Model\Document;

class DocumentObserver
{

    /**
     * @param Document $document
     */
    public function created(Document $document)
    {
        foreach ($document->getFields() as $key => $field) {
            $field->onDocumentCreated($document, $document->getAttribute($key));
        }
    }

    /**
     * @param Document $document
     */
    public function updated(Document $document)
    {
        foreach ($document->getFields() as $key => $field) {
            $field->onDocumentUpdated($document, $document->getAttribute($key));
        }
    }

    /**
     * @param Document $document
     */
    public function saving(Document $document)
    {
        foreach ($document->getFields() as $key => $field) {
            if ($document->exists) {
                $field->onDocumentUpdating($document, $document->getAttribute($key));
            } else {
                $field->onDocumentCreating($document, $document->getAttribute($key));
            }
        }
    }

    /**
     * @param Document $document
     */
    public function saved(Document $document)
    {
        foreach ($document->getFields() as $key => $field) {
            $field->onDocumentSaved($document, $document->getAttribute($key));
        }
    }

    /**
     * @param Document $document
     */
    public function deleting(Document $document)
    {
        foreach ($document->getFields() as $key => $field) {
            $field->onDocumentDeleting($document);
        }

        foreach ($document->getSection()->getRelatedFields()->getFields() as $key => $field) {
            $field->onRelatedDocumentDeleting($document);
        }
    }

    /**
     * @param Document $document
     */
    public function deleted(Document $document)
    {
        foreach ($document->getFields() as $key => $field) {
            $field->onDocumentDeleted($document);
        }

        foreach ($document->getSection()->getRelatedFields()->getFields() as $key => $field) {
            $field->onRelatedDocumentDeleted($document);
        }
    }
}

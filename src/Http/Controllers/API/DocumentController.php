<?php

namespace KodiCMS\Datasource\Http\Controllers\API;

use KodiCMS\API\Http\Controllers\System\Controller;
use KodiCMS\Datasource\Repository\DocumentRepository;

class DocumentController extends Controller
{
    /**
     * @param DocumentRepository $repository
     */
    public function deleteDelete(DocumentRepository $repository)
    {
        $docIds = $this->getRequiredParameter('document');
        $sectionId = $this->getRequiredParameter('section_id');

        $repository->deleteBySectionId($sectionId, $docIds);
    }

    /**
     * @param DocumentRepository $repository
     */
    public function getFind(DocumentRepository $repository)
    {
        $sectionId = $this->getRequiredParameter('section_id');
        $keyword = $this->getParameter('q');
        $excludeIds = (array) $this->getParameter('exclude', []);

        $documents = $repository->findByKeyword($sectionId, $keyword, $excludeIds);

        $this->setContent($documents);
    }
}

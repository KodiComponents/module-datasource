<?php

namespace KodiCMS\Datasource\Http\Controllers\API;

use DatasourceManager;
use KodiCMS\Datasource\Model\SectionFolder;
use KodiCMS\Datasource\Repository\SectionRepository;
use KodiCMS\API\Http\Controllers\System\Controller;

class MenuController extends Controller
{
    /**
     * @param SectionRepository $repository
     */
    public function getMenu(SectionRepository $repository)
    {
        $sectionId = $this->getRequiredParameter('section_id');

        $this->setContent(
            view('datasource::sections-list', [
                'sections' => DatasourceManager::getSections(),
                'folders' => SectionFolder::with('sections')->get(),
                'currentSection' => $repository->findOrFail($sectionId)
            ])
        );
    }

    /**
     * @param SectionRepository $repository
     */
    public function addSectionToFolder(SectionRepository $repository)
    {
        $sectionId = $this->getRequiredParameter('section_id');
        $folderId = $this->getRequiredParameter('folder_id');

        $repository->findOrFail($sectionId)->update([
           'folder_id' => $folderId
        ]);

        $this->setContent(true);
    }

    public function getFolderById()
    {
        $folderId = $this->getRequiredParameter('folder_id');
        $this->setContent(SectionFolder::findOrFail($folderId));
    }

    public function createFolder()
    {
        $name = $this->getRequiredParameter('name');

        $section = SectionFolder::create([
            'name' => $name
        ]);

        $this->setContent($section);
    }

    public function deleteFolder()
    {
        $folderId = $this->getRequiredParameter('folder_id');

        if (SectionFolder::findOrFail($folderId)->delete()) {
            $this->setContent(true);
        } else {
            $this->setContent(false);
        }
    }
}

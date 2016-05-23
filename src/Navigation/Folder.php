<?php

namespace KodiCMS\Datasource\Navigation;

use KodiCMS\Datasource\Model\SectionFolder;

class Folder extends \KodiCMS\CMS\Navigation\Page
{
    /**
     * @var SectionFolder
     */
    private $folder;

    /**
     * Page constructor.
     *
     * @param SectionFolder $folder
     */
    public function __construct(SectionFolder $folder)
    {
        parent::__construct();

        $this->folder = $folder;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'datasource-folder-'.$this->folder->id;
    }

    /**
     * @return bool
     */
    public function getAccessLogic()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->folder->name;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        $this->setIcon('folder-open-o');

        return parent::getIcon();
    }
}
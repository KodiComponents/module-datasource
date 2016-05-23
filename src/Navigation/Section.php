<?php

namespace KodiCMS\Datasource\Navigation;

use KodiCMS\Datasource\Contracts\SectionInterface;

class Section extends \KodiCMS\CMS\Navigation\Page
{
    /**
     * @var SectionInterface
     */
    private $section;

    /**
     * Page constructor.
     *
     * @param SectionInterface $section
     */
    public function __construct(SectionInterface $section)
    {
        parent::__construct();

        $this->section = $section;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'datasource-section-'.$this->section->getId();
    }

    /**
     * @return bool
     */
    public function getAccessLogic()
    {
        return $this->section->userHasAccessView();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->section->getName();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->section->getLink();
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        $this->setIcon($this->section->getIcon());
        return parent::getIcon();
    }
}
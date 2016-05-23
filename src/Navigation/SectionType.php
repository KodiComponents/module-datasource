<?php

namespace KodiCMS\Datasource\Navigation;

use KodiCMS\Datasource\Contracts\SectionTypeInterface;

class SectionType extends \KodiCMS\CMS\Navigation\Page
{
    /**
     * @var SectionTypeInterface
     */
    private $section;

    /**
     * Page constructor.
     *
     * @param SectionTypeInterface $section
     */
    public function __construct(SectionTypeInterface $section)
    {
        parent::__construct();

        $this->section = $section;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'datasource-section-'.uniqid();
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
        return $this->section->getTitle();
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
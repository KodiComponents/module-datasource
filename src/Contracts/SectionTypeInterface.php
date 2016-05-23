<?php

namespace KodiCMS\Datasource\Contracts;

interface SectionTypeInterface
{
    /**
     * @return bool
     */
    public function isExists();

    /**
     * @return bool
     */
    public function isDocumentClassExists();

    /**
     * @return string
     */
    public function getClass();

    /**
     * @return string
     */
    public function getDocumentClassName();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getIcon();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getCreateTemplate();

    /**
     * @return string
     */
    public function getEditTemplate();

    /**
     * @return string
     */
    public function getLink();
}

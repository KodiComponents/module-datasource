<?php

namespace KodiCMS\Datasource\Sections;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use KodiCMS\Datasource\Contracts\FieldInterface;
use KodiCMS\Datasource\Contracts\SectionHeadlineInterface;
use KodiCMS\Datasource\Contracts\SectionInterface;

class SectionHeadline implements SectionHeadlineInterface
{
    /**
     * @var SectionInterface
     */
    protected $section;

    /**
     * @var Collection
     */
    protected $fields;

    /**
     * @var Collection
     */
    protected $sectionFields;

    /**
     * @var int
     */
    protected $perPage = 20;

    /**
     * @var int
     */
    protected $currentPage;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var string
     */
    protected $template = 'datasource::section.headline';

    /**
     * @param SectionInterface $section
     */
    public function __construct(SectionInterface $section)
    {
        $this->section = $section;
        $this->sectionFields = $section->getFields()->getFields();

        $this->fields = new Collection();
        foreach ($this->sectionFields as $field) {
            if (! $field->isVisible()) {
                continue;
            }

            $params = $field->getHeadlineParameters($this);

            if ($this->section->getDocumentTitleKey() == $field->getDBKey()) {
                $params['type'] = 'link';
            }

            $this->fields->put($field->getKey(), $params);
        }
    }

    /**
     * @return Collection
     */
    public function getHeadlineFields()
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getActiveFieldIds()
    {
        return $this->sectionFields->filter(function(FieldInterface $field) {
            return $field->isVisible();
        })->pluck('key');
    }

    /**
     * @return array
     */
    public function getSearchableFields()
    {
        return $this->sectionFields->filter(function(FieldInterface $field) {
            return $field->isVisible() and $field->isSearchable();
        })->pluck('name');
    }

    /**
     * @return array
     */
    public function getOrderingRules()
    {
        return $this->section->getHeadlineOrdering();
    }

    /**
     * @return array
     */
    public function getDocuments()
    {
        return $this->section->getEmptyDocument()
            ->getDocuments($this->getActiveFieldIds(), $this->getOrderingRules())
            ->paginate();
    }

    /**
     * @return JsonResponse
     */
    public function JsonResponse()
    {
        return new JsonResponse($this->render());
    }

    /**
     * @param string|null $template
     *
     * @return \Illuminate\View\View
     */
    public function render($template = null)
    {
        if (is_null($template)) {
            if (method_exists($this->section, 'getHeadlineTemplate')) {
                $template = $this->section->getHeadlineTemplate();
            } else {
                $template = $template = $this->template;
            }
        }

        return view($template, [
            'fieldParams' => $this->getHeadlineFields(),
            'items'       => $this->getDocuments(),
            'section'     => $this->section,
        ])->render();
    }

    /**
     * @return \Illuminate\View\View
     */
    public function renderOrderSettings()
    {
        return view('datasource::widgets.partials.ordering', [
            'ordering' => $this->getOrderingRules(),
            'fields'   => $this->sectionFields,
        ])->render();
    }
}

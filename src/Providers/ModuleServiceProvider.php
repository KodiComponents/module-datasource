<?php

namespace KodiCMS\Datasource\Providers;

use KodiCMS\Datasource\Model\SectionFolder;
use KodiCMS\Datasource\Navigation\Folder;
use KodiCMS\Datasource\Navigation\Section;
use KodiCMS\Datasource\Navigation\SectionType;
use Yajra\Datatables\Datatables;
use KodiCMS\Support\ServiceProvider;
use KodiCMS\Datasource\FieldManager;
use KodiCMS\Datasource\FieldGroupManager;
use KodiCMS\Datasource\DatasourceManager;
use KodiCMS\Datasource\Console\Commands\DatasourceMigrate;
use KodiCMS\Datasource\Facades\FieldManager as FieldManagerFacade;
use KodiCMS\Datasource\Facades\FieldGroupManager as FieldGroupManagerFacade;
use KodiCMS\Datasource\Facades\DatasourceManager as DatasourceManagerFacade;

class ModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerAliases([
            'DatasourceManager' => DatasourceManagerFacade::class,
            'FieldManager'      => FieldManagerFacade::class,
            'Datatables'        => Datatables::class,
            'FieldGroupManager' => FieldGroupManagerFacade::class
        ]);

        $this->app->singleton('datasource.manager', function () {
            $manager = new DatasourceManager();

            $types = [
                'default'    => [
                    'class' => \KodiCMS\Datasource\Sections\DefaultSection\Section::class,
                    'title' => 'datasource::sections.default.title',
                ],
                'images'     => [
                    'class' => \KodiCMS\Datasource\Sections\Images\Section::class,
                    'title' => 'datasource::sections.images.title',
                    'icon'  => 'image',
                ]
            ];

            foreach ($types as $type => $settings) {
                $manager->registerSectionType($type, $settings);
            }

            return $manager;
        });

        $this->app->singleton('datasource.field.manager', function () {
            return new FieldManager(config('fields', []));
        });

        $this->app->singleton('datasource.group.manager', function () {
            return new FieldGroupManager(config('field_groups', []));
        });

        $this->registerConsoleCommand(DatasourceMigrate::class);
    }

    public function boot()
    {
        \Event::listen('config.loaded', function () {
            $this->initNavigation();
        }, 999);
    }

    protected function initNavigation()
    {
        $datasourcePage = \Navigation::addPage([
            'id' => 'datasource',
            'title' => 'datasource::core.title.section',
            'priority' => 500,
            'icon' => 'tasks',
        ]);

        $sections = app('datasource.manager')->getRootSections();

        foreach ($sections as $dsSection) {
            $page = new Section($dsSection);

            if ($dsSection->getSetting('show_in_root_menu')) {
                \Navigation::addPage($page);
            } else {
                $datasourcePage->addPage($page);
            }
        }

        $folders = SectionFolder::with('sections')->get();

        foreach ($folders as $folder) {
            if (count($folder->sections) > 0) {
                $folderPage = $datasourcePage->addPage(new Folder($folder));
                foreach ($folder->sections as $dsSection) {
                    $folderPage->addPage(new Section($dsSection));
                }
            }
        }

        if (count($types = app('datasource.manager')->getAvailableTypes()) > 0) {
            $create = $datasourcePage->addPage([
                'title' => 'datasource::core.button.create',
                'icon' => 'plus',
                'id' => 'datasource-create',
            ]);

            foreach ($types as $type => $object) {
                $create->addPage(new SectionType($object));
            }
        }
    }
}

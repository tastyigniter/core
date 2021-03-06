<?php

namespace Igniter\Admin\Controllers\Actions;

use Igniter\Admin\Facades\Template;
use Igniter\Admin\Traits\ListExtendable;
use Igniter\System\Classes\ControllerAction;

/**
 * List Controller Class
 */
class ListController extends ControllerAction
{
    use ListExtendable;

    /**
     * @var string The primary list alias to use.
     */
    protected $primaryAlias = 'list';

    /**
     * Define controller list configuration array.
     *  $listConfig = [
     *      'list'  => [
     *          'title'         => 'lang:text_title',
     *          'emptyMessage' => 'lang:igniter::admin.text_empty',
     *          'configFile'   => null,
     *          'showSetup'         => TRUE,
     *          'showSorting'       => TRUE,
     *          'showCheckboxes'    => TRUE,
     *          'defaultSort'  => [
     *              'primary_key', 'DESC'
     *          ],
     *      ],
     *  ];
     * @var array
     */
    public $listConfig;

    /**
     * @var \Igniter\Admin\Widgets\Lists[] Reference to the list widget objects
     */
    protected $listWidgets;

    /**
     * @var \Igniter\Admin\Widgets\Toolbar[] Reference to the toolbar widget objects.
     */
    protected $toolbarWidget;

    /**
     * @var \Igniter\Admin\Widgets\Filter[] Reference to the filter widget objects.
     */
    protected $filterWidgets = [];

    protected $requiredProperties = ['listConfig'];

    /**
     * @var array Required controller configuration array keys
     */
    protected $requiredConfig = ['model', 'configFile'];

    /**
     * List_Controller constructor.
     *
     * @param \Illuminate\Routing\Controller $controller
     *
     * @throws \Exception
     */
    public function __construct($controller)
    {
        parent::__construct($controller);

        $this->listConfig = $controller->listConfig;
        $this->primaryAlias = key($controller->listConfig);

        // Build configuration
        $this->setConfig($controller->listConfig[$this->primaryAlias], $this->requiredConfig);

        $this->hideAction([
            'index_onDelete',
            'renderList',
            'refreshList',
            'getListWidget',
            'listExtendColumns',
            'listExtendModel',
            'listExtendQueryBefore',
            'listExtendQuery',
            'listFilterExtendQuery',
            'listOverrideColumnValue',
            'listOverrideHeaderValue',
        ]);
    }

    public function index()
    {
        $pageTitle = lang($this->getConfig('title', 'lang:text_title'));
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->makeLists();
    }

    public function index_onDelete()
    {
        $checkedIds = post('checked');
        if (!$checkedIds || !is_array($checkedIds) || !count($checkedIds)) {
            flash()->success(lang('igniter::admin.list.delete_empty'));

            return $this->controller->refreshList();
        }

        if (!$alias = post('alias'))
            $alias = $this->primaryAlias;

        $listConfig = $this->makeConfig($this->listConfig[$alias], $this->requiredConfig);

        $modelClass = $listConfig['model'];
        $model = new $modelClass;
        $model = $this->controller->listExtendModel($model, $alias);

        $query = $model->newQuery();
        $this->controller->listExtendQueryBefore($query, $alias);

        $query->whereIn($model->getKeyName(), $checkedIds);
        $records = $query->get();

        // Delete records
        if ($count = $records->count()) {
            foreach ($records as $record) {
                $record->delete();
            }

            $prefix = ($count > 1) ? ' records' : 'record';
            flash()->success(sprintf(lang('igniter::admin.alert_success'), '['.$count.']'.$prefix.' '.lang('igniter::admin.text_deleted')));
        }
        else {
            flash()->warning(sprintf(lang('igniter::admin.alert_error_nothing'), lang('igniter::admin.text_deleted')));
        }

        return $this->controller->refreshList($alias);
    }

    /**
     * Creates all the widgets based on the model config.
     *
     * @return array List of Igniter\Admin\Classes\BaseWidget objects
     */
    public function makeLists()
    {
        $this->listWidgets = [];

        foreach ($this->listConfig as $alias => $config) {
            $this->listWidgets[$alias] = $this->makeList($alias);
        }

        return $this->listWidgets;
    }

    /**
     * Prepare the widgets used by this action
     *
     * @param $alias
     *
     * @return \Igniter\Admin\Classes\BaseWidget
     */
    public function makeList($alias)
    {
        if (!$alias || !isset($this->listConfig[$alias]))
            $alias = $this->primaryAlias;

        $listConfig = $this->controller->getListConfig($alias);

        $modelClass = $listConfig['model'];
        $model = new $modelClass;
        unset($listConfig['model']);
        $model = $this->controller->listExtendModel($model, $alias);

        // Prep the list widget config
        $requiredConfig = ['list'];
        $configFile = $listConfig['configFile'];
        $modelConfig = $this->loadConfig($configFile, $requiredConfig, 'list');

        $columnConfig['bulkActions'] = $modelConfig['bulkActions'] ?? [];
        $columnConfig['columns'] = $modelConfig['columns'];
        $columnConfig['model'] = $model;
        $columnConfig['alias'] = $alias;

        $widget = $this->makeWidget(\Igniter\Admin\Widgets\Lists::class, array_merge($columnConfig, $listConfig));

        $widget->bindEvent('list.extendColumns', function () use ($widget) {
            $this->controller->listExtendColumns($widget);
        });

        $widget->bindEvent('list.extendQueryBefore', function ($query) use ($alias) {
            $this->controller->listExtendQueryBefore($query, $alias);
        });

        $widget->bindEvent('list.extendQuery', function ($query) use ($alias) {
            $this->controller->listExtendQuery($query, $alias);
        });

        $widget->bindEvent('list.overrideColumnValue', function ($record, $column, $value) use ($alias) {
            return $this->controller->listOverrideColumnValue($record, $column, $alias);
        });

        $widget->bindEvent('list.overrideHeaderValue', function ($column, $value) use ($alias) {
            return $this->controller->listOverrideHeaderValue($column, $alias);
        });

        $widget->bindToController();

        // Prep the optional toolbar widget
        if (isset($this->controller->widgets['toolbar']) && (isset($listConfig['toolbar']) || isset($modelConfig['toolbar']))) {
            $this->toolbarWidget = $this->controller->widgets['toolbar'];
            if ($this->toolbarWidget instanceof \Igniter\Admin\Widgets\Toolbar)
                $this->toolbarWidget->reInitialize($listConfig['toolbar'] ?? $modelConfig['toolbar']);
        }

        // Prep the optional filter widget
        if (array_get($modelConfig, 'filter')) {
            $filterConfig = $modelConfig['filter'];
            $filterConfig['alias'] = "{$widget->alias}_filter";
            $filterWidget = $this->makeWidget(\Igniter\Admin\Widgets\Filter::class, $filterConfig);
            $filterWidget->bindToController();

            if ($searchWidget = $filterWidget->getSearchWidget()) {
                $searchWidget->bindEvent('search.submit', function () use ($widget, $searchWidget) {
                    $widget->setSearchTerm($searchWidget->getActiveTerm());

                    return $widget->onRefresh();
                });

                $widget->setSearchOptions([
                    'mode' => $searchWidget->mode,
                    'scope' => $searchWidget->scope,
                ]);

                // Find predefined search term
                $widget->setSearchTerm($searchWidget->getActiveTerm());
            }

            $filterWidget->bindEvent('filter.submit', function () use ($widget) {
                return $widget->onRefresh();
            });

            $filterWidget->bindEvent('filter.extendScopesBefore', function () use ($filterWidget) {
                $this->controller->listFilterExtendScopesBefore($filterWidget);
            });

            $filterWidget->bindEvent('filter.extendScopes', function ($scopes) use ($filterWidget) {
                $this->controller->listFilterExtendScopes($filterWidget, $scopes);
            });

            $filterWidget->bindEvent('filter.extendQuery', function ($query, $scope) {
                $this->controller->listFilterExtendQuery($query, $scope);
            });

            // Apply predefined filter values
            $widget->addFilter([$filterWidget, 'applyAllScopesToQuery']);

            $this->filterWidgets[$alias] = $filterWidget;
        }

        return $widget;
    }

    public function renderList($alias = null)
    {
        if (is_null($alias) || !isset($this->listConfig[$alias]))
            $alias = $this->primaryAlias;

        $list = [];

        if (!is_null($this->toolbarWidget)) {
            $list[] = $this->toolbarWidget->render();
        }

        if (isset($this->filterWidgets[$alias])) {
            $list[] = $this->filterWidgets[$alias]->render();
        }

        $list[] = $this->listWidgets[$alias]->render();

        return implode(PHP_EOL, $list);
    }

    public function refreshList($alias = null)
    {
        if (!$this->listWidgets) {
            $this->makeLists();
        }

        if (!$alias || !isset($this->listConfig[$alias])) {
            $alias = $this->primaryAlias;
        }

        return $this->listWidgets[$alias]->onRefresh();
    }

    /**
     * Returns the widget used by this behavior.
     *
     * @param string $alias
     *
     * @return \Igniter\Admin\Classes\BaseWidget
     */
    public function getListWidget($alias = null)
    {
        if (!$alias) {
            $alias = $this->primaryAlias;
        }

        return array_get($this->listWidgets, $alias);
    }

    /**
     * Returns the configuration used by this behavior.
     *
     * @param null $alias
     *
     * @return \Igniter\Admin\Classes\BaseWidget
     */
    public function getListConfig($alias = null)
    {
        if (!$alias) {
            $alias = $this->primaryAlias;
        }

        if (!$listConfig = array_get($this->listConfig, $alias)) {
            $listConfig = $this->listConfig[$alias] = $this->makeConfig($this->listConfig[$alias], $this->requiredConfig);
        }

        return $listConfig;
    }
}

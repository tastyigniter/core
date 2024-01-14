<?php

namespace Igniter\Admin\Widgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Admin\Classes\BaseWidget;
use Igniter\Admin\Classes\Widgets;
use Igniter\Flame\Exception\FlashException;
use Igniter\User\Models\UserPreference;

class DashboardContainer extends BaseWidget
{
    //
    // Configurable properties
    //

    /**
     * The unique dashboard context name
     * Defines the context where the container is used.
     * Widget settings are saved in a specific context.
     */
    public string $context = 'dashboard';

    /** Determines whether widgets could be added and deleted. */
    public bool $canManage = true;

    /** Determines whether widgets could be set as default. */
    public bool $canSetDefault = false;

    /**
     * A list of default widgets to load.
     * This structure could be defined in the controller containerConfig property
     * Example structure:
     *
     * public $containerConfig = [
     *     'trafficOverview' => [
     *         'class' => Igniter\GoogleAnalytics\DashboardWidgets\TrafficOverview::class,
     *         'priority' => 1,
     *         'config' => [
     *             title => 'Traffic overview',
     *             width => 10,
     *          ],
     *     ]
     * ];
     */
    public array $defaultWidgets = [];

    //
    // Object properties
    //

    protected string $defaultAlias = 'dashboardContainer';

    /** Collection of all dashboard widgets used by this container. */
    protected array $dashboardWidgets = [];

    /** Determines if dashboard widgets have been created. */
    protected bool $widgetsDefined = false;

    public function __construct(AdminController $controller, array $config = [])
    {
        parent::__construct($controller, $config);

        $this->fillFromConfig();
        $this->bindToController();
    }

    /**
     * Ensure dashboard widgets are registered so they can also be bound to
     * the controller this allows their AJAX features to operate.
     * @return void
     */
    public function bindToController()
    {
        $this->defineDashboardWidgets();
        parent::bindToController();
    }

    /**
     * Renders this widget along with its collection of dashboard widgets.
     */
    public function render()
    {
        return $this->makePartial('dashboardcontainer/dashboardcontainer');
    }

    public function loadAssets()
    {
        $this->addCss('dashboardcontainer.css');
        $this->addJs('dashboardcontainer.js');
    }

    //
    // Event handlers
    //

    public function onRenderWidgets(): array
    {
        $this->defineDashboardWidgets();
        $this->vars['widgets'] = $this->dashboardWidgets;

        return ['#'.$this->getId('container') => $this->makePartial('dashboardcontainer/widget_container')];
    }

    public function onLoadAddPopup(): array
    {
        $this->vars['gridColumns'] = $this->getWidgetPropertyWidthOptions();
        $this->vars['widgets'] = resolve(Widgets::class)->listDashboardWidgets();

        return ['#'.$this->getId('new-widget-modal-content') => $this->makePartial('new_widget_popup')];
    }

    public function onLoadUpdatePopup(): array
    {
        $widgetAlias = trim(post('widgetAlias'));

        if (!$widgetAlias) {
            throw FlashException::error(lang('igniter::admin.dashboard.alert_select_widget_to_update'));
        }

        $this->vars['widgetAlias'] = $widgetAlias;
        $this->vars['widget'] = $widget = $this->findWidgetByAlias($widgetAlias);
        $this->vars['widgetForm'] = $this->getFormWidget($widgetAlias, $widget);

        return ['#'.$widgetAlias.'-modal-content' => $this->makePartial('widget_form')];
    }

    public function onAddWidget(): array
    {
        $className = trim(post('className'));
        $size = trim(post('size'));

        if (!$className) {
            throw FlashException::error(lang('igniter::admin.dashboard.alert_select_widget_to_add'));
        }

        if (!class_exists($className)) {
            throw FlashException::error(lang('igniter::admin.dashboard.alert_widget_class_not_found'));
        }

        $widget = new $className($this->controller);
        if (!($widget instanceof \Igniter\Admin\Classes\BaseDashboardWidget)) {
            throw FlashException::error(lang('igniter::admin.dashboard.alert_invalid_widget'));
        }

        $widgetInfo = $this->addWidget($widget, $size);

        return [
            '@#'.$this->getId('container-list') => $this->makePartial('widget_item', [
                'widget' => $widget,
                'widgetAlias' => $widgetInfo['alias'],
                'priority' => $widgetInfo['priority'],
            ]),
        ];
    }

    public function onResetWidgets(): array
    {
        if (!$this->canManage) {
            throw FlashException::error(lang('igniter::admin.alert_access_denied'));
        }

        $this->resetWidgets();

        $this->vars['widgets'] = $this->dashboardWidgets;

        flash()->success(lang('igniter::admin.dashboard.alert_reset_layout_success'));

        return ['#'.$this->getId('container-list') => $this->makePartial('widget_list')];
    }

    public function onSetAsDefault()
    {
        if (!$this->canSetDefault) {
            throw FlashException::error(lang('igniter::admin.alert_access_denied'));
        }

        $widgets = $this->getWidgetsFromUserPreferences();

        params()->set($this->getSystemParametersKey(), $widgets);

        flash()->success(lang('igniter::admin.dashboard.make_default_success'));
    }

    public function onUpdateWidget(): array
    {
        if (!$this->canManage) {
            throw FlashException::error(lang('igniter::admin.alert_access_denied'));
        }

        $alias = post('alias');

        $widget = $this->findWidgetByAlias($alias);

        $widget->setProperties(post($alias.'_fields'));

        $this->saveWidgetProperties($alias, $widget->getProperties());

        $widget->initialize();

        $this->widgetsDefined = false;

        return $this->onRenderWidgets();
    }

    public function onRemoveWidget()
    {
        $alias = post('alias');

        $this->removeWidget($alias);
    }

    protected function addWidget(BaseDashboardWidget $widget, mixed $size): array
    {
        if (!$this->canManage) {
            throw FlashException::error(lang('igniter::admin.alert_access_denied'));
        }

        $widgets = $this->getWidgetsFromUserPreferences();

        $priority = 0;
        foreach ($widgets as $widgetInfo) {
            $priority = max($priority, $widgetInfo['priority']);
        }

        $priority++;

        $widget->setProperty('width', $size);

        $alias = $this->getUniqueAlias($widgets);

        $widgets[$alias] = [
            'class' => get_class($widget),
            'config' => $widget->getProperties(),
            'priority' => $priority,
        ];

        $this->setWidgetsToUserPreferences($widgets);

        return [
            'alias' => $alias,
            'priority' => $widgets[$alias]['priority'],
        ];
    }

    public function onSetWidgetPriorities()
    {
        $aliases = trim(post('aliases'));
        $priorities = trim(post('priorities'));

        if (!$aliases) {
            throw FlashException::error(lang('igniter::admin.dashboard.alert_invalid_aliases'));
        }

        if (!$priorities) {
            throw FlashException::error(lang('igniter::admin.dashboard.alert_invalid_priorities'));
        }

        $aliases = explode(',', $aliases);
        $priorities = explode(',', $priorities);

        if (count($aliases) != count($priorities)) {
            throw FlashException::error(lang('igniter::admin.dashboard.alert_invalid_data_posted'));
        }

        $widgets = $this->getWidgetsFromUserPreferences();
        foreach ($aliases as $index => $alias) {
            if (isset($widgets[$alias])) {
                $widgets[$alias]['priority'] = (int)$index;
            }
        }

        $this->setWidgetsToUserPreferences($widgets);

        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Dashboard widgets updated'))->now();
    }

    //
    // Helpers
    //

    /**
     * Registers the dashboard widgets that will be included in this container.
     * The chosen widgets are based on the user preferences.
     */
    protected function defineDashboardWidgets()
    {
        if ($this->widgetsDefined) {
            return;
        }

        $result = [];
        $widgets = $this->getWidgetsFromUserPreferences();
        foreach ($widgets as $alias => $widgetInfo) {
            if ($widget = $this->makeDashboardWidget($alias, $widgetInfo)) {
                $result[$alias] = ['widget' => $widget, 'priority' => $widgetInfo['priority']];
            }
        }

        uasort($result, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        $this->dashboardWidgets = $result;

        $this->widgetsDefined = true;
    }

    protected function makeDashboardWidget(string $alias, array $widgetInfo)
    {
        $config = $widgetInfo['config'];
        $config['alias'] = $alias;

        $className = $widgetInfo['class'];
        if (!class_exists($className)) {
            return;
        }

        $widget = $this->makeWidget($className, $config);
        $widget->bindToController();

        return $widget;
    }

    protected function resetWidgets()
    {
        $this->resetWidgetsUserPreferences();

        $this->widgetsDefined = false;

        $this->defineDashboardWidgets();
    }

    protected function removeWidget(string $alias)
    {
        if (!$this->canManage) {
            throw FlashException::error(lang('igniter::admin.alert_access_denied'));
        }

        $widgets = $this->getWidgetsFromUserPreferences();

        if (isset($widgets[$alias])) {
            unset($widgets[$alias]);
        }

        $this->setWidgetsToUserPreferences($widgets);
    }

    public function getFormWidget(string $alias, BaseDashboardWidget $widget): Form
    {
        $formConfig['fields'] = $this->getWidgetPropertyConfig($widget);

        $formConfig['model'] = UserPreference::onUser();
        $formConfig['data'] = $this->getWidgetPropertyValues($widget);
        $formConfig['previewMode'] = $this->previewMode;
        $formConfig['alias'] = $this->alias.studly_case('Form_'.$alias);
        $formConfig['arrayName'] = $alias.'_fields';

        /** @var Form $formWidget */
        $formWidget = $this->makeWidget(Form::class, $formConfig);
        $formWidget->bindToController();

        return $formWidget;
    }

    protected function findWidgetByAlias(string $alias): BaseDashboardWidget
    {
        $this->defineDashboardWidgets();

        $widgets = $this->dashboardWidgets;
        if (!isset($widgets[$alias])) {
            throw FlashException::error(lang('igniter::admin.dashboard.alert_widget_not_found'));
        }

        return $widgets[$alias]['widget'];
    }

    protected function getWidgetClassName(BaseDashboardWidget $widget): string
    {
        return get_class($widget);
    }

    protected function getWidgetPropertyConfigTitle(BaseDashboardWidget $widget): ?string
    {
        $config = $this->getWidgetPropertyConfig($widget);

        return array_get($config, 'title');
    }

    protected function getWidgetPropertyConfig(BaseDashboardWidget $widget): array
    {
        $properties = $widget->defineProperties();

        $result = [
            'width' => [
                'property' => 'width',
                'label' => lang('igniter::admin.dashboard.label_widget_columns'),
                'comment' => lang('igniter::admin.dashboard.help_widget_columns'),
                'type' => 'select',
                'options' => $this->getWidgetPropertyWidthOptions(),
            ],
        ];

        foreach ($properties as $name => $params) {
            $propertyType = array_get($params, 'type', 'text');

            if (!$this->checkWidgetPropertyType($propertyType)) {
                continue;
            }

            $property = [
                'property' => $name,
                'label' => isset($params['label']) ? lang($params['label']) : $name,
                'type' => $propertyType,
            ];

            foreach ($params as $key => $value) {
                if (isset($property[$key])) {
                    continue;
                }

                $property[$key] = !is_array($value) ? lang($value) : $value;
            }

            $result[$name] = $property;
        }

        return $result;
    }

    protected function getWidgetPropertyValues(BaseDashboardWidget $widget): array
    {
        $result = [];

        $properties = $widget->defineProperties();
        foreach ($properties as $name => $params) {
            $result[$name] = lang($widget->property($name));
        }

        $result['width'] = $widget->property('width');

        return $result;
    }

    protected function getWidgetPropertyWidthOptions(): array
    {
        $sizes = [];
        for ($i = 1; $i <= 12; $i++) {
            $sizes[$i] = $i;
        }

        return $sizes;
    }

    protected function checkWidgetPropertyType($type): bool
    {
        return in_array($type, [
            'text',
            'number',
            'select',
            'selectlist',
            'switch',
        ]);
    }

    //
    // User Preferences
    //

    protected function getWidgetsFromUserPreferences(): array
    {
        $defaultWidgets = params()->get($this->getSystemParametersKey(), $this->defaultWidgets);

        $widgets = UserPreference::onUser()
            ->get($this->getUserPreferencesKey(), $defaultWidgets);

        if (!is_array($widgets)) {
            return [];
        }

        return $widgets;
    }

    protected function setWidgetsToUserPreferences(array $widgets)
    {
        UserPreference::onUser()->set($this->getUserPreferencesKey(), $widgets);
    }

    protected function resetWidgetsUserPreferences()
    {
        UserPreference::onUser()->reset($this->getUserPreferencesKey());
    }

    protected function saveWidgetProperties(string $alias, array $properties)
    {
        $widgets = $this->getWidgetsFromUserPreferences();

        if (isset($widgets[$alias])) {
            $widgets[$alias]['config'] = $properties;

            $this->setWidgetsToUserPreferences($widgets);
        }
    }

    protected function getUserPreferencesKey(): string
    {
        return 'admin_dashboardwidgets_'.$this->context;
    }

    protected function getSystemParametersKey(): string
    {
        return 'admin_dashboardwidgets_default_'.$this->context;
    }

    protected function getUniqueAlias($widgets): string
    {
        $num = count($widgets);
        do {
            $num++;
            $alias = 'dashboard_container_'.$this->context.'_'.$num;
        } while (array_key_exists($alias, $widgets));

        return $alias;
    }
}

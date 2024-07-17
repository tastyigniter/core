<?php

namespace Igniter\Admin\Widgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Admin\Classes\BaseWidget;
use Igniter\Admin\Classes\Widgets;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Classes\ComponentManager;
use Igniter\User\Models\UserPreference;

class DashboardContainer extends BaseWidget
{
    use ValidatesForm;

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

    public string $dateRangeFormat = 'MMMM D, YYYY hh:mm A';

    public ?\DateTimeInterface $startDate = null;

    public ?\DateTimeInterface $endDate = null;

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
        $this->vars['startDate'] = $this->startDate = $this->getStartDate();
        $this->vars['endDate'] = $this->endDate = $this->getEndDate();
        $this->vars['dateRangeFormat'] = $this->dateRangeFormat;

        return $this->makePartial('dashboardcontainer/dashboardcontainer');
    }

    public function loadAssets()
    {
        $this->addJs('js/vendor.datetime.js', 'vendor-datetime-js');
        $this->addCss('formwidgets/datepicker.css', 'datepicker-css');

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
            throw new FlashException(lang('igniter::admin.dashboard.alert_select_widget_to_update'));
        }

        $this->vars['widgetAlias'] = $widgetAlias;
        $this->vars['widget'] = $widget = $this->findWidgetByAlias($widgetAlias);
        $this->vars['widgetForm'] = $this->getFormWidget($widgetAlias, $widget);

        return ['#'.$widgetAlias.'-modal-content' => $this->makePartial('widget_form')];
    }

    public function onAddWidget(): array
    {
        $validated = $this->validate(request()->post(), [
            'widget' => ['required', 'alpha_dash'],
            'size' => ['nullable', 'integer'],
        ]);

        throw_unless(
            $widgetClass = resolve(Widgets::class)->resolveDashboardWidget($widgetCode = array_get($validated, 'widget')),
            new FlashException(lang('igniter::admin.dashboard.alert_widget_class_not_found'))
        );

        /** @var BaseDashboardWidget $widget */
        $widget = $this->makeWidget($widgetClass, [
            'widget' => $widgetCode,
            'startDate' => $this->getStartDate(),
            'endDate' => $this->getEndDate(),
        ]);

        throw_unless(
            $widget instanceof \Igniter\Admin\Classes\BaseDashboardWidget,
            new FlashException(lang('igniter::admin.dashboard.alert_invalid_widget'))
        );

        $widget->bindToController();

        $widgetAlias = $widgetCode.'_'.str_random(5);
        $this->addWidget($widgetAlias, $widget, array_get($validated, 'size'));

        return [
            '@#'.$this->getId('container-list') => $this->makePartial('widget_item', [
                'widget' => $widget,
                'widgetAlias' => $widgetAlias,
            ]),
        ];
    }

    public function onResetWidgets(): array
    {
        $this->resetWidgets();

        $this->resetSession();

        $this->vars['widgets'] = $this->dashboardWidgets;

        flash()->success(lang('igniter::admin.dashboard.alert_reset_layout_success'));

        return ['#'.$this->getId('container-list') => $this->makePartial('widget_list')];
    }

    public function onSetAsDefault()
    {
        if (!$this->canSetDefault) {
            throw new FlashException(lang('igniter::admin.alert_access_denied'));
        }

        $widgets = $this->getWidgetsFromUserPreferences();

        setting()->setPref($this->getSystemParametersKey(), $widgets);

        flash()->success(lang('igniter::admin.dashboard.make_default_success'));
    }

    public function onUpdateWidget(): array
    {
        $alias = post('alias');

        $widget = $this->findWidgetByAlias($alias);

        [$rules, $attributes] = $widget->getPropertyRules();

        $validated = $this->validate(request()->post($alias.'_fields'), array_merge([
            'width' => ['numeric'],
        ], $rules), $attributes);

        $widget->mergeProperties($validated);

        $this->saveWidgetProperties($alias, $widget->getPropertiesToSave());

        $widget->initialize();

        $this->widgetsDefined = false;

        return $this->onRenderWidgets();
    }

    public function onRemoveWidget()
    {
        $alias = post('alias');

        $this->removeWidget($alias);
    }

    protected function addWidget(string $widgetAlias, BaseDashboardWidget $widget, mixed $size)
    {
        $widgets = $this->getWidgetsFromUserPreferences();

        $nextPriority = collect($widgets)->max('priority') + 1;

        $widget->setProperty('width', $size);
        $widget->setProperty('priority', $nextPriority);

        $widgets[$widgetAlias] = $widget->getPropertiesToSave();

        $this->setWidgetsToUserPreferences($widgets);
    }

    public function onSetWidgetPriorities()
    {
        $validated = $this->validate(request()->post(), [
            'aliases' => ['required', 'array'],
            'aliases.*' => ['alpha_dash'],
        ]);

        $aliases = array_get($validated, 'aliases');

        $this->setWidgetsToUserPreferences(
            collect($this->getWidgetsFromUserPreferences())
                ->mapWithKeys(function($widget, $alias) use ($aliases) {
                    $widget['priority'] = (int)array_search($alias, $aliases);

                    return [$alias => $widget];
                })->all()
        );

        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Dashboard widgets updated'))->now();
    }

    public function onSetDateRange()
    {
        $validated = $this->validate(request()->post(), [
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
        ]);

        $start = make_carbon(array_get($validated, 'start'));
        $end = make_carbon(array_get($validated, 'end'));
        if ($start->isSameDay($end)) {
            $start = $start->startOfDay();
            $end = $end->endOfDay();
        }

        $this->vars['startDate'] = $this->startDate = $start;
        $this->vars['endDate'] = $this->endDate = $end;

        $this->putSession('startDate', $start);
        $this->putSession('endDate', $end);

        $this->widgetsDefined = false;

        return $this->onRenderWidgets();
    }

    //
    // Helpers
    //

    public function getStartDate(): \DateTimeInterface
    {
        return $this->getSession('startDate', now()->startOfDay()->subDays(29));
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->getSession('endDate', now()->startOfDay());
    }

    /**
     * Registers the dashboard widgets that will be included in this container.
     * The chosen widgets are based on the user preferences.
     */
    protected function defineDashboardWidgets()
    {
        if ($this->widgetsDefined) {
            return;
        }

        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $widgets = collect($this->getWidgetsFromUserPreferences())
            ->sortBy('priority')
            ->mapWithKeys(function($widgetInfo, $alias) use ($start, $end) {
                if ($widget = $this->makeDashboardWidget($alias, $widgetInfo)) {
                    $widget->setProperty('startDate', $start);
                    $widget->setProperty('endDate', $end);

                    return [$alias => $widget];
                }

                return [];
            })->filter()->all();

        $this->dashboardWidgets = $widgets;

        $this->widgetsDefined = true;
    }

    protected function makeDashboardWidget(string $alias, array $widgetConfig)
    {
        $widgetConfig['alias'] = $alias;

        $widgetConfig['widget'] = $widgetCode = $widgetConfig['widget'] ?? $widgetConfig['class'] ?? $alias;
        $widgetClass = resolve(Widgets::class)->resolveDashboardWidget($widgetCode);

        return rescue(function() use ($widgetClass, $widgetConfig) {
            $widget = $this->makeWidget($widgetClass, $widgetConfig);
            $widget->bindToController();

            return $widget;
        });
    }

    protected function resetWidgets()
    {
        $this->resetWidgetsUserPreferences();

        $this->widgetsDefined = false;

        $this->defineDashboardWidgets();
    }

    protected function removeWidget(string $alias)
    {
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
            throw new FlashException(lang('igniter::admin.dashboard.alert_widget_not_found'));
        }

        return $widgets[$alias];
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
        return in_array($type, ComponentManager::ALLOWED_PROPERTY_TYPES);
    }

    //
    // User Preferences
    //

    protected function getWidgetsFromUserPreferences(): array
    {
        $defaultWidgets = params($this->getSystemParametersKey(), $this->defaultWidgets);

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
            $widgets[$alias] = $properties;

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

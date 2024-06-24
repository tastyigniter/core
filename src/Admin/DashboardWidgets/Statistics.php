<?php

namespace Igniter\Admin\DashboardWidgets;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Flame\Exception\SystemException;
use Igniter\Local\Traits\LocationAwareWidget;

/**
 * Statistic dashboard widget.
 */
class Statistics extends BaseDashboardWidget
{
    use LocationAwareWidget;

    /** A unique alias to identify this widget. */
    protected string $defaultAlias = 'statistics';

    protected ?array $cardDefinition = null;

    protected static array $registeredCards = [];

    public static function registerCards(\Closure $callback)
    {
        static::$registeredCards[] = $callback;
    }

    /**
     * Renders the widget.
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('statistics/statistics');
    }

    public function defineProperties(): array
    {
        return [
            'card' => [
                'label' => 'igniter::admin.dashboard.text_stats_card',
                'default' => 'sale',
                'type' => 'select',
                'placeholder' => 'igniter::admin.text_please_select',
                'options' => $this->getCardOptions(),
                'validationRule' => 'required|alpha_dash',
            ],
        ];
    }

    public function getActiveCard()
    {
        return $this->property('card', 'sale');
    }

    public function loadAssets()
    {
        $this->addCss('statistics.css', 'statistics-css');
    }

    protected function getCardOptions()
    {
        return array_map(function($context) {
            return array_get($context, 'label');
        }, $this->listCards());
    }

    protected function prepareVars()
    {
        $this->vars['statsContext'] = $context = $this->getActiveCard();
        $this->vars['statsLabel'] = $this->getCardDefinition('label', '--');
        $this->vars['statsColor'] = $this->getCardDefinition('color', 'success');
        $this->vars['statsIcon'] = $this->getCardDefinition('icon', 'fa fa-bar-chart-o');
        $this->vars['statsCount'] = $this->getValue($context);
    }

    protected function listCards()
    {
        $result = [];

        foreach (static::$registeredCards as $callback) {
            foreach ($callback() as $code => $config) {
                $result[$code] = $config;
            }
        }

        return $result;
    }

    protected function getCardDefinition($key, $default = null)
    {
        if (is_null($this->cardDefinition)) {
            $this->cardDefinition = array_get($this->listCards(), $this->getActiveCard());
        }

        return array_get($this->cardDefinition, $key, $default);
    }

    protected function getValue(string $cardCode): string
    {
        $start = $this->property('startDate', now()->subMonth());
        $end = $this->property('endDate', now());

        throw_unless($dataFromCallable = $this->getCardDefinition('valueFrom'), new SystemException(sprintf(
            'The card [%s] does must have a defined valueFrom property', $cardCode
        )));

        $count = $dataFromCallable($cardCode, $start, $end, function($query) use ($start, $end) {
            $this->locationApplyScope($query);
            $query->whereBetween('created_at', [$start, $end]);
        });

        return empty($count) ? 0 : $count;
    }
}

<?php

namespace Igniter\Admin\DashboardWidgets;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Admin\Classes\OnboardingSteps;

/**
 * Onboard dashboard widget.
 */
class Onboarding extends BaseDashboardWidget
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected string $defaultAlias = 'onboarding';

    public function initialize()
    {
        $this->setProperty('cssClass', 'widget-item-onboarding');
    }

    /**
     * Renders the widget.
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('onboarding/onboarding');
    }

    public function loadAssets()
    {
        $this->addCss('onboarding.css', 'onboarding-css');
    }

    protected function prepareVars()
    {
        $this->vars['onboarding'] = $this->getOnboarding();
    }

    protected function getOnboarding(): OnboardingSteps
    {
        return resolve(OnboardingSteps::class);
    }
}

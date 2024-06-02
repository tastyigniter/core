<?php

namespace Igniter\Main\Providers;

use Igniter\Admin\Classes\Widgets;
use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function register()
    {
        resolve(Widgets::class)->registerFormWidgets(function(Widgets $manager) {
            $manager->registerFormWidget(\Igniter\Main\FormWidgets\Components::class, [
                'label' => 'Components',
                'code' => 'components',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\MediaFinder::class, [
                'label' => 'Media finder',
                'code' => 'mediafinder',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\TemplateEditor::class, [
                'label' => 'Template editor',
                'code' => 'templateeditor',
            ]);
        });
    }
}

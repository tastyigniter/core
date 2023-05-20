<?php

namespace Igniter\Admin\EventSubscribers;

use Igniter\Admin\Events\LocationDefineOptionsFieldsEvent;
use Igniter\Admin\Requests\LocationRequest;
use Igniter\System\Classes\FormRequest;
use Illuminate\Contracts\Events\Dispatcher;

class DefineOptionsFormFieldsSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            LocationDefineOptionsFieldsEvent::class => 'handle',
            'system.formRequest.extendValidator' => 'handleValidation',
        ];
    }

    public function handle(LocationDefineOptionsFieldsEvent $event): array
    {
        return [
            'guest_order' => [
                'label' => 'lang:igniter::system.settings.label_guest_order',
                'accordion' => 'lang:igniter::admin.locations.text_tab_general_options',
                'type' => 'radiotoggle',
                'comment' => 'lang:igniter::admin.locations.help_guest_order',
                'default' => -1,
                'options' => [
                    -1 => 'lang:igniter::admin.text_use_default',
                    0 => 'lang:igniter::admin.text_no',
                    1 => 'lang:igniter::admin.text_yes',
                ],
            ],
        ];
    }

    public function handleValidation(FormRequest $formRequest, object $dataHolder)
    {
        if (!$formRequest instanceof LocationRequest) {
            return;
        }

        $dataHolder->attributes = array_merge($dataHolder->attributes, [
            'guest_order' => lang('igniter::admin.locations.label_guest_order'),
        ]);

        $dataHolder->rules = array_merge($dataHolder->rules, [
            'guest_order' => ['integer'],
        ]);
    }
}

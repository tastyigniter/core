<?php

namespace Igniter\System\Traits;

use Exception;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\ComposerException;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Http\RedirectResponse;

trait ManagesUpdates
{
    public function search(): array
    {
        $json = [];

        if (($filter = input('filter')) && is_array($filter)) {
            $itemType = $filter['type'] ?? 'extension';
            $searchQuery = isset($filter['search']) ? strtolower($filter['search']) : '';

            try {
                $json = resolve(UpdateManager::class)->searchItems($itemType, $searchQuery);
            } catch (Exception $ex) {
                $json = $ex->getMessage();
            }
        }

        return $json;
    }

    public function onApplyRecommended(): array
    {
        $itemsCodes = post('install_items') ?? [];
        $items = collect(post('items') ?? [])->whereIn('name', $itemsCodes);
        if ($items->isEmpty()) {
            throw new FlashException(lang('igniter::system.updates.alert_no_items'));
        }

        $this->validateItems();

        $response = resolve(UpdateManager::class)->requestApplyItems($items->all());
        $response = array_get($response, 'data', []);

        return [
            'steps' => $this->buildProcessSteps($response),
        ];
    }

    public function onApplyItems(): array
    {
        $items = post('items') ?? [];
        if (!count($items)) {
            throw new FlashException(lang('igniter::system.updates.alert_no_items'));
        }

        $this->validateItems();

        $response = resolve(UpdateManager::class)->requestApplyItems($items);
        $response = collect(array_get($response, 'data', []))
            ->whereIn('code', collect($items)->pluck('name')->all());

        if ($response->isEmpty()) {
            throw new FlashException(lang('igniter::system.updates.alert_no_items'));
        }

        return [
            'steps' => $this->buildProcessSteps($response->all()),
        ];
    }

    public function onApplyUpdate(): array
    {
        $updates = resolve(UpdateManager::class)->requestUpdateList();
        $itemsToUpdate = array_get($updates, 'items', []);

        if ($itemsToUpdate->isEmpty()) {
            throw new FlashException(lang('igniter::system.updates.alert_item_to_update'));
        }

        return [
            'steps' => $this->buildProcessSteps($itemsToUpdate->all()),
        ];
    }

    public function onCheckUpdates(): RedirectResponse
    {
        $updateManager = resolve(UpdateManager::class);
        $updateManager->requestUpdateList(true);

        return $this->redirect($this->checkUrl);
    }

    public function onIgnoreUpdate(): RedirectResponse
    {
        $itemCode = post('code', '');
        if (!strlen($itemCode)) {
            throw new FlashException(lang('igniter::system.updates.alert_item_to_ignore'));
        }

        $updateManager = resolve(UpdateManager::class);

        $updateManager->markedAsIgnored($itemCode, (bool)post('remove'));

        return $this->redirectBack();
    }

    public function onApplyCarte(): RedirectResponse
    {
        $carteKey = post('carte_key');
        if (!strlen($carteKey)) {
            throw new FlashException(lang('igniter::system.updates.alert_no_carte_key'));
        }

        resolve(UpdateManager::class)->applySiteDetail($carteKey);

        return $this->redirectBack();
    }

    public function onProcessItems(): array
    {
        return $this->processInstallOrUpdate();
    }

    //
    //
    //

    protected function initUpdate(string $itemType)
    {
        $this->prepareAssets();

        $updateManager = resolve(UpdateManager::class);

        $this->vars['itemType'] = $itemType;
        $this->vars['carteInfo'] = $updateManager->getSiteDetail();
        $this->vars['installedItems'] = $updateManager->getInstalledItems();
    }

    protected function prepareAssets()
    {
        $this->addJs('vendor/typeahead.js', 'typeahead-js');
        $this->addJs('updates.js', 'updates-js');
        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
    }

    protected function buildProcessSteps(array $itemsToUpdate): array
    {
        $processSteps = [];
        $itemsToUpdate = collect($itemsToUpdate);
        foreach (['check', 'install', 'complete'] as $step) {
            $itemsToUpdate = $itemsToUpdate->contains('type', 'core')
                ? $itemsToUpdate->where('type', 'core')
                : $itemsToUpdate;

            $feedback = lang('igniter::system.updates.progress_'.$step);

            $processSteps[$step] = [
                'meta' => $itemsToUpdate->all(),
                'process' => $step,
                'progress' => $feedback,
            ];
        }

        return $processSteps;
    }

    protected function processInstallOrUpdate(): array
    {
        $json = [];
        $success = false;

        try {
            $data = $this->validateProcess();

            $meta = array_get($data, 'meta');

            $updateManager = resolve(UpdateManager::class);

            try {
                match (array_get($data, 'process', '')) {
                    'check' => $updateManager->preInstall(),
                    'install' => $updateManager->install($meta),
                    'complete' => $updateManager->completeinstall($meta),
                    default => false,
                };
            } catch (ComposerException $e) {
                $errorMessage = nl2br($e->getMessage());
                $errorMessage .= "\n\n".'<a href="https://tastyigniter.com/support/articles/failed-updates" target="_blank">Troubleshoot</a>'."\n\n";
                $errorMessage .= $e->getMessage();

                throw new ApplicationException($errorMessage);
            }

            $json['message'] = implode(PHP_EOL, $updateManager->getLogs());

            $success = true;
        } catch (\Throwable $e) {
            $json['message'] = $e->getMessage();
        }

        $json['success'] = $success;

        return $json;
    }

    protected function validateItems(): array
    {
        return $this->validate(post(), [
            'items.*.name' => ['required'],
            'items.*.type' => ['required', 'in:core,extension,theme,language'],
            'items.*.ver' => ['sometimes', 'required'],
            'items.*.action' => ['required', 'in:install,update'],
        ], [], [
            'items.*.name' => lang('igniter::system.updates.label_meta_code'),
            'items.*.type' => lang('igniter::system.updates.label_meta_type'),
            'items.*.ver' => lang('igniter::system.updates.label_meta_version'),
            'items.*.action' => lang('igniter::system.updates.label_meta_action'),
        ]);
    }

    protected function validateProcess(): array
    {
        $rules = [
            'process' => ['required', 'in:check,install,complete'],
            'meta' => ['required', 'array'],
            'meta.*.code' => ['required'],
            'meta.*.name' => ['required'],
            'meta.*.package' => ['required'],
            'meta.*.author' => ['required'],
            'meta.*.type' => ['required', 'in:core,extension,theme'],
            'meta.*.version' => ['required'],
            'meta.*.hash' => ['required'],
            'meta.*.description' => ['sometimes'],
        ];

        $attributes = [
            'process' => lang('igniter::system.updates.label_meta_step'),
            'meta.*.code' => lang('igniter::system.updates.label_meta_code'),
            'meta.*.name' => lang('igniter::system.updates.label_meta_name'),
            'meta.*.package' => lang('igniter::system.updates.label_meta_package'),
            'meta.*.type' => lang('igniter::system.updates.label_meta_type'),
            'meta.*.author' => lang('igniter::system.updates.label_meta_author'),
            'meta.*.version' => lang('igniter::system.updates.label_meta_version'),
            'meta.*.hash' => lang('igniter::system.updates.label_meta_hash'),
            'meta.*.description' => lang('igniter::system.updates.label_meta_description'),
        ];

        return $this->validate(post(), $rules, [], $attributes);
    }
}

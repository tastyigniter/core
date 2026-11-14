<?php

declare(strict_types=1);

namespace Igniter\System\Http\Controllers\Concerns;

use Igniter\Admin\Facades\Template;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Widgets\Form;
use Igniter\Admin\Widgets\Toolbar;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\FlashException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Adds the mail builder page to the mail templates, layouts and partials
 * controllers: a full width editor, an "Open in editor" button on the edit
 * page and a reset to the registered content.
 *
 * Controllers define:
 *  - $mailBuilderUrl: the controller URL, e.g. mail_templates
 *  - mailBuilderFields(): the builder form fields
 *  - mailBuilderIsRegistered(): whether the record has registered content to reset to
 *  - mailBuilderResync(): recreates the registered records after a reset
 */
trait HasMailBuilder
{
    public function edit(string $context, mixed $recordId = null): void
    {
        $this->asExtension(FormController::class)->edit($context, $recordId);

        $toolbarWidget = $this->widgets['toolbar'] ?? null;
        if ($toolbarWidget instanceof Toolbar) {
            $toolbarWidget->addButton('builder', [
                'label' => 'lang:igniter::system.mail_builder.button_open_editor',
                'class' => 'btn btn-default pull-right',
                'href' => admin_url($this->mailBuilderUrl.'/builder/'.$recordId),
            ]);
        }
    }

    /**
     * The mail builder page for a record.
     */
    public function builder(string $context, mixed $recordId = null): void
    {
        throw_unless($recordId, new FlashException(lang('igniter::admin.form.missing_id')));

        /** @var Model $model */
        $model = $this->asExtension(FormController::class)->formFindModelObject($recordId);

        // Give the editor the full width, the admin navigation opens from the header
        $this->collapseSidebar = true;

        $title = sprintf(lang('igniter::system.mail_builder.text_title'), $this->mailBuilderRecordLabel($model));
        Template::setTitle($title);
        Template::setHeading($title);

        /** @var Form $formWidget */
        $formWidget = $this->makeWidget(Form::class, [
            'alias' => 'builderForm',
            'arrayName' => class_basename($model),
            'model' => $model,
            'context' => 'builder',
            'fields' => $this->mailBuilderFields(),
        ]);
        $formWidget->bindToController();

        $this->vars['builderModel'] = $model;
        $this->vars['builderUrl'] = $this->mailBuilderUrl;
        $this->vars['builderCanReset'] = $this->mailBuilderIsRegistered($model);
        $this->vars['formWidget'] = $formWidget;
    }

    public function builder_onSave(string $context, mixed $recordId = null): RedirectResponse
    {
        $this->builder($context, $recordId);

        /** @var Form $formWidget */
        $formWidget = $this->vars['formWidget'];
        $model = $formWidget->model;
        $data = $formWidget->getSaveData();

        Validator::make($data, $this->mailBuilderRules(), [], [
            'subject' => lang('igniter::system.mail_templates.label_subject'),
        ])->validate();

        foreach ($data as $attribute => $value) {
            $model->{$attribute} = $value;
        }

        $this->mailBuilderBeforeSave($model);
        $model->save();

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::system.mail_builder.text_saved')));

        return $this->redirect($this->mailBuilderUrl.'/builder/'.$model->getKey());
    }

    /**
     * Deletes the customized record so the registered content is used again.
     */
    public function builder_onReset(string $context, mixed $recordId = null): RedirectResponse
    {
        $this->builder($context, $recordId);

        $model = $this->vars['builderModel'];
        throw_unless($this->vars['builderCanReset'], new FlashException(lang('igniter::system.mail_builder.alert_reset_not_registered')));

        $code = $model->getAttribute('code');
        $model->delete();
        $this->mailBuilderResync();

        flash()->success(lang('igniter::system.mail_builder.alert_reset'));

        $restored = $model::query()->where('code', $code)->first();

        return $this->redirect($restored
            ? $this->mailBuilderUrl.'/builder/'.$restored->getKey()
            : $this->mailBuilderUrl);
    }

    abstract protected function mailBuilderFields(): array;

    abstract protected function mailBuilderIsRegistered(Model $model): bool;

    abstract protected function mailBuilderResync(): void;

    protected function mailBuilderRules(): array
    {
        return [];
    }

    /**
     * Marks the record as customized before it is saved.
     */
    abstract protected function mailBuilderBeforeSave(Model $model): void;

    protected function mailBuilderRecordLabel(Model $model): string
    {
        return (string)($model->title ?? $model->name ?? $model->code);
    }
}

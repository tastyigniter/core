<?php

namespace Igniter\System\Mail;

use Igniter\Flame\Mail\Mailable;
use Igniter\System\Classes\MailManager;
use Igniter\System\Helpers\ViewHelper;
use Igniter\System\Models\MailTemplate;
use ReflectionClass;
use ReflectionProperty;

class TemplateMailable extends Mailable
{
    protected string $templateCode;

    protected ?MailTemplate $mailTemplate = null;

    public static function getVariables(): array
    {
        return static::getPublicProperties();
    }

    public function getTemplateCode(): string
    {
        return $this->templateCode;
    }

    public function getMailTemplate(): MailTemplate
    {
        return $this->mailTemplate ?? $this->resolveTemplateModel();
    }

    protected function resolveTemplateModel(): MailTemplate
    {
        return $this->mailTemplate = MailTemplate::findOrMakeTemplate($this->getTemplateCode());
    }

    protected function buildView()
    {
        $template = $this->getMailTemplate();

        $manager = $this->getMailManager();

        $viewData = $this->buildViewData();

        $this->html = $manager->renderTemplate($template, $viewData);
        $this->textView = $manager->renderTextTemplate($template, $viewData);

        return parent::buildView();
    }

    protected function buildSubject($message): self
    {
        if ($subject = $this->getMailTemplate()->subject) {
            $subject = $this->getMailManager()->renderView($subject, $this->buildViewData());

            $message->subject($subject);

            return $this;
        }

        return parent::buildSubject($message);
    }

    public function buildViewData(): array
    {
        $data = parent::buildViewData();

        $globalVars = ViewHelper::getGlobalVars();
        if (!empty($globalVars)) {
            $data += $globalVars;
        }

        return $data;
    }

    protected static function getPublicProperties(): array
    {
        $class = new ReflectionClass(static::class);

        return collect($class->getProperties(ReflectionProperty::IS_PUBLIC))
            ->filter(fn($property) => $property->getDeclaringClass()->getName() !== self::class)
            ->map->getName()
            ->values()
            ->all();
    }

    protected function getMailManager(): MailManager
    {
        return resolve(MailManager::class);
    }
}

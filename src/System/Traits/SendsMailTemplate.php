<?php

namespace Igniter\System\Traits;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;

trait SendsMailTemplate
{
    public function mailGetReplyTo(): array
    {
        return [];
    }

    public function mailGetRecipients(string $type): array
    {
        return [];
    }

    public function mailGetData(): array
    {
        return [];
    }

    public function mailSend(string $view, ?string $recipientType = null)
    {
        $vars = $this->mailGetData();

        $result = $this->fireEvent('model.mailGetData', [$view, $recipientType]);
        if ($result && is_array($result)) {
            $vars = array_merge(...$result) + $vars;
        }

        if ($recipients = $this->mailBuildMessageTo($recipientType)) {
            Mail::queueTemplate($view, $vars, $recipients);
        }
    }

    protected function mailBuildMessageTo(?string $recipientType = null): array
    {
        $recipients = [];
        foreach ($this->mailGetRecipients($recipientType) as $recipient) {
            $recipients[] = new Address(...$recipient);
        }

        return $recipients;
    }
}

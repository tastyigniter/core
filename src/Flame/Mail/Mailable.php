<?php

namespace Igniter\Flame\Mail;

use Illuminate\Mail\Mailable as MailableBase;
use Illuminate\Support\Facades\App;

/**
 * Generic mailable class.
 *
 * Adapted from october\rain\mail\Mailable
 */
class Mailable extends MailableBase
{
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Queue\SerializesModels;

    public function build(): self
    {
        return $this;
    }

    public function buildViewData(): array
    {
        $data = parent::buildViewData();

        foreach ($data as $param => $value) {
            $data[$param] = $this->getRestoredPropertyValue($value);
        }

        return $data;
    }

    /**
     * Set serialized view data for the message.
     */
    public function withSerializedData(array $data): self
    {
        // Ensure that the current locale is stored with the rest of the data for proper translation of queued messages
        $defaultData = [
            '_current_locale' => App::getLocale(),
        ];

        $data = array_merge($defaultData, $data);

        foreach ($data as $param => $value) {
            $this->viewData[$param] = $this->getSerializedPropertyValue($value);
        }

        return $this;
    }

    /**
     * Set the subject for the message.
     *
     * @param \Illuminate\Mail\Message $message
     * @return $this
     */
    protected function buildSubject($message): self
    {
        // If a custom subject was set, then set it as the message subject
        // Otherwise attempt to set the subject if the message doesn't already have one set
        if ($this->subject) {
            $message->subject($this->subject);
        }

        return $this;
    }
}

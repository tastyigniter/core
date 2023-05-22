<?php

namespace Igniter\Admin\Models\Concerns;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\SystemException;

trait SendsInvite
{
    public static function bootSendsInvite()
    {
        static::extend(function (Model $model) {
            $model->addPurgeable(['send_invite']);
        });

        static::saved(function (Model $model) {
            $model->restorePurgedValues();
            if ($model->send_invite && $templateCode = $model->sendInviteGetTemplateCode()) {
                $model->sendInvite($templateCode);
            }
        });
    }

    protected function sendInviteGetTemplateCode(): string
    {
        throw new SystemException(sprintf(
            'The model [%s] must implement a sendInviteGetTemplateCode() method.',
            get_class($this)
        ));
    }

    public function sendInvite(string $templateCode)
    {
        $this->bindEventOnce('model.mailGetData', function ($view, $recipientType) use ($templateCode) {
            if ($view === $templateCode) {
                $this->newQuery()->update([
                    'reset_code' => $inviteCode = $this->generateResetCode(),
                    'invited_at' => now(),
                ]);

                return ['invite_code' => $inviteCode];
            }
        });

        $this->mailSend($templateCode);
    }
}
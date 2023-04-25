<?php

namespace Igniter\Flame\Mixins;

use Igniter\System\Mail\AnonymousTemplateMailable;

class MailMixin
{
    public function sendTemplate()
    {
        return function ($view, $vars, $callback = null) {
            /** @var \Illuminate\Mail\Mailer $this */
            return $this->send(AnonymousTemplateMailable::create($view)->applyCallback($callback)->with($vars));
        };
    }

    public function queueTemplate()
    {
        return function ($view, $vars, $callback = null) {
            /** @var \Illuminate\Mail\Mailer $this */
            return $this->queue(AnonymousTemplateMailable::create($view)->applyCallback($callback)->with($vars));
        };
    }
}

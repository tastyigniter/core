<?php

namespace Igniter\Flame\Mixins;

use Igniter\System\Mail\AnonymousTemplateMailable;

/** @mixin \Illuminate\Mail\Mailer */
class MailMixin
{
    public function sendTemplate()
    {
        return function($view, $vars, $callback = null) {
            return $this->send(AnonymousTemplateMailable::create($view)->applyCallback($callback)->with($vars));
        };
    }

    public function queueTemplate()
    {
        return function($view, $vars, $callback = null) {
            return $this->queue(AnonymousTemplateMailable::create($view)->applyCallback($callback)->with($vars));
        };
    }
}

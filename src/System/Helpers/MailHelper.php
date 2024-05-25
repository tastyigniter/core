<?php

namespace Igniter\System\Helpers;

use Igniter\System\Mail\AnonymousTemplateMailable;
use Illuminate\Support\Facades\Mail;

class MailHelper
{
    public static function sendTemplate($view, $vars, $callback = null)
    {
        return Mail::send(AnonymousTemplateMailable::create($view)->applyCallback($callback)->with($vars));
    }

    public static function queueTemplate($view, $vars, $callback = null)
    {
        return Mail::queue(AnonymousTemplateMailable::create($view)->applyCallback($callback)->with($vars));
    }
}

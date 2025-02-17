<?php

declare(strict_types=1);

namespace Igniter\System\Helpers;

use Igniter\System\Mail\AnonymousTemplateMailable;
use Illuminate\Support\Facades\Mail;

class MailHelper
{
    public function sendTemplate(string $view, $vars, $callback = null)
    {
        return Mail::send(AnonymousTemplateMailable::create($view)->applyCallback($callback)->with($vars));
    }

    public function queueTemplate(string $view, $vars, $callback = null)
    {
        return Mail::queue(AnonymousTemplateMailable::create($view)->applyCallback($callback)->with($vars));
    }
}

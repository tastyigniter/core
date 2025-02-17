<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Mail;

use Igniter\System\Classes\MailManager;
use Igniter\System\Mail\TemplateMailable;
use Igniter\System\Models\MailTemplate;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\HtmlString;

it('retrieves template code successfully', function() {
    $mailable = new class extends TemplateMailable
    {
        protected string $templateCode = 'test_template';
    };

    expect($mailable->getTemplateCode())->toBe('test_template');
});

it('builds subject from mail template', function() {
    MailTemplate::create([
        'code' => '_mail.test_template',
        'subject' => 'Test Subject',
    ]);
    $mailable = new class extends TemplateMailable
    {
        protected string $templateCode = '_mail.test_template';
    };

    $mailer = mock(Mailer::class);
    $mailer->shouldReceive('send')->withArgs(function($view, $data, $messageCallback): true {
        $message = mock(Message::class);
        $message->shouldReceive('subject')->once();
        $messageCallback($message);

        return true;
    })->once();
    $mailable->send($mailer);

    $mailable->hasSubject('Test Subject');
});

it('builds view with rendered templates', function() {
    MailTemplate::create([
        'code' => '_mail.test_template',
        'subject' => 'Test Subject',
    ]);
    $mailable = new class extends TemplateMailable
    {
        protected string $templateCode = '_mail.test_template';
    };
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('renderTemplate')->andReturn(new HtmlString('Rendered HTML'));
    $mailManager->shouldReceive('renderTextTemplate')->andReturn(new HtmlString('Rendered Text'));

    $mailer = mock(Mailer::class);
    $mailer->shouldReceive('send')->withArgs(function(array $view, $data, $messageCallback): true {
        expect($view['html']->toHtml())->toBe('Rendered HTML')
            ->and($view['text']->toHtml())->toBe('Rendered Text');

        return true;
    });

    $mailable->send($mailer);
});

it('returns variables correctly', function() {
    $mailable = new class extends TemplateMailable
    {
        protected string $templateCode = 'test_template';

        public $var1 = 'value1';

        public $var2 = 'value2';
    };

    expect($mailable->getVariables())->toContain('var1', 'var2');
});

<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models\Concerns;

use Facades\Igniter\System\Helpers\MailHelper;
use Igniter\System\Models\Concerns\SendsMailTemplate;
use Symfony\Component\Mime\Address;

it('returns reply to correctly', function() {
    $model = new class
    {
        use SendsMailTemplate;
    };

    expect($model->mailGetReplyTo())->toBeArray()->toBeEmpty();

    $model = new class
    {
        use SendsMailTemplate;

        public function mailGetReplyTo(?string $type = null): array
        {
            return ['test@example.com', 'Test User'];
        }
    };

    expect($model->mailGetReplyTo())->toBeArray()->toContain('test@example.com', 'Test User');
});

it('returns recipients correctly', function() {
    $model = new class
    {
        use SendsMailTemplate;
    };

    expect($model->mailGetRecipients('admin'))->toBeArray()->toBeEmpty();

    $model = new class
    {
        use SendsMailTemplate;

        public function mailGetRecipients(string $type): array
        {
            return [['test@example.com', 'Test User']];
        }
    };

    expect($model->mailGetRecipients('admin'))->toEqual([['test@example.com', 'Test User']]);
});

it('sends mail with additional variables', function() {
    $model = new class
    {
        use SendsMailTemplate;
    };

    expect($model->mailGetData())->toBeArray()->toBeEmpty();

    $model = new class
    {
        use SendsMailTemplate;

        public function mailGetData(): array
        {
            return ['key' => 'value'];
        }
    };

    expect($model->mailGetData())->toEqual(['key' => 'value']);
});

it('sends mail to valid recipients', function() {
    MailHelper::shouldReceive('queueTemplate')->once()->with('view', [], [
        new Address('test@example.com', 'Test User'),
    ]);
    $model = new class
    {
        use SendsMailTemplate;

        public function mailGetRecipients(string $type): array
        {
            return [['test@example.com', 'Test User']];
        }
    };

    $model->mailSend('view', 'admin');
});

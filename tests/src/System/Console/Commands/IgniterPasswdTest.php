<?php

namespace Igniter\Tests\System\Console\Commands;

use Igniter\Flame\Exception\FlashException;
use Igniter\User\Models\User;

it('changes password successfully with provided email and password', function() {
    $user = User::factory()->create();

    $this->artisan('igniter:passwd', ['email' => $user->email, 'password' => 'newpassword'])
        ->expectsOutput('Password successfully changed.')
        ->assertExitCode(0);

    expect(User::find($user->getKey())->password)->not->toBe($user->password);
});

it('throws exception if user does not exist', function() {
    $this->expectException(FlashException::class);
    $this->expectExceptionMessage('The specified user does not exist.');

    $this->artisan('igniter:passwd', ['email' => 'user@example.com', 'password' => 'newpassword'])
        ->assertExitCode(1);
});

it('prompts for email if not provided', function() {
    $user = User::factory()->create();

    $this->artisan('igniter:passwd', ['password' => 'newpassword'])
        ->expectsQuestion('Admin email to reset', $user->email)
        ->expectsOutput('Password successfully changed.')
        ->assertExitCode(0);

    expect(User::find($user->getKey())->password)->not->toBe($user->password);
});

it('generates password if not provided', function() {
    $user = User::factory()->create();

    $this->artisan('igniter:passwd', ['email' => $user->email])
        ->expectsQuestion('Enter new password (leave blank for generated password)', null)
        ->expectsOutput('Password successfully changed.')
        ->assertExitCode(0);

    expect(User::find($user->getKey())->password)->not->toBe($user->password);
});

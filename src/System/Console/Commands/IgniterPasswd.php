<?php

namespace Igniter\System\Console\Commands;

use Igniter\Admin\Models\User;
use Igniter\Flame\Exception\ApplicationException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;

/**
 * Console command to change the password of an Admin user via CLI.
 *
 * Adapted from october\system\console\OctoberPasswd
 */
class IgniterPasswd extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'igniter:passwd';

    /**
     * @var string The console command description.
     */
    protected $description = 'Change the password of an Admin user.';

    /**
     * @var bool Was the password automatically generated?
     */
    protected $generatedPassword = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email')
            ?? $this->ask('Admin email to reset');

        if (!$user = User::whereEmail($email)->first())
            throw new ApplicationException('The specified user does not exist.');

        if (is_null($password = $this->argument('password'))) {
            $password = $this->optionalSecret('Enter new password (leave blank for generated password)')
                ?: $this->generatePassword();
        }

        $user->password = $password;
        $user->save();

        $this->info('Password successfully changed.');
        if ($this->generatedPassword) {
            $this->output->writeLn('Password set to <info>'.$password.'</info>.');
        }
    }

    /**
     * Get the console command options.
     */
    protected function getArguments()
    {
        return [
            ['username', InputArgument::OPTIONAL, 'The email of the Admin user o reset'],
            ['password', InputArgument::OPTIONAL, 'The new password'],
        ];
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * Also allows for a default to be specified.
     *
     * @param string $question
     * @param bool $fallback
     * @return string
     */
    protected function optionalSecret($question)
    {
        $question = new Question($question, false);

        $question->setHidden(true)->setHiddenFallback(false);

        return $this->output->askQuestion($question);
    }

    /**
     * Generate a password and flag it as an automatically-generated password.
     *
     * @return string
     */
    protected function generatePassword()
    {
        $this->generatedPassword = true;

        return str_random(22);
    }
}

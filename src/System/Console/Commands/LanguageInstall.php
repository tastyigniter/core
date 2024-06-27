<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Language;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LanguageInstall extends Command
{
    protected $name = 'igniter:language-install';

    protected $description = 'Pull translated strings from the TastyIgniter marketplace.';

    public function handle()
    {
        $locale = $this->argument('locale');

        throw_unless($language = Language::findByCode($locale), new ApplicationException('Language not found'));

        $languageManager = resolve(LanguageManager::class);
        if (!$response = $languageManager->applyLanguagePack($language->code, (array)$language->version)) {
            $this->output->writeln('<info>No new translated strings found</info>');

            return;
        }

        if ($this->option('check')) {
            return;
        }

        $this->output->writeln(sprintf('<info>%s translated strings found</info>', count($response)));

        foreach ($response as $languageBuild) {
            $this->output->writeln(sprintf('<info>Installing %s translated strings for %s</info>', $language->code, $languageBuild['name']));
            $languageManager->installLanguagePack($language->code, [
                'name' => $languageBuild['code'],
                'type' => $languageBuild['type'],
                'ver' => str_before($languageBuild['version'], '+'),
                'build' => str_after($languageBuild['version'], '+'),
                'hash' => $languageBuild['hash'],
            ]);
        }
    }

    protected function getArguments()
    {
        return [
            ['locale', InputArgument::REQUIRED, 'The name of the language. Eg: fr_FR'],
        ];
    }

    protected function getOptions()
    {
        return [
            ['check', null, InputOption::VALUE_NONE, 'Run update checks only.'],
        ];
    }
}

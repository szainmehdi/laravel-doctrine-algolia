<?php

namespace Zain\LaravelDoctrine\Algolia\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Zain\LaravelDoctrine\Algolia\Settings\SettingsManager;

/**
 * @internal
 */
final class SearchSettingsPushCommand extends SearchSettingsCommand
{
    protected $name = 'search:settings:push';
    protected $description = 'Push settings from your project to the search engine';

    public function handle(SettingsManager $manager): int
    {
        $message = $manager->push($this->getParams());

        $this->output->writeln($message);

        return 0;
    }
}

<?php

namespace Zain\LaravelDoctrine\Algolia\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Zain\LaravelDoctrine\Algolia\Settings\SettingsManager;

/**
 * @internal
 */
final class SearchSettingsBackupCommand extends SearchSettingsCommand
{
    protected $name = 'search:settings:backup';
    protected $description = 'Backup search engine settings into your project';

    public function handle(SettingsManager $manager): int
    {
        $message = $manager->backup($this->getParams());

        $this->output->writeln($message);

        return 0;
    }
}

<?php

namespace Zain\LaravelDoctrine\Algolia\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
abstract class SearchSettingsCommand extends Command
{
    protected function getOptions()
    {
        return [
            new InputOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
        ];
    }

    protected function getArguments()
    {
        return [
            new InputArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            )
        ];
    }

    public function getParams(): array
    {
        if ($indexList = $this->option('indices')) {
            $indexList = explode(',', $indexList);
        }

        return [
            'indices' => (array) $indexList,
            'extra'   => $this->argument('extra'),
        ];
    }
}

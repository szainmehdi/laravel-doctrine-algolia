<?php

namespace Zain\LaravelDoctrine\Algolia\Console;

use Algolia\AlgoliaSearch\Response\IndexingResponse;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Zain\LaravelDoctrine\Algolia\SearchService;

/**
 * @internal
 */
final class SearchClearCommand extends IndexCommand
{
    protected $name = 'search:clear';
    protected $description = 'Clear index (remove all data but keep index and settings)';

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

    public function handle(SearchService $service): int
    {
        $this->searchService = $service;
        $indexToClear = $this->getEntities();

        foreach ($indexToClear as $indexName => $className) {
            $success = $this->searchService->clear($className);

            if ($success instanceof IndexingResponse) {
                $this->output->writeln('Cleared <info>' . $indexName . '</info> index of <comment>' . $className . '</comment> ');
            } else {
                $this->output->writeln('<error>Index <info>' . $indexName . '</info>  couldn\'t be cleared</error>');
            }
        }

        $this->output->writeln('<info>Done!</info>');

        return 0;
    }
}

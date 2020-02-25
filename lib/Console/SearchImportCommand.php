<?php

namespace Zain\LaravelDoctrine\Algolia\Console;

use Algolia\AlgoliaSearch\SearchClient;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Zain\LaravelDoctrine\Algolia\AtomicSearchService;
use Zain\LaravelDoctrine\Algolia\Entity\Aggregator;
use Zain\LaravelDoctrine\Algolia\SearchService;

/**
 * @internal
 */
final class SearchImportCommand extends IndexCommand
{
    protected $name = 'search:import';
    protected $description = 'Import given entity into search engine';

    private AtomicSearchService $atomicSearchService;
    private ManagerRegistry $managerRegistry;
    private SearchClient $searchClient;

    public function handle(
        SearchService $searchService,
        AtomicSearchService $atomicSearchService,
        ManagerRegistry $managerRegistry,
        SearchClient $searchClient
    ) {
        $this->searchService = $searchService;
        $this->atomicSearchService = $atomicSearchService;
        $this->managerRegistry = $managerRegistry;
        $this->searchClient = $searchClient;

        $shouldDoAtomicReindex = $this->option('atomic');
        $entitiesToIndex = $this->getEntities();
        $config = $this->searchService->getConfiguration();
        $indexingService = ($shouldDoAtomicReindex ? $this->atomicSearchService : $this->searchService);

        foreach ($entitiesToIndex as $key => $entityClassName) {
            if (is_subclass_of($entityClassName, Aggregator::class)) {
                unset($entitiesToIndex[$key]);
                $entitiesToIndex = array_merge($entitiesToIndex, $entityClassName::getEntities());
            }
        }

        $entitiesToIndex = array_unique($entitiesToIndex);

        foreach ($entitiesToIndex as $entityClassName) {
            if (!$this->searchService->isSearchable($entityClassName)) {
                continue;
            }

            $manager = $this->managerRegistry->getManagerForClass($entityClassName);
            $repository = $manager->getRepository($entityClassName);
            $sourceIndexName = $this->searchService->searchableAs($entityClassName);

            if ($shouldDoAtomicReindex) {
                $temporaryIndexName = $this->atomicSearchService->searchableAs($entityClassName);
                $this->output->writeln("Creating temporary index <info>$temporaryIndexName</info>");
                $this->searchClient->copyIndex(
                    $sourceIndexName,
                    $temporaryIndexName,
                    ['scope' => ['settings', 'synonyms', 'rules']]
                );
            }

            $page = 0;
            do {
                $entities = $repository->findBy(
                    [],
                    null,
                    $config['batchSize'],
                    $config['batchSize'] * $page
                );
                $responses = $this->formatIndexingResponse(
                    $indexingService->index($manager, $entities)
                );
                foreach ($responses as $indexName => $numberOfRecords) {
                    $this->output->writeln(
                        sprintf(
                            'Indexed <comment>%s / %s</comment> %s entities into %s index',
                            $numberOfRecords,
                            count($entities),
                            $entityClassName,
                            '<info>' . $indexName . '</info>'
                        )
                    );
                }

                $page++;
                $repository->clear();
            } while (count($entities) >= $config['batchSize']);

            if ($shouldDoAtomicReindex && isset($indexName)) {
                $this->output->writeln("Moving <info>$indexName</info> -> <comment>$sourceIndexName</comment>\n");
                $this->searchClient->moveIndex($indexName, $sourceIndexName);
            }

            $repository->clear();
        }

        $this->output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * @param array<int, array> $batch
     *
     * @return array<string, int>
     */
    private function formatIndexingResponse($batch)
    {
        $formattedResponse = [];

        foreach ($batch as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                if (!array_key_exists($indexName, $formattedResponse)) {
                    $formattedResponse[$indexName] = 0;
                }

                $formattedResponse[$indexName] += count($apiResponse->current()['objectIDs']);
            }
        }

        return $formattedResponse;
    }

    protected function getOptions()
    {
        return [
            new InputOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names'),
            new InputOption(
                'atomic',
                null,
                InputOption::VALUE_NONE,
                <<<EOT
If set, command replaces all records in an index without any downtime. It pushes a new set of objects and removes all previous ones.

Internally, this option causes command to copy existing index settings, synonyms and query rules and indexes all objects. Finally, the existing index is replaced by the temporary one.
EOT
            ),
        ];
    }

    protected function getArguments()
    {
        return [
            new InputArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            ),
        ];
    }
}

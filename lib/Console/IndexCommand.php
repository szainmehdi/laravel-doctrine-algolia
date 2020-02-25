<?php

namespace Zain\LaravelDoctrine\Algolia\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zain\LaravelDoctrine\Algolia\SearchService;

/**
 * @internal
 */
abstract class IndexCommand extends Command
{
    protected SearchService $searchService;

    /**
     * @return array<string, string>
     */
    protected function getEntities()
    {
        $entities = [];
        $indexNames = [];

        if ($indexList = $this->option('indices')) {
            $indexNames = explode(',', $indexList);
        }

        $config = $this->searchService->getConfiguration();

        if (count($indexNames) === 0) {
            $indexNames = array_keys($config['indices']);
        }

        foreach ($indexNames as $name) {
            if (isset($config['indices'][$name])) {
                $entities[$name] = $config['indices'][$name]['class'];
            } else {
                $this->output->writeln(
                    '<comment>No index named <info>' . $name . '</info> was found. Check you configuration.</comment>'
                );
            }
        }

        return $entities;
    }
}

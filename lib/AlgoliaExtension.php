<?php

declare(strict_types=1);

namespace Zain\LaravelDoctrine\Algolia;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use LaravelDoctrine\ORM\Extensions\Extension;
use Zain\LaravelDoctrine\Algolia\EventListener\SearchIndexerSubscriber;

class AlgoliaExtension implements Extension
{
    private SearchIndexerSubscriber $subscriber;

    public function __construct(SearchIndexerSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function addSubscribers(EventManager $manager, EntityManagerInterface $em, Reader $reader = null)
    {
        $manager->addEventSubscriber($this->subscriber);
    }

    public function getFilters()
    {
        return [];
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Shergold\IndexerGraphQl\Model\Resolver;

use Shergold\IndexerGraphQl\Model\Authorise;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Indexer\StateInterface;
use Magento\Framework\Mview\ViewInterface;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Indexer\Model\Indexer\DependencyDecorator;
use Magento\Indexer\Model\Indexer\CollectionFactory;

/**
 * Company profile data resolver, used for GraphQL request processing.
 */
class IndexerState implements ResolverInterface
{
    public const INDEXER_ARGUMENTS = 'indexer';

    /**
     * @param Authorise $authorise
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private Authorise $authorise,
        private CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {

        if (!$this->authorise->isCallAuthorised()) {
            throw new GraphQlAuthorizationException(
                __('You are not authorized to access this feature.')
            );
        }

        $rows = [];
        $input = $args[self::INDEXER_ARGUMENTS];

        $indexers = $this->getIndexers($input);

        foreach ($indexers as $indexer) {
            $view = $indexer->getView();

            $rowData = [
                'id'                => $indexer->getId(),
                'title'             => $indexer->getTitle(),
                'status'            => $this->getStatus($indexer),
                'update_on'         => $indexer->isScheduled() ? 'Schedule' : 'Save',
                'schedule_status'   => '',
                'updated'           => '',
            ];

            if ($indexer->isScheduled()) {
                $state = $view->getState();
                $rowData['schedule_status'] = "{$state->getStatus()} ({$this->getPendingCount($view)} in backlog)";
                $rowData['updated'] = $state->getUpdated();
            }

            $rows[] = $rowData;
        }

        return [
            'total_count' => count($indexers),
            'items' => $rows,
        ];
    }

    /**
     * Return the array of requested indexes.
     *
     * @param array $requestedTypes
     * @return array
     */
    private function getIndexers(array $requestedTypes): array
    {
        if (empty($requestedTypes)) {
            $indexers = $this->getAllIndexers();
        } else {
            $availableIndexers = $this->getAllIndexers();
            $unsupportedTypes = array_diff($requestedTypes, array_keys($availableIndexers));
            if ($unsupportedTypes) {
                throw new \InvalidArgumentException(
                    "The following requested index types are not supported: '" . join("', '", $unsupportedTypes)
                    . "'." . PHP_EOL . 'Supported types: ' . join(", ", array_keys($availableIndexers))
                );
            }
            $indexers = array_intersect_key($availableIndexers, array_flip($requestedTypes));
        }

        return $indexers;
    }

    /**
     * Return the array of all indexers with keys as indexer ids.
     *
     * @return DependencyDecorator[]
     */
    private function getAllIndexers(): array
    {
        $indexers = $this->collectionFactory->create()->getItems();

        return array_combine(
            array_map(
                function ($item) {
                    return $item->getId();
                },
                $indexers
            ),
            $indexers
        );
    }

    /**
     * Returns the current status of the indexer
     *
     * @param DependencyDecorator $indexer
     * @return string
     */
    private function getStatus(DependencyDecorator $indexer): string
    {
        return match ($indexer->getStatus()) {
            StateInterface::STATUS_VALID => 'Ready',
            StateInterface::STATUS_INVALID => 'Reindex required',
            StateInterface::STATUS_WORKING => 'Processing',
            default => 'unknown',
        };
    }

    /**
     * Returns the pending count of the view
     *
     * @param ViewInterface $view
     * @return int
     */
    private function getPendingCount(ViewInterface $view): int
    {
        $changelog = $view->getChangelog();

        try {
            $currentVersionId = $changelog->getVersion();
        } catch (ChangelogTableNotExistsException $e) {
            return 0;
        }

        $state = $view->getState();

        return count($changelog->getList($state->getVersionId(), $currentVersionId));
    }
}

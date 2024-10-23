<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Resource;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SvgFileRepository.
 *
 * @author Marcus Förster ; https://github.com/xerc
 */
class SvgFileRepository
{
    /**
     * Retrieves all used SVGs within given storage-array.
     */
    public function findAllByStorageUids(array $storageUids): \Traversable
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');

        return $queryBuilder
            ->select('sys_file.storage', 'sys_file.identifier', 'sys_file.sha1')
            ->from('sys_file')
            ->innerJoin(
                'sys_file',
                'sys_file_reference',
                'sys_file_reference',
                $queryBuilder->expr()->eq(
                    'sys_file_reference.uid_local',
                    $queryBuilder->quoteIdentifier('sys_file.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->in(
                    'sys_file.storage',
                    $queryBuilder->createNamedParameter($storageUids, \TYPO3\CMS\Core\Database\Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->lt(
                    'sys_file.size',
                    $queryBuilder->createNamedParameter((int) $GLOBALS['TSFE']->config['config']['svgstore.']['fileSize'] ?? null, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_file.mime_type',
                    $queryBuilder->createNamedParameter('image/svg+xml', \TYPO3\CMS\Core\Database\Connection::PARAM_STR)
                )
            )
            ->groupBy('sys_file.uid', 'sys_file.storage', 'sys_file.identifier', 'sys_file.sha1')
            ->orderBy('sys_file.storage')
            ->addOrderBy('sys_file.identifier')
            ->execute()
            ->iterateAssociative()
        ;
    }
}

<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Resource;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SvgFileRepository extends FileRepository.
 *
 * @author Marcus Förster ; https://github.com/xerc
 */
class SvgFileRepository extends \TYPO3\CMS\Core\Resource\FileRepository
{
    /**
     * Retrieves all used SVGs within given storage-array.
     */
    public function findAllByStorageUids(array $storageUids): array
    {
        return
            ($queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable($this->table))
                ->select('sys_file.storage', 'sys_file.identifier', 'sys_file.sha1')
                ->from($this->table)
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
                ->fetchAll() // TODO; use stdClass
        ;
    }
}

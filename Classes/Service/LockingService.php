<?php

namespace Itx\Importer\Service;

use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LockingService
{
    protected LockFactory $lockFactory;

    public function __construct()
    {
        $this->lockFactory = GeneralUtility::makeInstance(LockFactory::class);
    }

    /**
     * @param string $key The key to lock
     *
     * @return LockingStrategyInterface
     * @throws LockCreateException
     */
    public function createLock(string $key): LockingStrategyInterface
    {
        return $this->lockFactory->createLocker('importer-'.$key, LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE);
    }
}

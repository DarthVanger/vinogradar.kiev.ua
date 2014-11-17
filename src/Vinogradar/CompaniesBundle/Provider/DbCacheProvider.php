<?php

namespace Vinogradar\CompaniesBundle\Provider;

use Doctrine\Common\Cache\XcacheCache;

class DbCacheProvider
{
    private $cacheDriver;

    private $hashes;

    public function __construct(XcacheCache $cacheDriver) {
        $this->cacheDriver = $cacheDriver;
    }

    public function save($key, $value) {
        return $this->cacheDriver->save($key, $value);
    }

    public function fetch($key) {
        return $this->cacheDriver->fetch($key);
    }

    public function cacheIsFresh($key) {
        $dbHash = $this->getDbHash($key);
        $cacheHash  = $this->getCacheHash($key);
        if (empty($dbHash) || empty($cacheHash)) {
            // hashes may be empty for example if server restarted and thus cache is empty
            return false;
        }
        return $dbHash === $cacheHash;
    }


    public function updateDbHash($key) {
        $dateTime = new \DateTime();
        $this->cacheDriver->save('db_hash_'.$key, sha1($dateTime->getTimestamp()));
    }

    public function getDbHash($key) {
        return $this->cacheDriver->fetch('db_hash_'.$key);
    }

    public function updateCacheHash($key) {
        $dbHash = $this->getDbHash($key);
        $this->cacheDriver->save('cash_hash_'.$key, $dbHash);
    }

    public function getCacheHash($key) {
        return $this->cacheDriver->fetch('cash_hash_'.$key);
    }
}

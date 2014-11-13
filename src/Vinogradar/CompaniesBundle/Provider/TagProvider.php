<?php

namespace Vinogradar\CompaniesBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Cache\XcacheCache;
use Vinogradar\UtilsBundle\Transliterator;
use Vinogradar\UtilsBundle\NameForUrlPreparator;

class TagProvider
{

    protected $entityManager;
    protected $transliterator;
    protected $cacheDriver;
    protected $nameForUrlPreparator;


    public function __construct(
        EntityManager $entityManager,
        XcacheCache $cacheDriver,
        Transliterator $transliterator,
        NameForUrlPreparator $nameForUrlPreparator
    ) {
        $this->entityManager = $entityManager;
        $this->cacheDriver = $cacheDriver;
        $this->transliterator = $transliterator;
        $this->nameForUrlPreparator = $nameForUrlPreparator;
    }

    public function replaceDuplicatesWithExistingTags(&$tags) {
        $em = $this->entityManager;
        $tagRepository = $em->getRepository('VinogradarCompaniesBundle:Tag');
        foreach ($tags as $key => $tag) {
            $alreadyExistingTag  = $tagRepository->findOneBy(array('name' => $tag->getName()));
            if ($alreadyExistingTag) {
                $tags[$key] = $alreadyExistingTag;
            }
        }
        return $tags;
    }

    public function generateNamesForUrl(&$tags) {
        foreach ($tags as $tag) {
            $nameForUrl = $this->transliterator->translitRussian($tag->getName());
            $nameForUrl = $this->nameForUrlPreparator->cleanUp($nameForUrl);
            $tag->setNameForUrl($nameForUrl);
        }
    }

    public function fetchTagsFromDb() {

        $tagsManager = $this->entityManager;

        // select tags with companies count
        $query = $tagsManager->createQuery(
            'SELECT tag, COUNT(companies.id) as companies_count
            FROM VinogradarCompaniesBundle:Tag tag
            JOIN tag.companies companies
            GROUP BY
                tag.id
            ORDER BY
                companies_count DESC'
        );

        $queryResult = $query->getResult();

        // fetch the query
        $tags = array();
        foreach ($queryResult as $row) {
            $tag = $row[0];
            $tag->setCompaniesCount($row['companies_count']);
            $tags[] = $tag;
        }

        $this->cacheDriver->save('tags', $tags);
        $dateTime = new \DateTime();
        $this->cacheDriver->save('cachedCompaniesDbHash', $cacheDriver->fetch('companiesDbHash'));

        return $tags;
    }

    public function getAllTags() {
        if ($this->cacheDriver->fetch('cachedCompaniesDbHash') !==  $this->cacheDriver->fetch('companiesDbHash')) {
            // db has changed since last caching
            $tags = $this->fetchTagsFromDb();
        } else {
            // cached tags are up to date
            $tags = $this->cacheDriver->fetch('tags');
            if (!$tags) {
                // cache may be empty, for example if server restarted
                $tags = $this->fetchTagsFromDb();
            }
        }

        return $tags;
    }
}

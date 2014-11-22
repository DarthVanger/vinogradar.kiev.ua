<?php

namespace Vinogradar\CompaniesBundle\Provider;

use Doctrine\ORM\EntityManager;
use Vinogradar\CompaniesBundle\Provider\DbCacheProvider;
use Vinogradar\UtilsBundle\Transliterator;
use Vinogradar\UtilsBundle\NameForUrlPreparator;

class TagProvider
{
    protected $entityManager;
    protected $transliterator;
    protected $dbCacheProvider;
    protected $nameForUrlPreparator;

    public function __construct(
        EntityManager $entityManager,
        DbCacheProvider $dbCacheProvider,
        Transliterator $transliterator,
        NameForUrlPreparator $nameForUrlPreparator
    ) {
        $this->entityManager = $entityManager;
        $this->dbCacheProvider = $dbCacheProvider;
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

    // TODO: refactor this method to TagRepository
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

        $this->dbCacheProvider->save('tags', $tags);
        $this->dbCacheProvider->updateCacheHash('tags');

        return $tags;
    }

    public function getAllTagNames() {
        $tags = $this->getAllTags();
        $tagNames = array();
        foreach ($tags as $tag) {
           $tagNames[] = $tag->getName(); 
        }

        return $tagNames;
    }

    public function getAllTags() {
        if ($this->dbCacheProvider->cacheIsFresh('tags')) {
            $tags = $this->dbCacheProvider->fetch('tags');
        } else {
            // cache is not up-to-date
            $tags = $this->fetchTagsFromDb();
        }

        return $tags;
    }
}

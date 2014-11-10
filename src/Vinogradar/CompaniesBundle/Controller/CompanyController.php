<?php

namespace Vinogradar\CompaniesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Vinogradar\CompaniesBundle\Entity\Company;
use Vinogradar\CompaniesBundle\Entity\Tag;
use Vinogradar\CompaniesBundle\Form\Type\CompanyType;
use Vinogradar\CompaniesBundle\Form\Type\CategoryType;

class CompanyController extends Controller
{
    public function showAction($companyNameForUrl)
    {
        $company = $this->getDoctrine()
            ->getRepository('VinogradarCompaniesBundle:Company')
            ->findOneBy(array('nameForUrl' => $companyNameForUrl));

        return $this->render('VinogradarCompaniesBundle:Company:show.html.twig', array('company' => $company));
    }

    private function createTagsFromCsv($tagsCsv) {
        $tagNames = preg_split('/,|,\s/', $tagsCsv);
        var_dump($tagNames);
    }

    public function createAction(Request $request) {

        $company = new Company();

        $form = $this->createForm(new CompanyType(), $company);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $formData = $form->getData();
            $company = $formData;

            $tagProvider = $this->get('vinogradar_companies.tag_provider');
            $tags = $company->getTags();
            $tagProvider->replaceDuplicatesWithExistingTags($tags);
            $tagProvider->generateNamesForUrl($tags);

            $em = $this->getDoctrine()->getManager();
            $em->persist($company);
            $em->flush();

            //TODO: refactor this in service
            $dateTime = new \DateTime();
            $this->get('cache')
                ->save('companiesDbHash', sha1($dateTime->getTimestamp()));

            return $this->redirect(
                $this->generateUrl('vinogradar_companies_show_company',
                array('companyNameForUrl' => $company->getNameForUrl())));
        }

        return $this->render('VinogradarCompaniesBundle:Company:create.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function listAction() {
        $cacheDriver = $this->get('cache'); 

        if ($cacheDriver->fetch('cachedCompaniesDbHash') !==  $cacheDriver->fetch('companiesDbHash')) {
            // db has changed since last caching
            $tags = $this->fetchTagsFromDb();
        } else {
            // cached tags are up to date
            $tags = $cacheDriver->fetch('tags');
        }

        $viewData = array(
            'tags' => $tags
        );

        return $this->render('VinogradarCompaniesBundle:Company:list.html.twig', $viewData);
    }

    public function listByTagAction($tagNameForUrl) {
        $tag = $this->getDoctrine()
            ->getRepository('VinogradarCompaniesBundle:Tag')
            ->findOneBy(array('nameForUrl' => $tagNameForUrl));

        $viewData = array(
            'tag' => $tag
        );

        return $this->render('VinogradarCompaniesBundle:Company:list-by-tag.html.twig', $viewData);
    }

    private function fetchTagsFromDb() {

        $tagsManager = $this->getDoctrine()
            ->getManager();

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

        $cacheDriver = $this->get('cache'); 
        $cacheDriver->save('tags', $tags);
        $dateTime = new \DateTime();
        $cacheDriver->save('cachedCompaniesDbHash', $cacheDriver->fetch('companiesDbHash'));

        return $tags;
    }

}

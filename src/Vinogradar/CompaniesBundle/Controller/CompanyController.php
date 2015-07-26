<?php

namespace Vinogradar\CompaniesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

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

    public function createAction(Request $request) {
        $company = new Company();
        $form = $this->createForm(new CompanyType(), $company);
        $form->handleRequest($request);

        $tagProvider = $this->get('vinogradar_companies.tag_provider');
        $tagNamesForHint = $tagProvider->getAllTagNames();
        //$serializer = $this->container->get('serializer');
        //$tagsNamesForHintJson = $serializer->serialize($tagsForHint, 'json');
        $tagNamesForHintJson = json_encode($tagNamesForHint);

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

            $dbCacheProvider = $this->get('vinogradar_companies.db_cache_provider');

            // the database's checksum has changed
            $dbCacheProvider->updateDbHash('company');

            return $this->redirect(
                $this->generateUrl('vinogradar_companies_show_company',
                array('companyNameForUrl' => $company->getNameForUrl())));
        }

        return $this->render('VinogradarCompaniesBundle:Company:create.html.twig', array(
            'form' => $form->createView(),
            'tagsForHintJson' => $tagNamesForHintJson,
            'tagNamesForHint' => $tagNamesForHint,
        ));
    }

    public function listAction() {

        $tagProvider = $this->get('vinogradar_companies.tag_provider');
        $tags = $tagProvider->getAllTags();
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


}

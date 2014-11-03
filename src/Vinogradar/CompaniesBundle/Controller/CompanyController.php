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
    public function showAction($companyName)
    {
        $company = $this->getDoctrine()
            ->getRepository('VinogradarCompaniesBundle:Company')
            ->findOneByName($companyName);

        return $this->render('VinogradarCompaniesBundle:Company:show.html.twig', array('company' => $company));
    }

    private function createTagsFromCsv($tagsCsv) {
        $tagNames = preg_split('/,|,\s/', $tagsCsv);
        var_dump($tagNames);
    }

    public function createAction(Request $request) {
        $tags = $this->getDoctrine()
            ->getRepository('VinogradarCompaniesBundle:Tag')
            ->findAll();

        $company = new Company();
        foreach ($tags as $tag) {
            $company->addTag($tag);
        }

        $form = $this->createForm(new CompanyType(), $company);

        //\Doctrine\Common\Util\Debug::dump($request->request);
        //$companyFormParams = $request->request->get('vinogradar_companiesbundle_company');
        //if (isset($companyFormParams['tagsCsv'])) {
         //  echo "tagsCsv: $companyFormParams[tagsCsv]<br />";
          // $tags = $this->createTagsFromCsv($companyFormParams['tagsCsv']);

           //$request->request->remove('vinogradar_companiesbundle_company[tagsCsv]'); 
       // }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $formData = $form->getData();
            $company = $formData;
            $company->setLastUpdateDate(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->persist($company);
            $em->flush();

            return $this->redirect($this->generateUrl('vinogradar_companies_show_company', array('companyName' => $company->getName())));
        }

        return $this->render('VinogradarCompaniesBundle:Company:create.html.twig', array(
            'form' => $form->createView()
        ));
    }
}

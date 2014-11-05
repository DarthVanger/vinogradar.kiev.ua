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

            $em = $this->getDoctrine()->getManager();

            // check if user entered an existing tag
            $tags = $company->getTags();
            $tagRepository = $em->getRepository('VinogradarCompaniesBundle:Tag');
            foreach ($tags as $tag) {
                $duplicateTag = $tagRepository->findOneBy(array('name' => $tag->getName()));
                if ($duplicateTag) {
                    $company->removeTag($tag);
                    $company->addTag($duplicateTag);
                }
            }

            // generate nameForUrl from name for tags
            $tags = $company->getTags();
            foreach ($tags as $tag) {
                $tag->setNameForUrl($this->generateTagNameForUrl($tag->getName()));
            }

            $em->persist($company);
            $em->flush();

            $dateTime = new \DateTime();
            $this->get('cache')
                ->save('companiesDbHash', sha1($dateTime->getTimestamp()));

            return $this->redirect($this->generateUrl('vinogradar_companies_show_company', array('companyNameForUrl' => $company->getNameForUrl())));
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

    private function generateTagNameForUrl($str) {
        // переводим в транслит

        $str = $this->rus2translit($str);

        // в нижний регистр

        $str = strtolower($str);

        // заменям все ненужное нам на "-"

        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);

        // удаляем начальные и конечные '-'

        $str = trim($str, "-");

        return $str;
    }

    private function rus2translit($string) {

        $converter = array(

            'а' => 'a',   'б' => 'b',   'в' => 'v',

            'г' => 'g',   'д' => 'd',   'е' => 'e',

            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',

            'и' => 'i',   'й' => 'y',   'к' => 'k',

            'л' => 'l',   'м' => 'm',   'н' => 'n',

            'о' => 'o',   'п' => 'p',   'р' => 'r',

            'с' => 's',   'т' => 't',   'у' => 'u',

            'ф' => 'f',   'х' => 'h',   'ц' => 'c',

            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',

            'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',

            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

            

            'А' => 'A',   'Б' => 'B',   'В' => 'V',

            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',

            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',

            'И' => 'I',   'Й' => 'Y',   'К' => 'K',

            'Л' => 'L',   'М' => 'M',   'Н' => 'N',

            'О' => 'O',   'П' => 'P',   'Р' => 'R',

            'С' => 'S',   'Т' => 'T',   'У' => 'U',

            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',

            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',

            'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',

            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',

        );

        return strtr($string, $converter);

    }
}

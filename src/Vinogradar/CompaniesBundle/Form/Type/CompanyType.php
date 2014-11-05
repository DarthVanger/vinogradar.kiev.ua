<?php

namespace Vinogradar\CompaniesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Vinogradar\CompaniesBundle\Form\Type\TagType;

class CompanyType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array('label' => 'Название компании'))
            ->add('name_for_url', 'text', array(
                'label' => 'Название компании для ссылки (английскими, вместо пробелов ставьте дефисы)')
            )
            ->add('description', 'textarea', array('label' => 'Описание'))
            ->add('contact', 'textarea', array('label' => 'Контакты'))
            ->add('address', 'text', array('label' => 'Адрес'))
            ->add('website', 'text', array('label' => 'Веб-сайт (не обязательно)', 'required' => false))
            //->add('lastUpdateDate')
            //->add('viewsNumber')
            ->add('tags', 'collection', array(
                'type' => new TagType(),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ))
            ->add('save', 'submit', array('label' => 'Сохранить'));
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vinogradar\CompaniesBundle\Entity\Company'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'vinogradar_companiesbundle_company';
    }
}

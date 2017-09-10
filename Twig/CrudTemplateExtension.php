<?php

namespace PetkoparaCrudGeneratorBundle\Twig;

class CrudTemplateExtension extends \Twig_Extension
{

    public function getFilters()
    {
        return array(
            new \Twig_Filter('humanize', array('Symfony\Component\Form\FormRenderer', 'humanize')),
        );
    }

}
<?php

namespace PetkoparaCrudGeneratorBundle\Twig;

/**
 * Class CrudTemplateExtension
 * @package PetkoparaCrudGeneratorBundle\Twig
 *
 * This class adds two Twig filters: "humanize_lc" and "humanize_uc". Both of them
 * split camelCase and snake_case strings on their uppercase letters and "_" character,
 * respectively, to form a more human readable sentence.
 *
 * The _lc variant returns all words in the sentence in lower case. The _uc variant
 * makes all words in the sentence lowercase, except the first word which has its
 * first letter uppercase.
 *
 */
class CrudTemplateExtension extends \Twig_Extension
{

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('humanize_lc', array($this, 'humanize_lc')),
            new \Twig_SimpleFilter('humanize_uc', array($this, 'humanize_uc'))
        );
    }

    /**
     * Splits the incoming $text string on all uppercase letters and "_" characters,
     * and returns the resulting words as a sentence. All words in the output sentence
     * are lowercase.
     *
     * @param string $text
     * @return string
     */
    public function humanize_lc($text)
    {
        return strtolower(trim(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $text)));
    }

    /**
     * Splits the incoming $text string on all uppercase letters and "_" characters,
     * and returns the resulting words as a sentence. The first character of the first
     * word is set to uppercase, all other letters are set to lowercase.
     *
     * @param string $text
     * @return string
     */
    public function humanize_uc($text)
    {
        return ucfirst($this->humanize_lc($text));
    }

}
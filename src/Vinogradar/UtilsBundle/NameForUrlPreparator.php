<?php

namespace Vinogradar\UtilsBundle;

class NameForUrlPreparator
{
    private $delimiter = '-';

    /**
     * @copydoc self::slug 
     */
    public function cleanUp($name) {
        return $this->slug($name);
    }

    /**
     * Function from stackoverflow for cleaning up string for url
     * url: http://stackoverflow.com/questions/2103797/url-friendly-username-in-php 
     */
    public function slug($string) {
        return strtolower(trim(preg_replace('~[^0-9a-z]+~i', $this->delimiter, html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
    }
}

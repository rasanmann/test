<?php
namespace Drupal\yqb_blocks\TwigExtension;

class DateExtension extends \Twig_Extension {

    /**
     * Generates a list of all Twig filters that this extension defines.
     */
    public function getFilters() {
        return [
            new \Twig_SimpleFilter('compact_month', array($this, 'compactMonth')),
        ];
    }

    /**
     * Gets a unique identifier for this Twig extension.
     */
    public function getName() {
        return 'yqb_blocks.twig_extension';
    }

    /**
     * Replaces all instances of "animal" in a string with "plant".
     */
    public static function compactMonth($string) {
        // TODO : create table
        return mb_strtolower(mb_substr($string, 0, 3));
    }

}
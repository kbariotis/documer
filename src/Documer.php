<?php

namespace Documer;

use Documer\Storage\Adapter;

Class Documer
{

    /**
     * @var Adapter Storage Adapter
     */
    var $storage;

    function __construct($storage)
    {
        mb_internal_encoding("UTF-8");

        if ($storage instanceof Adapter)
            $this->storage = $storage;
        else
            throw new \Exception('Storage must implement Documer\Storage\Adapter interface.');
    }

    /**
     * This will return if a word is starting with an Uppercase letter, with UTF-8 Support
     *
     * @see http://stackoverflow.com/questions/2814880/how-to-check-if-letter-is-upper-or-lower-in-php
     *
     * @param $str String The string to examine
     */
    private function startsWithUppercase($str)
    {
        $chr = mb_substr($str, 0, 1, "UTF-8");

        return mb_strtolower($chr, "UTF-8") != $chr && mb_strlen($str, "UTF-8") > 1;
    }

    /**
     * This is our training method, that parses the text and push the keywords to DB
     *
     * @param $label
     * @param $text
     */
    public function train($label, $text)
    {
        $keywords = $this->parse($text);

        $this->getStorage()
             ->insertLabel($label);

        foreach ($keywords as $k) {
            $this->getStorage()
                 ->insertWord($k, $label);

        }
    }

    /**
     * We need to parse the text from some sort of tokenization
     *
     * We keep only alphanumeric strings that starts with an uppercase
     *
     * @param $text
     *
     * @return array
     */
    private function parse($text)
    {
        $unwantedChars = array(
            ',', '!', '?', '.', ']', '[', '!', '"', '#', '$', '%', '&', '\'', '(', ')', '*', '+', '/', ':', ';', '<',
            '=', '>', '?', '^', '{', '|', '}', '~', '-', '@', '\', ', '_', '`'
        );

        $str   = str_replace($unwantedChars, ' ', $text);
        $array = explode(" ", $str);
        $array = array_map('trim', $array);
        $array = array_unique($array);
        $array = array_values($array);

        $clean = array();
        foreach ($array as $k)
            if ($this->startsWithUppercase($k))
                $clean[ ] = $k;

        return array_values($clean);
    }

    /*
     * This is the guessing method, which uses Bayes Theorem to calculate probabilities
     */
    public function guess($text)
    {
        $scores = array();
        $words  = $this->parse($text);

        $labels = $this->getStorage()
                       ->getDistinctLabels();

        foreach ($labels as $label) {
            $logSum = 0;

            foreach ($words as $word) {

                $wordTotalCount = $this->getStorage()
                                       ->getWordCount($word);

                if ($wordTotalCount == 0) {

                    continue;

                } else {

                    $wordProbability        = $this->getStorage()
                                                   ->getWordProbabilityWithLabel($word, $label);
                    $wordInverseProbability = $this->getStorage()
                                                   ->getInverseWordProbabilityWithLabel($word, $label);

                    /**
                     * Prevent division with zero
                     */
                    if ($wordProbability + $wordInverseProbability == 0)
                        continue;

                    $wordicity = $this->getWordicity($wordTotalCount, $wordProbability, $wordInverseProbability);
                }

                /**
                 * logs to avoid "floating point underflow",
                 */
                $logSum += (log(1 - $wordicity) - log($wordicity));
            }


            /**
             * undo the log function and get back to 0-1 range
             */
            $scores[ $label ] = 1 / (1 + exp($logSum));
        }


        return $scores;
    }

    public function getWordicity($wordTotalCount, $wordProbability, $wordInverseProbability)
    {
        $denominator = $wordProbability + $wordInverseProbability;

        /**
         * Bayes Theorem using the above parameters
         *
         * the probability that this document is a particular LABEL
         * given that a particular WORD is in it
         *
         */
        $wordicity = $wordProbability / $denominator;

        /*
         * here 0.5 is the weight, higher training data in the db means higher weight
         */
        $wordicity = ((10 * 0.5) + ($wordTotalCount * $wordicity)) / (10 + $wordTotalCount);

        if ($wordicity == 0)
            $wordicity = 0.01;
        else if ($wordicity == 1)
            $wordicity = 0.99;

        return $wordicity;
    }

    /**
     * Check if text is of the given label
     *
     * @param $label
     * @param $text
     */
    public function is($label, $text)
    {
        $scores = $this->guess($text);

        $value = max($scores);

        return $label == array_search($value, $scores);
    }

    /**
     * @return Adapter
     */
    public function getStorage()
    {
        return $this->storage;
    }

}


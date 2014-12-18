<?php

namespace Classifier;

/**
 *
 * A simple document classifier
 *
 * @author: kbariotis (konmpar at gmail dot com)
 *
 * That's a basic document classification algorithm inspired by Burak Kanber at
 * http://burakkanber.com/blog/machine-learning-naive-bayes-1/( Thanks for the great article dude! ).
 * I basicaly rewrite the code in PHP
 * and added some modifications to create a document classification example rather than a language detection system.
 *
 */
Class Classifier
{

    /**
     * @var Spot Spot Object
     */
    var $db;

    function __construct(\Spot\Locator $spot)
    {
        date_default_timezone_set('Europe/Athens');

        $this->db = $spot;

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

        // must be first uppercase letter and more than 2 letters
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

        $labelMapper = $this->db->mapper('Classifier\Entity\Label');
        $labelMapper->migrate();
        $labelModel       = $labelMapper->get();
        $labelModel->name = $label;
        $labelMapper->insert($labelModel);

        foreach ($keywords as $k) {

            $wordMapper = $this->db->mapper('Classifier\Entity\Word');
            $wordMapper->migrate();
            $wordModel        = $wordMapper->get();
            $wordModel->label = $label;
            $wordModel->name  = $k;
            $wordMapper->insert($wordModel);

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
        $str           = str_replace($unwantedChars, ' ', $text);
        $strToArray    = explode(" ", $str);
        $finalArray    = array_unique($strToArray);

        $array = array_values($finalArray);

        $result = array_map('trim', $array);

        $clean = array();
        foreach ($result as $k)
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

        $labels = $this->getDistinctLabels();

        foreach ($labels as $label) {
            $logSum = 0;

            foreach ($words as $word) {

                $wordTotalCount = $this->getWordCount($word);

                if ($wordTotalCount == 0) {

                    continue;

                } else {

                    $wordProbability        = $this->getWordProbabilityWithLabel($word, $label);
                    $wordInverseProbability = $this->getInverseWordProbabilityWithLabel($word, $label);

                    /**
                     * Bayes Theorem using the above parameters
                     *
                     * the probability that this document is a particular LABEL
                     * given that a particular WORD is in it
                     *
                     */
                    $wordicity = $wordProbability / ($wordProbability + $wordInverseProbability);

                    /*
                     * here 0.5 is the weight, higher training data in the db means higher weight
                     */
                    $wordicity = ((10 * 0.5) + ($wordTotalCount * $wordicity)) / (10 + $wordTotalCount);

                    if ($wordicity == 0)
                        $wordicity = 0.01;
                    else if ($wordicity == 1)
                        $wordicity = 0.99;
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


    /**
     * Get total documents the system has been trained,
     * by counting the number of labels (not distinct)
     *
     * @return int
     */
    public function getTotalDocs()
    {
        $labelMapper = $this->db->mapper('Classifier\Entity\Label');

        return count($labelMapper->all());
    }

    /**
     * Get how many documents we have seen so far for each label
     *
     * @return array
     */
    public function getTotalDocsGroupByLabel()
    {
        $labelMapper = $this->db->mapper('Classifier\Entity\Label');

        $eachLabelTotal = $labelMapper->query("SELECT name, COUNT( name ) AS total FROM labels GROUP BY name");

        $docCounts = array();
        foreach ($eachLabelTotal as $r)
            $docCounts[ $r->name ] = $r->total;

        return $docCounts;
    }

    public function getDistinctLabels()
    {
        $labelMapper = $this->db->mapper('Classifier\Entity\Label');

        $collection = $labelMapper->query("SELECT DISTINCT(name) FROM labels");
        $labels     = array();
        foreach ($collection as $r)
            array_push($labels, $r->name);

        return $labels;
    }

    /**
     * Get how many documents there are that does not contain a label
     * grouped by label
     *
     * @return array
     */
    public function getInverseTotalDocsGroupByLabel()
    {
        $docCounts = $this->getTotalDocsGroupByLabel();
        $totalDocs = $this->getTotalDocs();

        $docInverseCounts = array();
        foreach ($docCounts as $key => $item) {
            $docInverseCounts[ $key ] = $totalDocs - $item;
        }

        return $docInverseCounts;
    }

    /**
     * Get how many times we have seen this word before
     *
     * @param $word
     *
     * @return int
     */
    public function getWordCount($word)
    {
        $wordMapper = $this->db->mapper('Classifier\Entity\Word');

        return count(
            $wordMapper->query("SELECT COUNT(name) AS total FROM words WHERE name= :word",
                               [
                                   'word' => $word
                               ])
        );
    }

    /**
     * Get the probability that this word shows up in a LABEL document
     *
     * @param $word
     * @param $label
     *
     * @return float
     */
    public function getWordProbabilityWithLabel($word, $label)
    {
        $wordMapper = $this->db->mapper('Classifier\Entity\Word');

        $wordProbabilityTemp =
            $wordMapper->query("SELECT COUNT(name) AS total FROM words WHERE name=:word AND label=:label",
                               [
                                   'word'  => $word,
                                   'label' => $label,
                               ]);

        $docCounts = $this->getTotalDocsGroupByLabel();

        return $wordProbabilityTemp->first()->total / $docCounts[ $label ];
    }

    /**
     * Get the probability that this word shows up in a any other LABEL
     *
     * @param $word
     * @param $label
     *
     * @return float
     */
    public function getInverseWordProbabilityWithLabel($word, $label)
    {
        $wordMapper = $this->db->mapper('Classifier\Entity\Word');

        $wordInverseProbabilityTemp =
            $wordMapper->query("SELECT COUNT(name) AS total FROM words WHERE name=:word AND label <> :label",
                               [
                                   'word'  => $word,
                                   'label' => $label,
                               ]);

        $docInverseCounts = $this->getInverseTotalDocsGroupByLabel();

        return $wordInverseProbabilityTemp->first()->total / $docInverseCounts[ $label ];
    }

    /**
     * Singleton Pattern
     *
     * @return Classifier
     */
    public static function getInstance($spot)
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static($spot);
        }

        return $instance;
    }

}

?>

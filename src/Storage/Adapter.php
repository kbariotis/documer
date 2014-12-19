<?php

namespace Documer\Storage;

interface Adapter
{

    /**
     * Get distinct names of labels
     *
     * @return array
     */
    public function getDistinctLabels();

    /**
     * Get how many times we have seen this word before
     *
     * @param $word
     *
     * @return int
     */
    public function getWordCount($word);

    /**
     * Get the probability that this word shows up in a LABEL document
     *
     * @param $word
     * @param $label
     *
     * @return float
     */
    public function getWordProbabilityWithLabel($word, $label);

    /**
     * Get the probability that this word shows up in a any other LABEL
     *
     * @param $word
     * @param $label
     *
     * @return float
     */
    public function getInverseWordProbabilityWithLabel($word, $label);

    public function insertLabel($label);

    public function insertWord($word, $label);

}

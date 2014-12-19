<?php

namespace Documer\Storage;

class Memory implements Adapter
{

    var $words = array();
    var $labels = array();

    /**
     * Get total documents the system has been trained,
     * by counting the number of labels (not distinct)
     *
     * @return int
     */
    public function getTotalDocs()
    {
        return count($this->labels);
    }

    /**
     * Get how many documents we have seen so far for each label
     *
     * @return array
     */
    public function getTotalDocsGroupByLabel()
    {

        $results = array();
        foreach ($this->labels as $l)
            if (isset($results[ $l ]))
                $results[ $l ] += 1;
            else
                $results[ $l ] = 1;

        return $results;
    }

    /**
     * @see Adapter::getDistinctLabels()
     */
    public function getDistinctLabels()
    {

        $results = array();
        foreach ($this->labels as $l)
            if (!in_array($l, $results))
                $results[ ] = $l;

        return $results;
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
     * @see Adapter::getWordCount()
     */
    public function getWordCount($word)
    {
        $total = 0;
        foreach ($this->words as $w)
            if ($w[ 'word' ] == $word)
                $total++;

        return $total;
    }

    /**
     * @see Adapter::getWordProbabilityWithLabel()
     */
    public function getWordProbabilityWithLabel($word, $label)
    {
        $total = 0;
        foreach ($this->words as $k => $w)
            if ($w[ 'word' ] == $word && $w[ 'label' ] == $label)
                $total++;

        return $total;

    }

    /**
     * @see Adapter::getInverseWordProbabilityWithLabel()
     */
    public function getInverseWordProbabilityWithLabel($word, $label)
    {
        $total = 0;
        foreach ($this->words as $k => $w)
            if ($w[ 'word' ] == $word && $w[ 'label' ] != $label)
                $total++;

        return $total;
    }

    /**
     * @see Adapter::insertLabel()
     */
    public function insertLabel($label)
    {

        $this->labels[ ] = $label;

    }

    /**
     * @see Adapter::insertWord()
     */
    public function insertWord($word, $label)
    {

        $this->words[ ] = [
            'word'  => $word,
            'label' => $label
        ];

    }
}

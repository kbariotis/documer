<?php

namespace Documer\Storage\MySQL;

use Documer\Storage\Adapter;

class MySQL implements Adapter
{

    /**
     * @var Spot Spot Object
     */
    var $db;

    function __construct(\Spot\Locator $spot)
    {
        $this->db = $spot;
    }

    /**
     * Get total documents the system has been trained,
     * by counting the number of labels (not distinct)
     *
     * @return int
     */
    public function getTotalDocs()
    {
        $labelMapper = $this->db->mapper('Documer\Entity\Label');

        return count($labelMapper->all());
    }

    /**
     * Get how many documents we have seen so far for each label
     *
     * @return array
     */
    public function getTotalDocsGroupByLabel()
    {
        $labelMapper = $this->db->mapper('Documer\Entity\Label');

        $eachLabelTotal = $labelMapper->query("SELECT name, COUNT( name ) AS total FROM labels GROUP BY name");

        $docCounts = array();
        foreach ($eachLabelTotal as $r)
            $docCounts[ $r->name ] = $r->total;

        return $docCounts;
    }

    public function getDistinctLabels()
    {
        $labelMapper = $this->db->mapper('Documer\Entity\Label');

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
        $wordMapper = $this->db->mapper('Documer\Entity\Word');

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
        $wordMapper = $this->db->mapper('Documer\Entity\Word');

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
        $wordMapper = $this->db->mapper('Documer\Entity\Word');

        $wordInverseProbabilityTemp =
            $wordMapper->query("SELECT COUNT(name) AS total FROM words WHERE name=:word AND label <> :label",
                               [
                                   'word'  => $word,
                                   'label' => $label,
                               ]);

        $docInverseCounts = $this->getInverseTotalDocsGroupByLabel();

        return $wordInverseProbabilityTemp->first()->total / $docInverseCounts[ $label ];
    }

    public function insertLabel($label)
    {

        $labelMapper = $this->db->mapper('Documer\Entity\Label');
        $labelMapper->migrate();

        $labelModel       = $labelMapper->get();
        $labelModel->name = $label;
        $labelMapper->insert($labelModel);

    }

    public function insertWord($word, $label)
    {

        $wordMapper = $this->db->mapper('Documer\Entity\Word');
        $wordMapper->migrate();

        $wordModel        = $wordMapper->get();
        $wordModel->label = $label;
        $wordModel->name  = $word;
        $wordMapper->insert($wordModel);

    }
}

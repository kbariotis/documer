<?php
/**
 *
 * A simple document classifier
 * @author: kbariotis (konmpar at gmail dot com) 
 * 
 * That's a basic document classification algorithm inspired by Burak Kanber at 
 * http://burakkanber.com/blog/machine-learning-naive-bayes-1/( Thanks for the great article dude! ). I basicaly rewrite the code in PHP 
 * and added some modifications to create a document classification example rather than a language detection system.
 *
 */
 
//Show me errors while developing
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

//MySQL DB functions
require_once("DbConnect.class.php"); 

Class Classifier {

	var $text; /* holds the input text from the user */
	var $label; /* holds the input label from the user */
	
	var $db; /* DB connection object */
	
	var $words = array(); /* holds the keywords we are going to use */
	
	
	function __construct($text='', $label='') {
		$this->text = $text;
		$this->label = $label;
		
		$this->words = $this->parse($text);
		
		//Set up all yor paramaters for connection 
		$this->db = new DbConnect("host","username","password","dbname",$error_reporting=true,$persistent=false); 
		//Open the connection to your database 
		$this->db->open() or die($this->db->error()); 
		
		//required for non UTF-8 databases
		$this->db->query("SET NAMES UTF8") or die($db->error()); 
		
	}

	// this will return if a word is starting with an Uppercase letter, with UTF-8 Support
	// http://stackoverflow.com/questions/2814880/how-to-check-if-letter-is-upper-or-lower-in-php
	private function starts_with_upper($str) {
		$chr = mb_substr ($str, 0, 1, "UTF-8");
		// must be first uppercase letter and more than 2 letters
		return mb_strtolower($chr, "UTF-8") != $chr && mb_strlen($str, "UTF-8") > 1;
	}
	
	// this is our training method, which just parsing the text and push the keywords to DB
	public function train() {

		// put the label in the db
		$this->db->query("INSERT INTO labels VALUES('', '$this->label')") or die($this->db->error()); 
		
		// put each keyword in the db with it's label
		$words = $this->words;
		for($i = 0; $i<count($words);$i++){
			$this->db->query("INSERT INTO words VALUES('', '$this->label', '$words[$i]')") or die($this->db->error()); 
		}
	
	}
	
	// we need to parse the text from some sort of tokenization
	private function parse($text) {

		// strip unwanted characters and create an array with distinct values
		$unwantedChars = array(',', '!', '?', '.',']','[','!','"','#','$','%','&','\'','(',')','*','+','/',':',';','<','=','>','?','^','{','|','}','~','-', '@', '\', ', '_', '`'); 
		$str = str_replace($unwantedChars, ' ', $text); 
		$non1 = explode(" ", $str);
		$array1 = array_unique($non1);
		
		// fix array index
		$array = array_values($array1);
		
		// trim unwanted spaces
		$result = array_map('trim', $array);
		
		// length
		$length = count($array);
		
		$clean = array();
		
		for($i = 0; $i<$length-1;$i++){
			// if we detect a keyword we push it to our global keywords array
			if($this->starts_with_upper($result[$i])) {
				$clean[$i] = $result[$i];
			}
		}
		
		// fix array index and return
		return array_values($clean);
		
	}
	
	// this is the guessing method, which uses Bayes Theorem to calculate propabilities
	public function guess() {
		// get total documents we have seen so far
		$this->db->query("SELECT COUNT(label) AS total FROM labels") or die($this->db->error()); 
		$totalDocs = $this->db->fetchassoc();
		
		// holds how many documents we have seen so far for each label
		$docCounts = array();
		$this->db->query("SELECT label, COUNT( label ) AS total FROM labels GROUP BY label") or die($this->db->error()); 	
		while($row=$this->db->fetcharray()) { 
			$docCounts[$row['label']] = $row['total'];
		} 
		
		// holds how many documents there are that doesn't contain a label 
		$docInverseCounts = array();
		foreach($docCounts as $key => $item) {
			$docInverseCounts[$key] = $totalDocs['total'] - $item;
		}
		
		// fetch all labels
		$this->db->query("SELECT DISTINCT(label) FROM labels") or die($this->db->error()); 
		$labels = array();
		while($row=$this->db->fetcharray()) {  array_push($labels, $row['label']); }
		
		// for every label loop
		for ($j = 0; $j < count($labels); $j++) {
			$label = $labels[$j];
			$logSum = 0;
		
			// for every word in that label loop
			for ($i = 0; $i< count($this->words); $i++) {
				$word = $this->words[$i];
				
				// deternine how many times we have seen this word before
				$this->db->query("SELECT COUNT(word) AS total FROM words WHERE word='$word'") or die($this->db->error()); 
				$stemTotalCount = $this->db->fetchassoc();
				
				// if we haven't seen it before, skip it
				if($stemTotalCount['total']==0){
					continue;
				}else{
					// probability that this word shows up in a LABEL document
					$this->db->query("SELECT COUNT(word) AS total FROM words WHERE word='$word' AND label='$label'") or die($this->db->error()); 
					$wordProbabilityTemp = $this->db->fetchassoc();
					$wordProbability = $wordProbabilityTemp['total'] / $docCounts[$label];
					
					// probability that this word shows up in a any other LABEL
					$this->db->query("SELECT COUNT(word) AS total FROM words WHERE word='$word' AND label <> '$label'") or die($this->db->error()); 
					$wordInverseProbabilityTemp = $this->db->fetchassoc();
					$wordInverseProbability = $wordInverseProbabilityTemp['total'] / $docInverseCounts[$label];
					
					// HERE IS BAYES THEOREM using the above parameters
					// the probability that this document is a particular LABEL given that a particular WORD is in it
					$wordicity = $wordProbability / ($wordProbability + $wordInverseProbability);
					
					// here 1 is the weight, higher training data in the db means higher weight
					$wordicity = ( (10 * 0.5) + ($stemTotalCount['total'] * $wordicity) ) / ( 10 + $stemTotalCount['total'] );
					
					// *
					if ($wordicity == 0) $wordicity = 0.01;
					else if ($wordicity == 1) $wordicity = 0.99;
				}
				
				// logs to avoid "floating point underflow",
				$logSum += (log(1 - $wordicity) - log($wordicity));
			}
			
			// undo the log function and get back to 0-1 range
			$scores[$label] = 1 / ( 1 + exp($logSum) );
		}
		
		return $scores;
	}
	
}
?>

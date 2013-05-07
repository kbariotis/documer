Doc classifier
==============
 
* author: [kbariotis](mailto:konmpar@gmail.com)

	That's a basic document classification algorithm inspired by [Burak Kanber](http://burakkanber.com/blog/machine-learning-naive-bayes-1/). I basicaly rewrite the code in PHP and added some modifications to create a document classification example rather than a language detection system.

* Basic Concept

	_every document has key words e.g. *Margaret Thatcher*_
	_every document has a label e.g. *Politics*_
	
	Supose, that in every document there are *key words all starting with an uppercase letter*. We store these words in our DB end every time we need to guess a document against a particular *label*, we use Bayes algorithm.
	
	Let's clear that out:

* Training:
	First, we tokenize the document and keep only our key words(All words starting with an uppercase letter) in an array. We store that array in our DB(See example schema below). 

* Guessing:
	This is very simple. Again, we parse the document we want to be classified and create an array with the key words. Here is the pseudo code:
	
		for every label in DB
			for every key word in document
				P(label/word) = P(word/label)P(label) /	( P(word/label)P(label) + (1 - P(word/label))(1 - P(label)) )
						
* Basic DB Schema
	
	
		CREATE TABLE IF NOT EXISTS 'labels' (
			'id' int(11) NOT NULL AUTO_INCREMENT,
			'name' varchar(64) CHARACTER SET utf8 NOT NULL,
			PRIMARY KEY ('id')\n`
		);
		
		CREATE TABLE IF NOT EXISTS 'words' (
			'id' int(11) NOT NULL AUTO_INCREMENT,
			'label' varchar(64) CHARACTER SET utf8 NOT NULL,
			'name' varchar(64) CHARACTER SET utf8 NOT NULL,
			PRIMARY KEY ('id')
		);


* Proof of concept
	I have setup a simple interface (index.php) for the proof of concept. Download all files in your root directory of your web server. Create a new database with the above tables. Setup the classifier.class.php with your credentials and start training. After some training shoot a document in and get your results!


###I am looking forward for your thoughts on this. Thank you!
Kostas Bariotis

Bayes algorithm implementation in PHP for auto document classification.
==============
_author: [kbariotis](mailto:konmpar@gmail.com)_

Description
-----------------------------
Inspired by [Burak Kanber](http://burakkanber.com/blog/machine-learning-naive-bayes-1/). Thank you for that great
article!

Concept
-----------------------------

_every document has key words e.g. *Margaret Thatcher*_

_every document has a label e.g. *Politics*_

Suppose, that in every document there are *key words all starting with an uppercase letter*. We store these words in our DB end every time we need to guess a document against a particular *label*, we use Bayes algorithm.

Let's clear that out:

###Training:

	First, we tokenize the document and keep only our key words(All words starting with an uppercase letter) in an array. We store that array in our DB(See example schema below). 

###Guessing:

	This is very simple. Again, we parse the document we want to be classified and create an array with the key words. Here is the pseudo code:
	
		for every label in DB
			for every key word in document
				P(label/word) = P(word/label)P(label) /	( P(word/label)P(label) + (1 - P(word/label))(1 - P(label)) )
						
Usage
------------
Documer uses Spot2 to store it's knowledge. Spot2 supports MySQL/SQLite.

##Install through composer

```json
"require": {
    "kbariotis/documer": "dev-master"
  },
```

##Instantiate

Pass a [Spot](https://github.com/vlucas/spot2) object with your configuration to `getInstance`.

```
$cfg = new \Spot\Config();
$cfg->addConnection('mysql', 'mysql://user:password@localhost/documer');
$spot = new \Spot\Locator($cfg);

$documer = Classifier\Classifier::getInstance($spot);

```

##Train

```
$documer->train("politics", "A big and long text about a political act");
```

##Guess

```
$scores = $documer->guess("And an other big and long text about a political act");
```

`$scores` will hold an array with all labels of your system and the posibbility which the document will belong to
each label.

###I am looking forward for your thoughts on this. Thank you!

_Kostas Bariotis_

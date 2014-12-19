Documer
==============
Bayes algorithm implementation in PHP for auto document classification.

Concept
-----------------------------

_every document has key words e.g. *Margaret Thatcher*_

_every document has a label e.g. *Politics*_

Suppose, that in every document there are *key words all starting with an uppercase letter*. We store these words in our DB end every time we need to guess a document against a particular *label*, we use Bayes algorithm.

Let's clear that out:

**Training:**

First, we tokenize the document and keep only our key words (All words starting with an uppercase letter) in an array. We store that array in our DB.

**Guessing:**

This is very simple. Again, we parse the document we want to be classified and create an array with the key words. Here is the pseudo code:

	for every label in DB
		for every key word in document
			P(label/word) = P(word/label)P(label) /	( P(word/label)P(label) + (1 - P(word/label))(1 - P(label)) )

Usage
------------
**Install through composer**

```json
"require": {
    "kbariotis/documer": "dev-master"
  },
```

**Instantiate**

Pass a Storage Adapter object to the Documer Constructor.

```php

$documer = new Documer\Documer(new \Documer\Storage\Memory());
```

**Train**

```php
$documer->train('politics', 'This is text about Politics and more');
$documer->train('philosophy', 'Socrates is an ancent Greek philosopher');
$documer->train('athletic', 'Have no idea about athletics. Sorry.');
$documer->train('athletic', 'Not a clue.');
$documer->train('athletic', 'It is just not my thing.');
```

**Guess**

```php
$scores = $documer->guess('What do we know about Socrates?');
```

`$scores` will hold an array with all labels of your system and the posibbility which the document will belong to
each label.

**Storage Adapters**
Implement [Documer\Storage\Adapter](src/Storage/Adapter.php) to create your own Storage Adapter.

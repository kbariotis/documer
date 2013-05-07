<?php 
	require_once("Classifier.class.php"); 
	
	if(isset($_POST['text']) && isset($_POST['label'])){
		$text = $_POST['text'];
		$label = $_POST['label'];
		$classifier = new Classifier($text, $label);		
		$classifier->train();
	}
	
	if(isset($_POST['guess'])){	
		$text = $_POST['guess'];
		$classifier = new Classifier($text);	
		$result = $classifier->guess();		
	}
?>
<!DOCTYPE>
<html>
<head>
<meta charset="utf-8">
</head>
<body>

<form name="insert" action="" method="POST">
	<textarea rows="15" cols="100" name="text" placeholder="Place training data here"></textarea>
	<label for="label">Σε ποια κατηγορία ανήκει το άρθρο σας;</label>
	<select name="label">
		<option value ="Πολιτικά">Politics</option>
		<option value ="Αθλητικά">Sports</option>
		<option value ="Κοινωνικά">Social</option>
		<option value ="Ψυχαγωγία">Entertainment</option>
	</select>
	<input type="submit" value="Train me">
</form>

<form name="guess" action="" method="POST">
	<textarea rows="15" cols="100" name="guess" placeholder="Place document to be classified here"></textarea>
	<input type="submit" value="Guess">
</form>

<h2><?php if(isset($result)) print_r($result); ?></h2>

</body>
</html>
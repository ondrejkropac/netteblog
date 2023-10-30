<?php

//$data = json_decode(file_get_contents("php://input"), true);

//echo "Hello, " . $data["name"] . "!";



        // Vytvoření instance modelu
        //$spravceClanku = new SpravceClanku();
$arr = explode(",",$_POST['tags']);//print_r($arr);



//print_r($_POST);
$notes = array('tags' => $_POST['tags']);print_r($notes);
//$spravceClanku->ulozClanek($_POST['clanky_id'], $notes);

//pasáž zápis do DB!!!
/* až pocem ptákoviny! */
// Update the details below with your MySQL details
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'redakce_nette';
try {
    $pdo = new PDO('mysql:host=' . $DATABASE_HOST . ';dbname=' . $DATABASE_NAME . ';charset=utf8', $DATABASE_USER, $DATABASE_PASS);
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error
    exit('Failed to connect to database!');
}
$test['clanky_id']='1';//print_r($_POST['tags']);
// Page ID needs to exist, this is used to determine which comments are for which page
if (isset($test['clanky_id'])) {
    // Check if the submitted form variables exist
    if (isset($_POST['tags'])) { //$_POST['name'], 
        // POST variables exist, insert a new comment into the MySQL comments table (user submitted form)
        $stmt = $pdo->prepare('UPDATE clanky SET tags = ? WHERE clanky_id = ?');
        $stmt->execute([ $_POST['tags'], $test['clanky_id'] ]);//, $_POST['parent_id'], $_POST['name']

        exit('Your tags has been submitted!');
    }
    
    // Get all comments by the Page ID ordered by the submit date
    $stmt = $pdo->prepare('SELECT * FROM clanky WHERE clanky_id = ?'); // ORDER BY submit_date DESC
    $stmt->execute([ $_GET['clanky_id'] ]);
    $clanek = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    exit('No article ID specified!');
}


?>

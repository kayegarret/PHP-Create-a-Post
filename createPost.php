<?php

// This script is envoked when a user creates a new post which is refered to as a Statement on Pixacourt
// If you are wondering what a Statement is think of a Statement as a Tweet, they share similarities


// Establish database credentials
$servername = "localhost";
$username = "username";
$password = "password";

// Create the connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
	die("Connection failed" . $conn->connect_error);
}

// Get the data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Create vars to store data from the request
$statementText = $data['statementText'];
$senderID = $data['senderID'];
$senderName = $data['senderName'];
$statementID = $data['statementID'];
$mediaType = $data['mediaType'];
$date = $data['creationDate'];

// Create words array that holds all the words of the content of the post
$words = explode(" ", $statementText);

// Create an array to hold all the members tagged in the post
$membersTagged = Array();

// Loop through the words to check to see if any members were tagged
foreach($words as $value) {
	if (strpos($value,'@') !== false) {
		$membersTagged[] = $value;
	}
}

// Insert the data of the statement into the Statements table of the database
$sql = "INSERT INTO database.Statements (Text, SenderID, SenderName, StatementID, Media, CreationDate) VALUES ('" . $statementText . "', '" . $senderID . "','" . $senderName . "', '" . $statementID . "', '" . $mediaType . "', '" . $date . "')";
if ($conn->query($sql) === TRUE) {

	// Add a comma at the end of the statementID (this is for storage and parsing purposes)
	$statementID = $statementID . ',';

	// Add the statementID to the senders profile by adding it to their StatementIDs column
	$sql2 = "UPDATE database.MembershipIDs SET StatementIDs = CONCAT(StatementIDs, '$statementID') WHERE Username=('" . $senderID . "')";
	if ($conn->query($sql2) === TRUE) {
			
		// Init sql var
		$sql3 = "";

		// Create count var to keep track of index in loop
		$count = 0;

		// Create mentioned statement id to store for the user that was mentioned in mentioned users notifications
		$mentionedStatementID = 'MentionedStatement:' . $statementID;

		// Loop through the members tagged array and to store the data in each of the mentioned users notifications
		foreach($membersTagged as $value) {

			// The last index in the members tagged array will be equal to "" so check if it is empty and if it is exit the loop
        	if (empty($membersTagged)) {
            	break;
        	}

			// If the count is equal to 0 this means that this is the first line of the sql query, it needs to be initialized with just an = sign
    		if ($count == 0) {
    			$sql3 = "UPDATE database.Notifications SET NotificationData = CONCAT(NotificationData, '$mentionedStatementID'), PushNumber = PushNumber + 1 WHERE Username=('" . $value . "');";
    		}
    		// This is not the first line of the sql querry, it needs to be added in additon to the queries before it
    		else {
    			$sql3 .= "UPDATE database.Notifications SET NotificationData = CONCAT(NotificationData, '$mentionedStatementID'), PushNumber = PushNumber + 1 WHERE Username=('" . $value . "');";
    		}	

    		// Increase the count
			++$count;
		}

		// Execute multi query
		if (mysqli_multi_query($conn,$sql3)) {

			// Relay data back to client, in this case let the client side know that the script succeded
			echo '{"result" : "success"}';
		}
		// No members were tagged/mentioned in the post
		else {

			// Check to make sure the fact that no members were tagged in the post is the reason why the sql multi query failed
			if (empty($membersTagged)) {
            	echo '{"result" : "success"}';
        	}
        	else {
        		// Something went wrong with the query, let the client side know
        		echo '{"result" : "error"}';
        	}
		}


	}
	else {
		// Something went wrong with the query, let the client side know
		echo '{"result" : "error"}';
	}
}
else {
	// Something went wrong with the query, let the client side know
	echo '{"result" : "error"}';
}

// Close the connection
$conn->close();



?>

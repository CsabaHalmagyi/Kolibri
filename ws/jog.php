<?php
require_once '../includes/connection.inc.php';
require_once '../includes/dbservice.inc.php';
//is_logged_out();

$errorCode = 0;
$message = "";

function printResponse($errorCode, $message, $response){
	$responseArray = array (
			'serviceName' => 'kolibri_rest',
			'errorCode' => $errorCode,
			'message' => $message,
			'data' => $response
	);

	header ( "Access-Control-Allow-Origin: *" );
	header ( 'Content-Type: application/json; charset=utf-8');
	echo json_encode ( $responseArray );
	die();
}

if (!isset($_SESSION['felhasznalo']['felhasznalo_id'])) printResponse(1000, "Logout", null);
if ($_SESSION['jog']['admin'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null);

if(count($_POST)>0){

	$action = $_POST['action'];
	//ACTION: createNewGroup
	if($action == "createNewGroup"){
		$name = $_POST['name'];

		if (strlen($name)<2){
			$errorCode = 2;
			$message = "Túl rövid csoportnév!";
			printResponse($errorCode,$message,null);
		}
		else{
			$perm = json_decode($_POST['permissions'],true);
			$perm['uj_2']=true;

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null);
			}

			$result = createNewGroup($dbh, $name, $perm);
			printResponse($result['errorCode'], $result['message'], null);
		}

	}
	//ACTION: deleteGroup
	else if($action == "deleteGroup"){
		$groupID = $_POST['groupID'];

		if($groupID != "1"){
				
			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null);
			}
				
			$result = deleteGroup($dbh, $groupID);
			printResponse($result['errorCode'], $result['message'], null);
		}

	else{
		$errorCode = 3;
		$message = 'Admin nem törölhető!';
		printResponse($errorCode,$message,null);
	}
}
//ACTION: updateGroup
else if($action == "updateGroup"){
	$groupID = $_POST['groupID'];

	if (strlen($groupID)<1){
		$errorCode = 5;
		$message = "Hiányzó csoport azonosító!";
		printResponse($errorCode,$message,null);
	}
	else{
		$perm = json_decode($_POST['permissions'],true);
		$perm[$groupID.'_2']=true;

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null);
			}
				
			$result = updateGroup($dbh, $groupID, $perm);
			printResponse($result['errorCode'], $result['message'], null);
	}
}
else{
	$errorCode = 1;
	$message = 'Ismeretlen feladat!';
	printResponse($errorCode,$message,null);

}



}







?>
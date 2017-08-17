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

	//ACTION: getUsers
	if($action == "getUsers"){

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null);
		}
			
		$response = getUsersAndGroups($dbh);

		if(count($response['users'])>0) {
			$message = "Felhasználók lekérdezve.";
			printResponse(0,$response['groups'],$response['users']);
		}
		else{
			$message = "Felhasználó tábla üres.";
			printResponse(8,$message,null);
		}
	}
	//ACTION: getAllGroups
	else if($action == "getAllGroups"){

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null);
		}
			
		$response = getUsersAndGroups($dbh);

		$csoportok = $response['groups'];
			
		if(count(csoportok) != 0){
			$errorCode = 9;
			$message = 'Nincsenek csoportok definiálva!';
			printResponse($errorCode,$message,null);
		}
		else{
			$message = 'Sikeres csoport beolvasás.';
			printResponse(0,$message,$csoportok);
		}

	}
	//ACTION: createNewUser
	else if($action == "createNewUser"){

		$group = $_POST['group'];
		$userName = $_POST['username'];
		$pass = $_POST['password'];
		$firstName = $_POST['firstname'];
		$surName = $_POST['surname'];

		if (empty($group) || empty($userName) || empty($pass) || empty($firstName) || empty($surName)){
			$errorCode = 10;
			$message = "Hiányzó felhasználói adat!";
			printResponse($errorCode,$message,null);
		}
		else{

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null);
			}
				
			$result = createNewUser($dbh, $userName, $pass, $firstName, $surName, $group);
			printResponse($result['errorCode'], $result['message'], null);
		}

	}
	//ACTION: deleteUser
	else if($action == "deleteUser"){

		$userID = $_POST['userid'];

		if (empty($userID)){
			$errorCode = 12;
			$message = "Hiányzó felhasználó azonosító!";
			printResponse($errorCode,$message,null);
		}
		else if($userID == $_SESSION['felhasznalo']['felhasznalo_id']){
			$errorCode = 13;
			$message = "Hülye! Hát ki ne töröld már a saját fiókodat...";
			printResponse($errorCode,$message,null);
		}
		else{

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null);
			}
				
			$result = deleteUser($dbh, $userID);
			printResponse($result['errorCode'], $result['message'], null);
		}
	}
	//ACTION: updateUser
	else if($action == "updateUser"){

		$userID = $_POST['userid'];

		if (empty($userID)){
			$errorCode = 12;
			$message = "Hiányzó felhasználó azonosító!";
			printResponse($errorCode,$message,null);
		}
		else{

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null);
			}
				
			$result = updateUser($dbh, $userID, $_POST['firstname'], $_POST['surname'],
			$_POST['group'], $_POST['status'], $_POST['pass']);
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
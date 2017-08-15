<?php
require_once '../includes/connection.inc.php';
require_once '../includes/dbservice.inc.php';
//is_logged_out();


$errorCode = 0;
$message = "";

function printResponse($errorCode, $message, $response1, $response2, $response3){
	$responseArray = array (
			'serviceName' => 'kolibri_rest',
			'errorCode' => $errorCode,
			'message' => $message,
			'data' => $response1,
			'data2' => $response2,
			'data3' => $response3
	);

	header ( "Access-Control-Allow-Origin: *" );
	header ( 'Content-Type: application/json; charset=utf-8');
	echo json_encode ( $responseArray );
	die();
}

if (!isset($_SESSION['felhasznalo']['felhasznalo_id'])) printResponse(1000, "Logout", null, null,null);
if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null, null);

if(count($_POST)>0){

	$action = $_POST['action'];
	//ACTION: getUsers
	//penzugyi kodok lekerdezes!
	if($action == "getDorms"){

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
		}

		$result = getDormsWithFinancialCodes($dbh);

		if($result['kollegiumok'] != null){
			$message = "Kollégiumok lekérdezve.";
			printResponse(0,$message,$result['kollegiumok'],$result['penzugyikodok'],null);
		}
		else{
			$message = "Kollégium tábla üres.";
			printResponse(18,$message,null,null,null);
		}

	}

	//ACTION: getRoomDetails
	//
	else if($action == "getRoomDetails"){

		if(isset($_POST['koliID']) && !empty($_POST['koliID'])){
			$koliID = $_POST['koliID'];
		}
		else{
			$errorCode = 30;
			$message = 'Hiányzó kollégium azonosító!';
			printResponse($errorCode,$message,null,null,null);
		}

		if(isset($_POST['szobaID']) && !empty($_POST['szobaID'])){
			$szobaID = $_POST['szobaID'];
		}
		else{
			$errorCode = 30;
			$message = 'Hiányzó szoba azonosító!';
			printResponse($errorCode,$message,null, null,null);
		}

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
		}

		$szobalakok = getRoomDetails($dbh, $koliID, $szobaID);

		$szobatlan_hallgatok = getStudentsWithoutRoom($dbh, $koliID);

		printResponse(0,"",$szobalakok, $szobatlan_hallgatok, null);
	}
	else if($action == "kartyaKiadas"){

		if(strlen($_POST['hallgato'])>0 && strlen($_POST['kartya'])>0){

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
			}

			$result = assignCardToStudent($dbh, $_POST['hallgato'], $_POST['kartya']);
			printResponse($result['errorCode'], $result['message'], null, null, null);

		}
		else{
			printResponse(1,"Hiányzó adatok!",null, null,null);
		}
	}
	else if($action == "kartyaVisszavetel"){

		if(strlen($_POST['bejegyzes'])>0){

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
			}

			$result = revokeCardFromStudent($dbh, $_POST['bejegyzes']);
			printResponse($result['errorCode'], $result['message'], null, null, null);

		}
		else{
			printResponse(1,"Hiányzó adatok!",null, null,null);
		}

	}
	else if($action == "tanevZaras"){

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
		}

		//mindket epuletben nulla a szobaval rendelkezo hallgatok szama
		if(count(getStudentsWithRoom($dbh, 1)) == 0 && count(getStudentsWithRoom($dbh, 2)) == 0){
			//truncate felvett hallgatok lista
			clearEnrollmentList($dbh);

			$message = "Tanév lezárva, minden listára felvett hallgató törölve.";
			printResponse(0,$message,null,null,null);
		}
		else{

			$message = "Még vannak hallgatók a kollégium(ok)ban, a tanév nem zárható le.";
			printResponse(49,$message,null,null,null);
		}


	}
	else if($action == "tanevValtas"){

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
		}

		if(count(getStudentsWithRoom($dbh, 1)) > 0 || count(getStudentsWithRoom($dbh, 2)) > 0){

			$message = "Tanév váltás csak lezárt tanévet követően lehetséges.";
			printResponse(52,$message,null,null,null);
		}


		if(isset($_POST['tanev']) && !empty($_POST['tanev']) && intval($_POST['tanev']) != 0){

			$tanev = intval($_POST['tanev']);
			setCurrentSemester($dbh, $tanev);

			$message = "Tanév váltás sikeres.";
			printResponse(0,$message,null,null,null);

		}
		else{
			$errorCode = 39;
			$message = 'Hibás tanév azonosító!';
			printResponse($errorCode,$message,null,null,null);
		}

	}

	else{
		$errorCode = 1;
		$message = 'Ismeretlen feladat!';
		printResponse($errorCode,$message,"",null);

	}



}







?>
<?php
require_once '../includes/connection.inc.php';
//is_logged_out();
require_once '../settings/db.php';

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
if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null);

if(count($_POST)>0){
	
	$action = $_POST['action'];	
	//ACTION: getUsers	
	//penzugyi kodok lekerdezes!
	if($action == "getDorms"){
		try {
			$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
		}catch (PDOException $e) {
			echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
			die();
		}
			
		$dbh -> exec("SET CHARACTER SET utf8");
		$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
			
		$sql = 'SELECT * FROM kolibri_kollegiumok';
		
		$sth = $dbh->prepare($sql);
		$sth->execute();
		
		$kollegiumok = $sth->fetchAll(PDO::FETCH_ASSOC);

		
		$sql = 'SELECT pk_id, kollegium_id, kollegiumi_dij FROM kolibri_penzugyi_kodok';
		$sth = $dbh->prepare($sql);
		$sth->execute();
		$penzugyikodok = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		if(count($kollegiumok)>0) {
			$message = "Kollégiumok lekérdezve.";
			printResponse(0,$message,$kollegiumok,$penzugyikodok,null);
				
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
		
		try {
			$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
		}catch (PDOException $e) {
			$message = "Adatbázis hiba - ".$e->getMessage();
			printResponse(1,$message,null,null,null);
		}
			
		//return szobalakók
		//több bejegyzés kezelése
		
		$dbh -> exec("SET CHARACTER SET utf8");
		$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
			
		$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod, kolibri_hallgatok.hallgato_neve, kolibri_szoba_reszletek.bekoltozes_datuma  
				FROM kolibri_szoba_reszletek
				INNER JOIN kolibri_hallgatok
				ON kolibri_szoba_reszletek.hallgato_id = kolibri_hallgatok.hallgato_id
				WHERE kolibri_szoba_reszletek.tanev_id = :tanevid
				AND kolibri_szoba_reszletek.kollegium_id = :kollid
				AND kolibri_szoba_reszletek.szoba_id = :szobaid
				and kolibri_szoba_reszletek.kikoltozes_datuma = "0000-00-00 00:00:00"';
		
		$sth = $dbh->prepare($sql);
		$sth->bindParam(':kollid', $koliID);
		$sth->bindParam(':szobaid', $szobaID);
		$sth->bindParam(':tanevid', $_SESSION['beallitasok']['aktualis_tanev_id']);
		$sth->execute();
		
		$szobalakok = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		
		//adott koliba felvett, szoba nélküli hallgatók listázása
		
		$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_felvettek
					INNER JOIN kolibri_hallgatok
					ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
					WHERE kolibri_felvettek.tanev_id = :tanevid
					AND kolibri_felvettek.kollegium_id = :kollid
					AND kolibri_felvettek.szobaba_beosztva = "0"
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 10';
		
		$sth = $dbh->prepare($sql);
		$sth->bindParam(':tanevid', $_SESSION['beallitasok']['aktualis_tanev_id']);
		$sth->bindParam(':kollid', $koliID);
		
		$sth->execute();
		//file_put_contents("koll.log", $sth->debugDumpParams());
		
		$szobatlan_hallgatok = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		printResponse(0,"",$szobalakok, $szobatlan_hallgatok, null);
	}
	else if($action == "kartyaKiadas"){
		
		if(strlen($_POST['hallgato'])>0 && strlen($_POST['kartya'])>0){
			
			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				$message = "Adatbázis hiba - ".$e->getMessage();
				printResponse(1,$message,null,null,null);
			}
			
			
			$dbh -> exec("SET CHARACTER SET utf8");
			$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
				
			$sql = 'SELECT * FROM kolibri_belepokartyak
					WHERE kartya_szam = :kartya
					AND leadas_datuma = "0000-00-00 00:00:00"';
			
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':kartya', $_POST['kartya']);
			$sth->execute();
			
			$hasznalo = $sth->fetchAll(PDO::FETCH_ASSOC);
			
			//valakinel ott van a kartya
			if(count($hasznalo)>0){
				printResponse(2,"Ez a kártya már/még egy másik hallgatónál van.",null,null,null);
			}
			else{
				
				$sql = 'INSERT INTO kolibri_belepokartyak (tanev_id, hallgato_id, kartya_szam, felvetel_datuma)
						VALUES(:tanev, :hallgato, :kartya, :felvetel)';
					
				$sth = $dbh->prepare($sql);
				$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
				$sth->bindParam(':hallgato', $_POST['hallgato']);
				$sth->bindParam(':kartya', $_POST['kartya']);
				$sth->bindParam(':felvetel', date('Y-m-d H:i:s'));
				$sth->execute();
				
				printResponse(0,"Siker.",null,null,null);
			}
		}
		else{
			printResponse(1,"Hiányzó adatok!",null, null,null);
		}
	}
	else if($action == "kartyaVisszavetel"){
		
		if(strlen($_POST['bejegyzes'])>0){
				
			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				$message = "Adatbázis hiba - ".$e->getMessage();
				printResponse(1,$message,null,null,null);
			}
			
			$dbh -> exec("SET CHARACTER SET utf8");
			$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
			
			$sql = 'UPDATE kolibri_belepokartyak SET leadas_datuma = :leadas
					WHERE kartya_bejegyzes_id = :bejegyzes';
				
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':leadas', date('Y-m-d H:i:s'));
			$sth->bindParam(':bejegyzes', $_POST['bejegyzes']);
			$sth->execute();
			
			printResponse(0,"Siker.",null,null,null);
			
		}
		
	}
	
	
	else{
		$errorCode = 1;
		$message = 'Ismeretlen feladat!';
		printResponse($errorCode,$message,"",null);
		
	}
	
	
	
}







?>
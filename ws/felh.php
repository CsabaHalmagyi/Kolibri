<?php
require_once '../includes/connection.inc.php';
//is_logged_out();
require_once '../settings/db.php';

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
		try {
			$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
		}catch (PDOException $e) {
			echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
			die();
		}
			
		$dbh -> exec("SET CHARACTER SET utf8");
		$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
			
		$sql = 'SELECT kolibri_felhasznalok.*,kolibri_jogcsoportok.id, kolibri_jogcsoportok.csoportnev 
				FROM kolibri_felhasznalok INNER JOIN kolibri_jogcsoportok 
				ON kolibri_felhasznalok.csoport=kolibri_jogcsoportok.id ORDER BY kolibri_felhasznalok.felhasznalo_id';
		
		$sth = $dbh->prepare($sql);
		$sth->execute();
		
		$felhasznalok = $sth->fetchAll(PDO::FETCH_ASSOC);

		$sql = 'SELECT id, csoportnev
				FROM kolibri_jogcsoportok';
		
		$sth = $dbh->prepare($sql);
		$sth->execute();
		$csoportok = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		if(count(felhasznalok)>0) {
			$message = "Felhasználók lekérdezve.";
			printResponse(0,$csoportok,$felhasznalok);
				
		}
		else{
			$message = "Felhasználó tábla üres.";
			printResponse(8,$message,null);
				
		}
			
		
		
	}
	//ACTION: getAllGroups
	else if($action == "getAllGroups"){

			//check whether any user belongs to that group
			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}
				
			$dbh -> exec("SET CHARACTER SET utf8");
			$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
			
			$sql = 'SELECT id, csoportnev FROM kolibri_jogcsoportok';
			$sth = $dbh->prepare($sql);
			$sth->execute();

			$csoportok = $sth->fetchAll(PDO::FETCH_ASSOC);
			
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
		$group = $_POST['group'];
	
		if (empty($group) || empty($userName) || empty($pass) || empty($firstName) || empty($surName)){
			$errorCode = 10;
			$message = "Hiányzó felhasználói adat!";
			printResponse($errorCode,$message,null);
		}
		else{

			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}
			
			$dbh -> exec("SET CHARACTER SET utf8");
			$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
			
			//check if user exists
			$sql = "SELECT felhasznalonev FROM kolibri_felhasznalok WHERE felhasznalonev = :felhnev";
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':felhnev', $surName);
			$sth->execute();
			
			//the user does not exist, can be inserted
			if($sth->rowCount() == 0){
				$sql = "INSERT INTO kolibri_felhasznalok(
				felhasznalonev,
           		vezeteknev,
            	keresztnev,
            	jelszo,
            	regisztracio_idopontja,
				utolso_belepes,
				letrehozta,
				csoport,
				aktiv) VALUES (
				:felhasznalonev,
            	:vezeteknev,
            	:keresztnev,
            	:jelszo,
            	:regisztracio_idopontja,
            	'0000-00-00 00:00:00',
				:letrehozta,
				:csoport,
				'1')";
					
				$sth = $dbh->prepare($sql);
				$sth->bindParam(':felhasznalonev', $userName);
				$sth->bindParam(':vezeteknev', $surName);
				$sth->bindParam(':keresztnev', $firstName);
				$sth->bindParam(':jelszo', md5($pass));
				$sth->bindParam(':regisztracio_idopontja', date('Y-m-d H:i:s'));
				//$sth->bindParam(':utolso_belepes', "0000-00-00 00:00:00");
				$sth->bindParam(':letrehozta', $_SESSION['felhasznalo']['felhasznalo_id']);
				$sth->bindParam(':csoport', $group);
				//$sth->bindParam(':aktiv', "1");
				$sth->execute();
				
				//user created
				if($sth->rowCount() > 0){
					$errorCode = 0;
					$message = 'Felhasználó létrehozva!';
					printResponse($errorCode,$message,null);
				}
				
			}
			//existing user, return with error
			else{
				$errorCode = 11;
				$message = 'Létező felhasználó!';
				printResponse($errorCode,$message,null);
			}

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

			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}
			
			$dbh -> exec("SET CHARACTER SET utf8");
			$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
			
			//check if user exists
			$sql = "DELETE FROM kolibri_felhasznalok WHERE felhasznalo_id = :userid";
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':userid', $userID);
			$sth->execute();
			
			//the user does not exist, can be inserted
			if($sth->rowCount() == 1){
				$errorCode = 0;
				$message = 'Felhasználó törölve!';
				printResponse($errorCode,$message,null);
			}
			else{
				$errorCode = 14;
				$message = 'A felhasználót nem sikerült törölni!';
				printResponse($errorCode,$message,null);
			}	
			


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
	
			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}
				
			$dbh -> exec("SET CHARACTER SET utf8");
			$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");

			//password update or details update?
			if(!empty($_POST['pass'])){
				$sql = "UPDATE kolibri_felhasznalok SET 
							vezeteknev = :vezeteknev,
							keresztnev = :keresztnev,
							jelszo = :jelszo,
							csoport = :csoport,
							aktiv = :statusz				 
            			WHERE felhasznalo_id = :userid";
			}
			else{
				$sql = "UPDATE kolibri_felhasznalok SET
							vezeteknev = :vezeteknev,
							keresztnev = :keresztnev,
							csoport = :csoport,
							aktiv = :statusz
            			WHERE felhasznalo_id = :userid";
			}
			
			
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':userid', $userID);
			$sth->bindParam(':vezeteknev', $_POST['surname']);
			$sth->bindParam(':keresztnev', $_POST['firstname']);
			$sth->bindParam(':csoport', $_POST['group']);
			$sth->bindParam(':statusz', $_POST['status']);
			
			if(!empty($_POST['pass'])){
				$sth->bindParam(':jelszo', md5($_POST['pass']));
				
			}
			
			$sth->execute();
				
			//the user does not exist, can be inserted
			if($sth->rowCount() == 1){
				$errorCode = 0;
				$message = 'Felhasználó módosítva!';
				printResponse($errorCode,$message,null);
			}
			else{
				$errorCode = 14;
				$message = 'A felhasználót nem sikerült módosítani!';
				printResponse($errorCode,$message,null);
			}
				
	
	
		}
	
	}	
	
	else{
		$errorCode = 1;
		$message = 'Ismeretlen feladat!';
		printResponse($errorCode,$message,null);
		
	}
	
	
	
}







?>
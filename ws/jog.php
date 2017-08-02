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
			
			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}
			
			$dbh -> exec("SET CHARACTER SET utf8");
			
			$sql = 'SELECT * from kolibri_jogcsoportok WHERE csoportnev = :csoport';
			
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':csoport', $name);
			$sth->execute();
			
			$csoportok = $sth->fetchAll(PDO::FETCH_ASSOC);
			
			if(count($csoportok) == 0){
				$sql = 'INSERT INTO kolibri_jogcsoportok(
				csoportnev,
            	hallgato_adatmodositas,
            	hallgato_nev_szoba,
            	hallgato_telefonszam,
            	hallgato_cim,
				hallgato_penzugy,
				igazolas,
				bekoltoztetes,
				lakolista,
				statisztika,
				admin) VALUES ("'.$name.'",';
				
				foreach($perm as $id=>$val){
					if($val){
						$sql.= '"1"';
					}
					else{
						$sql.= '"0"';
					}
					if ($id != 'uj_10') $sql.=',';
				}
				$sql.=")";
					
				$sth = $dbh->prepare($sql);
				$sth->execute();
					
				$message = "Csoport létrehozva.";
				printResponse(0,$message,null);
				
			}
			else{

				$errorCode = 2;
				$message = "A csoport már létezik!";
				printResponse($errorCode,$message,null);
			}
			

			
		}
		
	}
	//ACTION: deleteGroup
	else if($action == "deleteGroup"){
		$groupID = $_POST['groupID'];
		
		if($groupID != "1"){
			//check whether any user belongs to that group
			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}
				
			$dbh -> exec("SET CHARACTER SET utf8");
			
			$sql = 'SELECT * FROM kolibri_felhasznalok WHERE csoport ="'.$groupID.'"';
			$sth = $dbh->prepare($sql);
			$sth->execute();

			$felhasznalok = $sth->fetchAll(PDO::FETCH_ASSOC);
			
			if(count($felhasznalok) != 0){
				$errorCode = 4;
				$message = 'A csoport nem törölhető, mert tagjai vannak!';
				printResponse($errorCode,$message,null);
			}
			else{
				$sql = 'DELETE FROM kolibri_jogcsoportok WHERE id ='.$groupID;
				$sth = $dbh->prepare($sql);
				$sth->execute();
				
				if($sth->rowCount()>0){
					$errorCode = 0;
					$message = 'Csoport törölve!';
					printResponse($errorCode,$message,null);
					
				}
				else{
					$errorcode = 5;
					$message = 'Hiba a csoport törlésekor.';
					printResponse($errorCode,$message,null);
				}
			}
			
			
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
				
			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}
			$jog = array();
			foreach($perm as $pk=>$pv){
				if($pv){
					$jog[]="1";
				}
				else{
					$jog[]="0";
				}
			}
			
			$dbh -> exec("SET CHARACTER SET utf8");
				
				
			$sql = 'UPDATE kolibri_jogcsoportok SET
            hallgato_adatmodositas = "'.$jog[0].'",
            hallgato_nev_szoba = "'.$jog[1].'",
            hallgato_telefonszam = "'.$jog[2].'",
            hallgato_cim = "'.$jog[3].'",
			hallgato_penzugy = "'.$jog[4].'",
			igazolas = "'.$jog[5].'",
			bekoltoztetes = "'.$jog[6].'",
			lakolista = "'.$jog[7].'",
			statisztika = "'.$jog[8].'",
			admin = "'.$jog[9].'" WHERE id="'.$groupID.'"';

				
			$sth = $dbh->prepare($sql);
			$sth->execute();

			if($sth->rowCount()>0){
				$errorCode = 0;
				$message = 'Csoport módosítva!';
				printResponse($errorCode,$message,null);
					
			}
			else{
				$errorcode = 6;
				$message = 'Hiba a csoport módosításakor.';
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
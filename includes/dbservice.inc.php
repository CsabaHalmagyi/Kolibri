<?php
/**
 * This file is to provide service for various database interactions.
 * It always assume that $dbh is not null, ie the database connection was successful.
 * Always check $dbh before calling a function with that parameter.
 *
 */

require_once 'connection.inc.php';

DEFINE ('DB_HOST', 'localhost');
DEFINE ('DB_NAME', 'kolibri');
DEFINE ('DB_USER', 'kolibri');
DEFINE ('DB_PASS', 'kolibri');


/**
 *
 * Creates a DB connection
 */
function connectToDB(){

	try {
		$dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS );
	}catch (PDOException $e) {
		echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
		return false;
	}

	$dbh -> exec("SET CHARACTER SET utf8");
	$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");
	return $dbh;
}

/***************************************************************************
 * USER RELATED METHODS                                                    *
 ***************************************************************************/

/**
 *
 * Returns all users and groups in array("users"=>[],"groups"=[]) format
 */
function getUsersAndGroups($dbh){

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

	return array("users"=>$felhasznalok, "groups"=>$csoportok);
}

/**
 *
 * Checks if userName is available
 * returns true or false
 */
function isUserNameAvailable($dbh, $userName){

	$sql = "SELECT felhasznalonev FROM kolibri_felhasznalok WHERE felhasznalonev = :felhnev";
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':felhnev', $userName);
	$sth->execute();

	if($sth->rowCount() == 0) return true;
	return false;
}



/**
 *
 * Creates a new user
 */
function createNewUser($dbh, $userName, $pass, $firstName, $surName, $group){

	if(isUserNameAvailable($dbh, $userName)){

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
		$sth->bindParam(':letrehozta', $_SESSION['felhasznalo']['felhasznalo_id']);
		$sth->bindParam(':csoport', $group);
		$sth->execute();

		//user created
		if($sth->rowCount() > 0){
			$errorCode = 0;
			$message = 'Felhasználó létrehozva!';
			return array("errorCode"=>$errorCode,"message"=>$message);
		}
		else{
			$errorCode = 11;
			$message = 'Új felhasználó létrehozás sikertelen!';
			return array("errorCode"=>$errorCode,"message"=>$message);
		}

	}
	else{
		$errorCode = 11;
		$message = 'Létező felhasználó!';
		return array("errorCode"=>$errorCode,"message"=>$message);
	}
}

/**
 *
 * Deletes an existing user
 * @param unknown_type $dbh
 * @param unknown_type $userID
 */
function deleteUser($dbh, $userID){

	$sql = "DELETE FROM kolibri_felhasznalok WHERE felhasznalo_id = :userid";
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':userid', $userID);
	$sth->execute();

	if($sth->rowCount() > 0){
		$errorCode = 0;
		$message = 'Felhasználó törölve!';
		return array("errorCode"=>$errorCode,"message"=>$message);
	}
	else{
		$errorCode = 14;
		$message = 'A felhasználót nem sikerült törölni!';
		return array("errorCode"=>$errorCode,"message"=>$message);
	}
}


/**
 * Updates a user
 *
 * @param unknown_type $dbh
 * @param unknown_type $userID
 * @param unknown_type $firstName
 * @param unknown_type $surName
 * @param unknown_type $group
 * @param unknown_type $pass
 */
function updateUser($dbh, $userID, $firstName, $surName, $group, $status, $pass){

	//password update or details update?
	if(!empty($pass)){
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
	$sth->bindParam(':vezeteknev', $surName);
	$sth->bindParam(':keresztnev', $firstName);
	$sth->bindParam(':csoport', $group);
	$sth->bindParam(':statusz', $status);

	if(!empty($pass)){
		$sth->bindParam(':jelszo', md5($pass));
	}

	$sth->execute();

	if($sth->rowCount() == 1){
		$errorCode = 0;
		$message = 'Felhasználó módosítva!';
		return array("errorCode"=>$errorCode,"message"=>$message);
	}
	else{
		$errorCode = 14;
		$message = 'A felhasználót nem sikerült módosítani!';
		return array("errorCode"=>$errorCode,"message"=>$message);
	}
}

/***************************************************************************
 * GROUPS/PERMISSIONS RELATED METHODS                                                    *
 ***************************************************************************/

/**
 *
 * Creates a new group with given permissions
 * @param unknown_type $dbh
 * @param unknown_type $groupName
 * @param unknown_type $permissions
 * @return multitype:number string
 */
function createNewGroup($dbh, $groupName, $permissions){

	$sql = 'SELECT * from kolibri_jogcsoportok WHERE csoportnev = :csoport';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':csoport', $groupName);
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
				admin) VALUES (:csoport,';

		foreach($permissions as $id=>$val){
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
		$sth->bindParam(':csoport', $groupName);
		$sth->execute();
			
		$message = "Csoport létrehozva.";
		return array("errorCode"=>0,"message"=>$message);
	}
	else{

		$errorCode = 2;
		$message = "A csoport már létezik!";
		return array("errorCode"=>$errorCode,"message"=>$message);
	}
}


/**
 *
 * Deletes a group from the database
 * @param unknown_type $dbh
 * @param unknown_type $groupID
 */
function deleteGroup($dbh, $groupID){


	$sql = 'SELECT * FROM kolibri_felhasznalok WHERE csoport = :groupid';
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':groupid', $groupID);
	$sth->execute();

	$felhasznalok = $sth->fetchAll(PDO::FETCH_ASSOC);

	if(count($felhasznalok) != 0){
		$errorCode = 4;
		$message = 'A csoport nem törölhető, mert tagjai vannak!';
		return array("errorCode"=>$errorCode,"message"=>$message);
	}
	else{
		$sql = 'DELETE FROM kolibri_jogcsoportok WHERE id = :groupid';
		$sth = $dbh->prepare($sql);
		$sth->bindParam(':groupid', $groupID);
		$sth->execute();

		if($sth->rowCount()>0){
			$errorCode = 0;
			$message = 'Csoport törölve!';
			return array("errorCode"=>$errorCode,"message"=>$message);
		}
		else{
			$errorcode = 5;
			$message = 'Hiba a csoport törlésekor.';
			return array("errorCode"=>$errorCode,"message"=>$message);
		}
	}
}


/**
 *
 * Updates selected group with given permissions
 * @param unknown_type $dbh
 * @param unknown_type $groupID
 * @param unknown_type $perm
 */
function updateGroup($dbh, $groupID, $perm){

	$jog = array();
	foreach($perm as $pk=>$pv){
		if($pv){
			$jog[]="1";
		}
		else{
			$jog[]="0";
		}
	}


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
			admin = "'.$jog[9].'" WHERE id = :groupid';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':groupid', $groupID);
	$sth->execute();

	if($sth->rowCount()>0){
		$errorCode = 0;
		$message = 'Csoport módosítva!';
		return array("errorCode"=>$errorCode,"message"=>$message);

	}
	else{
		$errorcode = 6;
		$message = 'Hiba a csoport módosításakor.';
		return array("errorCode"=>$errorCode,"message"=>$message);
	}

}


?>
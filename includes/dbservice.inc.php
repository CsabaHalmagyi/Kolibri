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

?>
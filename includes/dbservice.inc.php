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

		$sql = "INSERT INTO `kolibri_felhasznalok`(
				`felhasznalo_id`,
				`felhasznalonev`,
           		`vezeteknev`,
            	`keresztnev`,
            	`jelszo`,
            	`regisztracio_idopontja`,
				`utolso_belepes`,
				`letrehozta`,
				`csoport`,
				`aktiv`) VALUES (NULL,
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
 * USER GROUPS/PERMISSIONS RELATED METHODS                                 *
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
		$sql = 'INSERT INTO `kolibri_jogcsoportok`(
				`csoportnev`,
            	`hallgato_adatmodositas`,
            	`hallgato_nev_szoba`,
            	`hallgato_telefonszam`,
            	`hallgato_cim`,
				`hallgato_penzugy`,
				`igazolas`,
				`bekoltoztetes`,
				`lakolista`,
				`statisztika`,
				`admin`) VALUES (:csoport,';

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



/***************************************************************************
 * DORM/FINANCE RELATED FUNCTIONS                                          *
 ***************************************************************************/

/**
 *
 * Returns the list of dorms and financialCodes
 * @param unknown_type $dbh
 */
function getDormsWithFinancialCodes($dbh){

	$sql = 'SELECT * FROM kolibri_kollegiumok';

	$sth = $dbh->prepare($sql);
	$sth->execute();
	$kollegiumok = $sth->fetchAll(PDO::FETCH_ASSOC);

	$sql = 'SELECT * FROM kolibri_penzugyi_kodok';
	$sth = $dbh->prepare($sql);
	$sth->execute();
	$penzugyikodok = $sth->fetchAll(PDO::FETCH_ASSOC);

	if(count($kollegiumok)>0) {
		return array("kollegiumok"=>$kollegiumok, "penzugyikodok"=>$penzugyikodok);
	}
	else{
		return array("kollegiumok"=>null, "penzugyikodok"=>null);
	}
}


function getFinancialCodesByDormId($dbh, $kollegium){

	$sql = "SELECT * FROM kolibri_penzugyi_kodok
			WHERE kollegium_id = :kollid";

	$sth_penzugy = $dbh->prepare ( $sql );
	$sth_penzugy->bindParam ( ':kollid', $kollegium );
	$sth_penzugy->execute ();

	return $sth_penzugy->fetchAll ( PDO::FETCH_ASSOC );
}

/**
 *
 * Returns the students of a room (using current semester)
 * @param unknown_type $dbh
 */
function getRoomDetails($dbh, $koliID, $szobaID){

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

	return $sth->fetchAll(PDO::FETCH_ASSOC);
}

function getAllRoomsInDorm($dbh, $kollID){

	$sql = 'SELECT kolibri_szoba_definiciok.szoba_def_id, kolibri_szoba_definiciok.szoba_szam, kolibri_szoba_definiciok.max_ferohely,
				kolibri_szoba_definiciok.szabad_ferohely, kolibri_kollegiumok.kollegium_rovid_nev
				FROM kolibri_szoba_definiciok
				INNER JOIN kolibri_kollegiumok
				ON kolibri_szoba_definiciok.kollegium_id = kolibri_kollegiumok.kollegium_id
				WHERE kolibri_szoba_definiciok.kollegium_id = :koliid';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':koliid', $kollID);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_ASSOC);
}

function getDorms($dbh){

	$sql = 'SELECT * FROM kolibri_kollegiumok';

	$sth = $dbh->prepare($sql);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_ASSOC);
}


/**
 *
 * Igaz, ha minden szoba_reszlet rendelkezik kikoltozes datummal
 * @param unknown_type $dbh
 */
function isAllBuildingEmpty($dbh){

	$sql = 'SELECT * FROM kolibri_szoba_reszletek
	WHERE kolibri_szoba_reszletek.kikoltozes_datuma = "0000-00-00 00:00:00"';

	$sth = $dbh->prepare($sql);
	$sth->execute();

	if($sth->rowCount() == 0) return true;
	return false;

}

function clearEnrollmentList($dbh){

	$sql = 'TRUNCATE TABLE `kolibri_felvettek`';
	$sth = $dbh->prepare($sql);
	$sth->execute();

}


/***************************************************************************
 * CARD RELATED FUNCTIONS                                                  *
 ***************************************************************************/

/**
 *
 * Checks if a given card is currently available
 * @param unknown_type $dbh
 * @param unknown_type $card
 */
function isCardAvailable($dbh, $card){

	$sql = 'SELECT * FROM kolibri_belepokartyak
					WHERE kartya_szam = :kartya
					AND leadas_datuma = "0000-00-00 00:00:00"';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':kartya', $card);
	$sth->execute();

	$hasznalo = $sth->fetchAll(PDO::FETCH_ASSOC);

	if(count($hasznalo)>0){
		return false;
	}

	return true;
}


function getActiveCardsWithStudentId($dbh){

	$sql_k = 'SELECT hallgato_id, kartya_szam FROM kolibri_belepokartyak
			WHERE leadas_datuma = "0000-00-00 00:00:00"';

	$sth_k = $dbh->prepare($sql_k);
	$sth_k->execute();
	return $sth_k->fetchAll(PDO::FETCH_ASSOC);

}



/**
 *
 * Assigns a card to a student
 * @param unknown_type $dbh
 * @param unknown_type $student
 * @param unknown_type $card
 */
function assignCardToStudent($dbh, $student, $card){

	if(isCardAvailable($dbh, $card)){

		$sql = 'INSERT INTO `kolibri_belepokartyak` (`tanev_id`, `hallgato_id`, `kartya_szam`, `felvetel_datuma`)
						VALUES(:tanev, :hallgato, :kartya, :felvetel)';
			
		$sth = $dbh->prepare($sql);
		$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
		$sth->bindParam(':hallgato', $student);
		$sth->bindParam(':kartya', $card);
		$sth->bindParam(':felvetel', date('Y-m-d H:i:s'));
		$sth->execute();

		$errorcode = 0;
		$message = 'Kártya hallgatóhoz rendelve.';
		return array("errorCode"=>$errorCode,"message"=>$message);

	}
	else{
		$errorcode = 67;
		$message = 'A kártya jelenleg használatban van.';
		return array("errorCode"=>$errorCode,"message"=>$message);
	}

}


/**
 *
 * Revokes a card from a student
 * @param unknown_type $dbh
 * @param unknown_type $cardEntryID
 */
function revokeCardFromStudent($dbh, $cardEntryID){

	$sql = 'UPDATE kolibri_belepokartyak SET leadas_datuma = :leadas
					WHERE kartya_bejegyzes_id = :bejegyzes';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':leadas', date('Y-m-d H:i:s'));
	$sth->bindParam(':bejegyzes', $cardEntryID);
	$sth->execute();

	$errorcode = 0;
	$message = 'Kártya hallgatótól visszavonva.';
	return array("errorCode"=>$errorCode,"message"=>$message);
}



/***************************************************************************
 * STUDENT RELATED FUNCTIONS                                             *
 ***************************************************************************/

/**
 *
 * Returns true if the neptun code exists in the database
 * @param unknown_type $dbh
 * @param unknown_type $neptun
 */
function isExistinStudent($dbh, $neptun){

	$sql = 'SELECT * FROM kolibri_hallgatok WHERE hallgato_neptun_kod = :neptunkod';
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':neptunkod', $neptun);
	$sth->execute();

	$hallgato = $sth->fetchAll(PDO::FETCH_ASSOC);
	//if the student exists already, update her
	if(count($hallgato) == 1) return true;
	return false;

}


function getStudentByNeptun($dbh, $neptun){

	$sql = 'SELECT * FROM kolibri_hallgatok WHERE hallgato_neptun_kod = :neptunkod';
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':neptunkod', $neptun);
	$sth->execute();

	$hallgato = $sth->fetchAll(PDO::FETCH_ASSOC);
	//if the student exists already, update her
	if(count($hallgato) == 1) return $hallgato[0];
	return null;

}


function getStudentByID($dbh, $hallgato){

	$sql = 'SELECT * FROM kolibri_hallgatok WHERE hallgato_id = :hallgato';
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':hallgato', $hallgato);
	$sth->execute();

	return $sth->fetch(PDO::FETCH_ASSOC);
}


function updateStudentByID($dbh, $nev, $email, $telefon, $lakcim,
$allampolgarsag, $kepzesiforma, $penzugyikod, $hallgID){

	$sql = "UPDATE kolibri_hallgatok SET
							hallgato_neve = :nev,
							hallgato_email = :email,
							hallgato_telefon = :telefon,
							hallgato_lakcim = :lakcim,
							hallgato_allampolgarsag = :allampolgarsag,
							hallgato_kepzesi_forma = :kepzesiforma,
							hallgato_penzugyi_kod = :penzugyikod
            			WHERE hallgato_id = :hid";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':nev', $nev);
	$sth->bindParam(':email', $email);
	$sth->bindParam(':telefon', $telefon);
	$sth->bindParam(':lakcim', $lakcim);
	$sth->bindParam(':allampolgarsag', $allampolgarsag);
	$sth->bindParam(':kepzesiforma', $kepzesiforma);
	$sth->bindParam(':penzugyikod', $penzugyikod);
	$sth->bindParam(':hid', $hallgID);
	$sth->execute();
}

/**
 *
 * Returns the semester entry for a student
 *
 *  felvettek_id 	tanev_id 	kollegium_id 	hallgato_id 	szobaba_beosztva
 *
 * @param unknown_type $dbh
 * @param unknown_type $studentID
 */
function getEnrollDetails($dbh, $studentID){

	$sql2 = "SELECT * from kolibri_felvettek
						WHERE tanev_id = :tanev
						AND hallgato_id = :hallgato";

	$sth2 = $dbh->prepare($sql2);
	$sth2->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth2->bindParam(':hallgato', $studentID);
	$sth2->execute();

	$felvett =  $sth2->fetch(PDO::FETCH_ASSOC);

	return $felvett;
}


function searchForStudentOnEnrollmentListByName($dbh, $name){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_felvettek
					INNER JOIN kolibri_hallgatok
					ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
					WHERE kolibri_hallgatok.hallgato_neve LIKE :nev
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 15';

	$sth = $dbh->prepare($sql);
	$keyword = "%$name%";
	$sth->bindParam(':nev', $keyword);
	$sth->execute();
	return $sth->fetchAll(PDO::FETCH_ASSOC);

}




function searchForStudentOnEnrollmentListByNeptun($dbh, $neptun){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_felvettek
					INNER JOIN kolibri_hallgatok
					ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
					WHERE kolibri_hallgatok.hallgato_neptun_kod LIKE :neptun
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 15';

	$sth = $dbh->prepare($sql);
	$keyword = "%$neptun%";
	$sth->bindParam(':neptun', $keyword);
	$sth->execute();
	return $sth->fetchAll(PDO::FETCH_ASSOC);
}


function searchForStudentOnEnrollmentListByCard($dbh, $kartya){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve, kolibri_belepokartyak.kartya_szam
					FROM kolibri_felvettek
					INNER JOIN kolibri_hallgatok
					ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
					INNER JOIN kolibri_belepokartyak
					ON kolibri_belepokartyak.hallgato_id = kolibri_felvettek.hallgato_id		
					WHERE kolibri_belepokartyak.kartya_szam LIKE :kartya
					AND kolibri_belepokartyak.leadas_datuma = "0000-00-00 00:00:00"	
					ORDER BY  kolibri_belepokartyak.kartya_szam
					LIMIT 15';

	$sth = $dbh->prepare($sql);
	$keyword = "$kartya%";
	$sth->bindParam(':kartya', $keyword);
	$sth->execute();
	return $sth->fetchAll(PDO::FETCH_ASSOC);
}



function searchForStudentByName($dbh, $name){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_hallgatok
					WHERE kolibri_hallgatok.hallgato_neve LIKE :nev
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 15';

	$sth = $dbh->prepare($sql);
	$keyword = "%$name%";
	$sth->bindParam(':nev', $keyword);
	$sth->execute();
	return $sth->fetchAll(PDO::FETCH_ASSOC);
}


function searchForStudentByNeptun($dbh, $neptun){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_hallgatok
					WHERE kolibri_hallgatok.hallgato_neptun_kod LIKE :neptun
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 15';

	$sth = $dbh->prepare($sql);
	$keyword = "%$neptun%";
	$sth->bindParam(':neptun', $keyword);
	$sth->execute();
	return $sth->fetchAll(PDO::FETCH_ASSOC);
}




function searchForStudentByCard($dbh, $card){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve, kolibri_belepokartyak.kartya_szam
					FROM kolibri_hallgatok
					INNER JOIN kolibri_belepokartyak
					ON kolibri_belepokartyak.hallgato_id = kolibri_hallgatok.hallgato_id
					WHERE kolibri_belepokartyak.kartya_szam LIKE :kartya
					AND kolibri_belepokartyak.leadas_datuma = "0000-00-00 00:00:00"
					ORDER BY  kolibri_belepokartyak.kartya_szam
					LIMIT 15';

	$sth = $dbh->prepare($sql);
	$keyword = "$card%";
	$sth->bindParam(':kartya', $keyword);
	$sth->execute();
	return $sth->fetchAll(PDO::FETCH_ASSOC);
}


/**
 *
 * Creates a student and returns the hallgato_id or false, if the insert was not successful.
 *
 * @param unknown_type $dbh
 * @param unknown_type $nk
 * @param unknown_type $nev
 * @param unknown_type $email
 * @param unknown_type $telefon
 * @param unknown_type $lakcim
 * @param unknown_type $allampolgarsag
 * @param unknown_type $kepzesiforma
 * @param unknown_type $penzugyikod
 * @param unknown_type $hallgatoID
 */
function createNewStudent($dbh, $nk, $nev, $email, $telefon, $lakcim,
$allampolgarsag, $kepzesiforma, $penzugyikod){

	$sql = "INSERT INTO `kolibri_hallgatok` (
							`hallgato_neptun_kod`,
							`hallgato_neve`,
							`hallgato_email`, 
							`hallgato_telefon`, 
							`hallgato_lakcim`, 
							`hallgato_allampolgarsag`, 
							`hallgato_kepzesi_forma`, 
							`hallgato_penzugyi_kod`) 
						VALUES(:nk,:nev,:email,:telefon, :lakcim, :allampolgarsag, :kepzesiforma,:penzugyikod)";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':nk', $nk);
	$sth->bindParam(':nev', $nev);
	$sth->bindParam(':email', $email);
	$sth->bindParam(':telefon', $telefon);
	$sth->bindParam(':lakcim', $lakcim);
	$sth->bindParam(':allampolgarsag', $allampolgarsag);
	$sth->bindParam(':kepzesiforma', $kepzesiforma);
	$sth->bindParam(':penzugyikod', $penzugyikod);
	//$sth->bindParam(':hid', $hid);
	$sth->execute();

	if($sth) return $dbh->lastInsertId();
	return false;
}



function updateStudent($dbh, $nk, $nev, $email, $telefon, $lakcim,
$allampolgarsag, $kepzesiforma, $penzugyikod, $hallgatoID){

	$sql = "UPDATE kolibri_hallgatok SET
							hallgato_neptun_kod = :nk,
							hallgato_neve = :nev,
							hallgato_email = :email,
							hallgato_telefon = :telefon,
							hallgato_lakcim = :lakcim,
							hallgato_allampolgarsag = :allampolgarsag,
							hallgato_kepzesi_forma = :kepzesiforma,
							hallgato_penzugyi_kod = :penzugyikod
            			WHERE hallgato_id = :hid";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':nk', $nk);
	$sth->bindParam(':nev', $nev);
	$sth->bindParam(':email', $email);
	$sth->bindParam(':telefon', $telefon);
	$sth->bindParam(':lakcim', $lakcim);
	$sth->bindParam(':allampolgarsag', $allampolgarsag);
	$sth->bindParam(':kepzesiforma', $kepzesiforma);
	$sth->bindParam(':penzugyikod', $penzugyikod);
	$sth->bindParam(':hid', $hallgatoID);

	$sth->execute();
}



/**
 *
 * Adds a student to the enrollment list (felvettek listája adott kollégiumba aktuális tanévre)
 * @param unknown_type $dbh
 * @param unknown_type $kollID
 * @param unknown_type $hallgID
 */
function addStudentToEnrollmentList($dbh, $kollID, $hallgID){

	$sql = "INSERT INTO `kolibri_felvettek` (
							`tanev_id`,
							`kollegium_id`,
							`hallgato_id`,
							`szobaba_beosztva`)
							VALUES(:tanev,:kollegium,:hallgato, '0')";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->bindParam(':kollegium',  $kollID);
	$sth->bindParam(':hallgato', $hallgID);
	$sth->execute();
	
}


/**
 *
 * Removes a student from the enrollment list (felvettek listája adott kollégiumba aktuális tanévre)
 * @param unknown_type $dbh
 * @param unknown_type $hallgID
 */
function removeStudentFromEnrollmentList($dbh, $hallgID){

	$sql = 'DELETE FROM kolibri_felvettek
				WHERE hallgato_id = :hallgato';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':hallgato', $hallgID);
	$sth->execute();
}



function getStudentBeKikoltoztetLista($dbh, $name, $kollID){

	$sql = 'SELECT *, kh.hallgato_id as hallg_azon
 FROM kolibri_felvettek kf
    inner join kolibri_hallgatok kh on kf.hallgato_id = kh.hallgato_id
    left join kolibri_szoba_reszletek ksr on kh.hallgato_id = ksr.hallgato_id and kf.tanev_id = ksr.tanev_id and not exists(
          select 1
          from kolibri_szoba_reszletek ksr2
          where ksr.hallgato_id = ksr2.hallgato_id
           and ksr.tanev_id = ksr2.tanev_id
             and ksr2.reszletek_id > ksr.reszletek_id
       )
    join kolibri_szoba_definiciok ksd on ksr.szoba_id = ksd.szoba_def_id
 WHERE kh.hallgato_neve like :nev
 AND kf.kollegium_id = :kollid
 LIMIT 10';

	$sth = $dbh->prepare($sql);
	$keyword = $name;
	$keyword = "%$keyword%";
	$sth->bindParam(':nev', $keyword);
	$sth->bindParam(':kollid', $kollID);

	$sth->execute();
	//file_put_contents("koll.log", $sth->debugDumpParams());
	return $sth->fetchAll(PDO::FETCH_ASSOC);
}



function getStudentBeKikoltoztetAdat($dbh, $hallgID, $kollID){

	$sql = 'SELECT *
					FROM kolibri_felvettek kf
    				inner join kolibri_hallgatok kh on kf.hallgato_id = kh.hallgato_id
    				left join kolibri_szoba_reszletek ksr on kh.hallgato_id = ksr.hallgato_id and not exists(
            		select 1
            		from kolibri_szoba_reszletek ksr2
            		where ksr.hallgato_id = ksr2.hallgato_id
                	and ksr2.reszletek_id > ksr.reszletek_id
        			)
    				left join kolibri_szoba_definiciok ksd on ksr.szoba_id = ksd.szoba_def_id
					WHERE kh.hallgato_id = :hallgid
					AND kf.kollegium_id = :kollid';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':hallgid', $hallgID);
	$sth->bindParam(':kollid', $kollID);
	$sth->execute();
	//file_put_contents("koll.log", $sth->debugDumpParams());
	return $sth->fetch(PDO::FETCH_ASSOC);
}


function getStudentRoomHistory($dbh, $hallgid){

	$sql = 'SELECT *
				FROM kolibri_szoba_reszletek szr
				INNER JOIN kolibri_szoba_definiciok szd
				ON szr.szoba_id = szd.szoba_def_id
				INNER JOIN kolibri_tanevek te
				ON szr.tanev_id = te.tanev_id
				INNER JOIN kolibri_kollegiumok kk
				ON szr.kollegium_id = kk.kollegium_id
				WHERE szr.hallgato_id = :hallgid
				ORDER BY szr.beosztas_datuma DESC';

	$sth_adatok = $dbh->prepare($sql);
	$sth_adatok->bindParam(':hallgid', $hallgid);
	$sth_adatok->execute();

	return $sth_adatok->fetchAll(PDO::FETCH_ASSOC);
}


function getStudentCardHistory($dbh, $hallgid){

	$sql = 'SELECT * FROM kolibri_belepokartyak
			WHERE hallgato_id = :hallgid
			ORDER BY felvetel_datuma DESC';

	$sth_kartyak = $dbh->prepare($sql);
	$sth_kartyak->bindParam(':hallgid', $hallgid);
	$sth_kartyak->execute();

	return $sth_kartyak->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentActiveCards($dbh, $hallgid){

	$sql = 'SELECT *
        		FROM kolibri_belepokartyak kb
				WHERE kb.hallgato_id = :hallgid
    			AND kb.leadas_datuma = "0000-00-00 00:00:00"
				ORDER BY kb.felvetel_datuma';

	$sth_kartyak = $dbh->prepare($sql);
	$sth_kartyak->bindParam(':hallgid', $hallgid);
	$sth_kartyak->execute();

	return $sth_kartyak->fetchAll(PDO::FETCH_ASSOC);
}


function getRoomDefinition($dbh, $roomID){

	$sql = 'SELECT * FROM kolibri_szoba_definiciok WHERE szoba_def_id = :szoba';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':szoba', $roomID);
	$sth->execute();
	return $sth->fetch(PDO::FETCH_ASSOC);
}


/**
 *
 * Hallgato félév bejegyzései
 */
function getStudentSemesterEntries($dbh, $studentID, $kollID){

	$sql = 'SELECT reszletek_id,bekoltozes_datuma FROM kolibri_szoba_reszletek
						WHERE tanev_id = :tanev
						AND kollegium_id = :kollegium
						AND hallgato_id = :hallgato
						ORDER BY reszletek_id DESC';

	$sth2 = $dbh->prepare($sql);

	$sth2->bindParam(':hallgato', $studentID);
	$sth2->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth2->bindParam(':kollegium', $kollID);
	$sth2->execute();

	return $sth2->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentSemesterEntriesToRoom($dbh, $studentID, $kollID, $roomID){

	$sql = 'SELECT reszletek_id, bekoltozes_datuma FROM kolibri_szoba_reszletek
						WHERE tanev_id = :tanev
						AND kollegium_id = :kollegium
						AND hallgato_id = :hallgato
						AND szoba_id = :szoba 
						ORDER BY reszletek_id DESC
						LIMIT 1';

	$sth2 = $dbh->prepare($sql);

	$sth2->bindParam(':hallgato', $studentID);
	$sth2->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth2->bindParam(':kollegium', $kollID);
	$sth2->bindParam(':szoba', $roomID);
	$sth2->execute();

	return $sth2->fetch(PDO::FETCH_ASSOC);
}




function isStudentInRoom($dbh, $kollegium, $szoba, $hallgato){

	$sql = 'SELECT reszletek_id, bekoltozes_datuma FROM kolibri_szoba_reszletek
						WHERE tanev_id = :tanev
						AND kollegium_id = :kollegium
						AND hallgato_id = :hallgato
						AND szoba_id = :szoba 
						ORDER BY reszletek_id DESC';

	$sth2 = $dbh->prepare($sql);

	$sth2->bindParam(':hallgato', $hallgato);
	$sth2->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth2->bindParam(':kollegium', $kollegium);
	$sth2->bindParam(':szoba', $roomID);
	$sth2->execute();

	$result = $sth2->fetchAll(PDO::FETCH_ASSOC);

	if(count($result)>0) return true;
	return false;

}

function getAllStudentsFromEnrollmentList($dbh, $kollID){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id,
				kolibri_hallgatok.hallgato_neptun_kod, 
				kolibri_hallgatok.hallgato_neve,
				kolibri_szoba_definiciok.szoba_szam,
				kolibri_szoba_reszletek.bekoltozes_datuma, 
				kolibri_kollegiumok.kollegium_rovid_nev
			FROM kolibri_szoba_reszletek
			INNER JOIN 
			kolibri_hallgatok
			ON kolibri_szoba_reszletek.hallgato_id = kolibri_hallgatok.hallgato_id
			INNER JOIN
			kolibri_szoba_definiciok
			ON kolibri_szoba_reszletek.szoba_id = kolibri_szoba_definiciok.szoba_def_id
			INNER JOIN kolibri_kollegiumok
			ON kolibri_szoba_reszletek.kollegium_id = kolibri_kollegiumok.kollegium_id		
			WHERE kolibri_szoba_reszletek.kikoltozes_datuma = "0000-00-00 00:00:00"
			AND kolibri_szoba_reszletek.kollegium_id = :kollid
			AND kolibri_szoba_reszletek.tanev_id = :akttanev
			ORDER BY kolibri_szoba_definiciok.szoba_szam';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':kollid', $kollID);
	$sth->bindParam(':akttanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_ASSOC);

}


function getRoomDetailsByID($dbh, $entryID){

	$sql = 'SELECT bekoltozes_datuma, kikoltozes_datuma
				FROM kolibri_szoba_reszletek
				WHERE reszletek_id = :reszletek';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':reszletek', $entryID);
	$sth->execute();

	return $sth->fetch(PDO::FETCH_ASSOC);
}


/**
 *
 * Legal relationship = hallgató létező koll. jogviszonnyal
 * Enter description here ...
 */
function assignStudentWithLegalRelationshipToRoom($dbh, $studentID, $kollID, $roomID){

	$sql = "INSERT INTO `kolibri_szoba_reszletek` (
							`hallgato_id`,
							`tanev_id`,
							`kollegium_id`,
							`szoba_id`,
							`beosztas_datuma`,
							`bekoltozes_datuma`)
							VALUES(:hallgato,:tanev,:kollegium, :szoba, :beosztas_datuma,:bekoltozes_datuma)";

	$sth = $dbh->prepare($sql);

	$sth->bindParam(':hallgato', $studentID);
	$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->bindParam(':kollegium', $kollID);
	$sth->bindParam(':szoba', $roomID);
	$sth->bindParam(':beosztas_datuma', date('Y-m-d H:i:s'));
	$sth->bindParam(':bekoltozes_datuma', date('Y-m-d H:i:s'));
	$sth->execute();
}



/**
 *
 * Szobabeosztás (assign student without legal relationship to room )
 *
 * @param unknown_type $dbh
 * @param unknown_type $studentID
 * @param unknown_type $kollID
 * @param unknown_type $roomID
 */
function assignStudentToRoom($dbh, $studentID, $kollID, $roomID){

	$sql = "INSERT INTO `kolibri_szoba_reszletek` (
							`hallgato_id`,
							`tanev_id`,
							`kollegium_id`,
							`szoba_id`,
							`beosztas_datuma`)
							VALUES(:hallgato,:tanev,:kollegium, :szoba, :beosztas_datuma)";

	$sth = $dbh->prepare($sql);

	$sth->bindParam(':hallgato', $studentID);
	$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->bindParam(':kollegium', $kollID);
	$sth->bindParam(':szoba', $roomID);
	$sth->bindParam(':beosztas_datuma', date('Y-m-d H:i:s'));
	$sth->execute();
}

function removeStudentFromRoom($dbh, $studentID, $kollID, $roomID ){

	$sql = "DELETE FROM kolibri_szoba_reszletek
				WHERE hallgato_id = :hallgato
				AND tanev_id = :tanev
				AND kollegium_id = :kollegium
				AND szoba_id = :szoba";

	$sth = $dbh->prepare($sql);

	$sth->bindParam(':hallgato', $studentID);
	$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->bindParam(':kollegium', $kollID);
	$sth->bindParam(':szoba', $roomID);
	$sth->execute();

}


function removeStudentWithLegalRelationshipFromRoom($dbh, $reszletek_id){

	$sql = 'UPDATE kolibri_szoba_reszletek SET kikoltozes_datuma = :kikoltozes WHERE reszletek_id = :reszletek';
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':kikoltozes', date('Y-m-d H:i:s'));
	$sth->bindParam(':reszletek', $reszletek_id);
	$sth->execute();
}



/**
 * Hallgató felvettek lista update
 *
 */
function updateRoomStatusOnStudentSemesterList($dbh, $studentID, $status){

	$sqlu = "UPDATE kolibri_felvettek SET szobaba_beosztva = :status WHERE hallgato_id = :hallgato";
	$sth2 = $dbh->prepare($sqlu);
	//$beosztva = 1;
	$sth2->bindParam(':status', $status);
	$sth2->bindParam(':hallgato', $studentID, PDO::PARAM_INT);
	$sth2->execute();
}



function updateRoomDefFreeSpace($dbh, $szoba, $ujszabadhely){

	//update szabadhely
	$sqlu2 = "UPDATE kolibri_szoba_definiciok SET szabad_ferohely = :szabadhely WHERE szoba_def_id = :szoba";

	$sth3 = $dbh->prepare($sqlu2);
	$sth3->bindParam(':szabadhely', $ujszabadhely, PDO::PARAM_INT);
	$sth3->bindParam(':szoba', $szoba, PDO::PARAM_INT);
	$sth3->execute();
}

/**
 * Jogviszony létrehozás szobába beosztott hallgatóhoz
 *
 */
function createLegalRelationshipByID($dbh, $reszletek){

	$sql = 'UPDATE kolibri_szoba_reszletek SET bekoltozes_datuma = :bekoltozes WHERE reszletek_id = :reszletek';
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':reszletek', $reszletek);
	$sth->bindParam(':bekoltozes', date('Y-m-d H:i:s'));
	$sth->execute();
}

/**
 *
 * Hallgató szoba státusza
 * 0 = nincs szobába beosztva
 * 1 = be van osztva szobába
 * @param unknown_type $dbh
 * @param unknown_type $hallgato
 */
function getStudentRoomStatus($dbh, $hallgato){

	$sql = 'SELECT szobaba_beosztva
				FROM kolibri_felvettek
				WHERE hallgato_id = :hallgato';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':hallgato', $hallgato);
	$sth->execute();

	return $sth->fetch(PDO::FETCH_ASSOC);
}


function getStudentEnrollmentListData($dbh, $student){

	$sql = "SELECT kolibri_kollegiumok.kollegium_id, kolibri_kollegiumok.kollegium_nev
			FROM kolibri_felvettek
			INNER JOIN kolibri_kollegiumok
			ON kolibri_kollegiumok.kollegium_id = kolibri_felvettek.kollegium_id
			WHERE kolibri_felvettek.tanev_id = :tanev
			AND kolibri_felvettek.hallgato_id = :hallgato";

	$sth_koli = $dbh->prepare ( $sql );
	$sth_koli->bindParam ( ':tanev', $_SESSION ['beallitasok'] ['aktualis_tanev_id'] );
	$sth_koli->bindParam ( ':hallgato', $student );
	$sth_koli->execute ();

	return $sth_koli->fetch ( PDO::FETCH_ASSOC );
}




/***************************************************************************
 * REPORT CREATING FUNCTIONS                                               *
 ***************************************************************************/


function customReport($dbh, $tipus_sql, $osszeg, $koli){

	$sql = 'SELECT
			kolibri_hallgatok.*,'
			.$tipus_sql.' AS penzugyikod, '
			.$osszeg.' AS osszeg,
			kk.kollegium_rovid_nev AS kolinev
			FROM kolibri_felvettek
			INNER JOIN kolibri_hallgatok
			ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
			LEFT JOIN kolibri_penzugyi_kodok pk
			ON kolibri_hallgatok.hallgato_penzugyi_kod = pk.pk_id
			INNER JOIN kolibri_kollegiumok kk
			ON kk.kollegium_id = kolibri_felvettek.kollegium_id
			WHERE kolibri_felvettek.tanev_id = :akttanev
			AND kolibri_felvettek.kollegium_id = :kollid';

			$sth = $dbh->prepare($sql);
			$sth->bindParam(':kollid', $koli);
			$sth->bindParam(':akttanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
			$sth->execute();

			return $sth->fetchAll(PDO::FETCH_ASSOC);
}


function getReportUsingNeptun($dbh, $tipus_sql, $osszeg, $nk){

	$sql = 'SELECT
			kolibri_hallgatok.*,'
			.$tipus_sql.' AS penzugyikod, '
			.$osszeg.' AS osszeg,
			kk.kollegium_rovid_nev AS kolinev
			FROM kolibri_felvettek
			INNER JOIN kolibri_hallgatok
			ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
			INNER JOIN kolibri_penzugyi_kodok pk
			ON kolibri_hallgatok.hallgato_penzugyi_kod = pk.pk_id
			INNER JOIN kolibri_kollegiumok kk
			ON kk.kollegium_id = kolibri_felvettek.kollegium_id
			WHERE kolibri_felvettek.tanev_id = :akttanev
			AND kolibri_hallgatok.hallgato_neptun_kod = :neptun';

			$sth = $dbh->prepare($sql);
			$sth->bindParam(':akttanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
			$sth->bindParam(':neptun', $nk);
			$sth->execute();
			return $sth->fetch(PDO::FETCH_ASSOC);

}



function getStudentDetailsByDormReport($dbh, $koli){

	$sql = 'SELECT kolibri_hallgatok.hallgato_neptun_kod,
				kolibri_szoba_definiciok.szoba_szam,
				kolibri_szoba_reszletek.bekoltozes_datuma, 
				kolibri_kollegiumok.kollegium_rovid_nev
			FROM kolibri_szoba_reszletek
			INNER JOIN 
			kolibri_hallgatok
			ON kolibri_szoba_reszletek.hallgato_id = kolibri_hallgatok.hallgato_id
			INNER JOIN
			kolibri_szoba_definiciok
			ON kolibri_szoba_reszletek.szoba_id = kolibri_szoba_definiciok.szoba_def_id
			INNER JOIN kolibri_kollegiumok
			ON kolibri_szoba_reszletek.kollegium_id = kolibri_kollegiumok.kollegium_id		
			WHERE kolibri_szoba_reszletek.kikoltozes_datuma = "0000-00-00 00:00:00"
			AND kolibri_szoba_reszletek.kollegium_id = :kollid
			AND kolibri_szoba_reszletek.tanev_id = :akttanev
			ORDER BY kolibri_szoba_definiciok.szoba_szam';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':kollid', $koli);
	$sth->bindParam(':akttanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_ASSOC);

}



/***************************************************************************
 * ADDITIONAL HELPER FUNCTIONS                                             *
 ***************************************************************************/


/**
 *
 * Returns max 10 students without room (using current semester)
 * @param unknown_type $dbh
 * @param unknown_type $koliID
 */
function getStudentsWithoutRoom($dbh, $koliID){

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

	$szobatlan_hallgatok = $sth->fetchAll(PDO::FETCH_ASSOC);

	return $szobatlan_hallgatok;
}


function getStudentsWithRoom($dbh, $koliID){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_felvettek
					INNER JOIN kolibri_hallgatok
					ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
					WHERE kolibri_felvettek.tanev_id = :tanevid
					AND kolibri_felvettek.kollegium_id = :kollid
					AND kolibri_felvettek.szobaba_beosztva = "1"
					ORDER BY  kolibri_hallgatok.hallgato_neve';

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':tanevid', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->bindParam(':kollid', $koliID);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentsWithoutRoomNameContains($dbh, $koliID, $name){

	$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_felvettek
					INNER JOIN kolibri_hallgatok
					ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
					WHERE kolibri_felvettek.tanev_id = :tanevid
					AND kolibri_felvettek.kollegium_id = :kollid
					AND kolibri_felvettek.szobaba_beosztva = "0"
					AND kolibri_hallgatok.hallgato_neve LIKE :nev
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 10';

	$sth = $dbh->prepare($sql);
	$keyword = $_POST['hallgato'];
	$sth->bindParam(':tanevid', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->bindParam(':kollid', $koliID);
	$keyword = "%$name%";
	$sth->bindParam(':nev', $keyword);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_ASSOC);
}


function getSettings($dbh){

	$sql = 'SELECT * FROM kolibri_beallitasok';

	$sth = $dbh->prepare ( $sql );
	$sth->execute ();
	return $sth->fetch ( PDO::FETCH_ASSOC );
}


function getSemesters($dbh){

	$sql = "SELECT * FROM kolibri_tanevek";
	$sth = $dbh->prepare ( $sql );
	$sth->execute ();
	return $sth->fetchAll ( PDO::FETCH_ASSOC );
}

function getSemesterByID($dbh, $id){

	$sql = "SELECT * FROM kolibri_tanevek
	WHERE tanev_id = :id";
	$sth = $dbh->prepare ( $sql );
		$sth->bindParam(':id', $id);
	$sth->execute ();
	return $sth->fetch ( PDO::FETCH_ASSOC );
}

function getUser($dbh, $user){

	$sql = "SELECT * FROM kolibri_felhasznalok WHERE felhasznalonev = :felhnev";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':felhnev', $user);
	$sth->execute();

	return $sth->fetch(PDO::FETCH_ASSOC);
}


function updateUserLastLogin($dbh, $userID){

	$sql = "UPDATE kolibri_felhasznalok SET utolso_belepes = :lastLogin
                        WHERE felhasznalo_id = :userID";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':lastLogin', $lastLogin);
	$sth->bindParam(':userID', $userID);

	$sth->execute();
}

function getGroupPermissions($dbh, $group){

	$sql = "SELECT * FROM kolibri_jogcsoportok WHERE id = :group";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':group', $group);
	$sth->execute();

	return $sth->fetch(PDO::FETCH_ASSOC);
}

function getGroups($dbh){

	$sql = "SELECT * FROM kolibri_jogcsoportok ORDER BY id";

	$sth = $dbh->prepare($sql);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_ASSOC);

}

/**
 *
 * Aktualis tanev beallitas
 * @param unknown_type $dbh
 * @param unknown_type $semester
 */
function setCurrentSemester($dbh, $semester){

	$sql = "UPDATE `kolibri_beallitasok` SET `aktualis_tanev_id` = :tanevid WHERE `beallitasok_id` = 1";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':tanevid', $semester);
	$sth->execute();
}


function getCurrentSemester($dbh){

	$sql = "SELECT kt.*, kb.* FROM kolibri_beallitasok kb
	INNER JOIN kolibri_tanevek kt 
	ON kb.aktualis_tanev_id = kt.tanev_id";

	$sth = $dbh->prepare($sql);
	$sth->execute();

	return $sth->fetchAll(PDO::FETCH_ASSOC);

}


?>
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

	$sql = 'SELECT pk_id, kollegium_id, kollegiumi_dij FROM kolibri_penzugyi_kodok';
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

	$szobalakok = $sth->fetchAll(PDO::FETCH_ASSOC);

	return $szobalakok;

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

/**
 *
 * Assigns a card to a student
 * @param unknown_type $dbh
 * @param unknown_type $student
 * @param unknown_type $card
 */
function assignCardToStudent($dbh, $student, $card){

	if(isCardAvailable($dbh, $card)){

		$sql = 'INSERT INTO kolibri_belepokartyak (tanev_id, hallgato_id, kartya_szam, felvetel_datuma)
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
$allampolgarsag, $kepzesiforma, $penzugyikod, $hallgatoID){

	$sql = "INSERT INTO kolibri_hallgatok (
							hallgato_neptun_kod,
							hallgato_neve,
							hallgato_email, 
							hallgato_telefon, 
							hallgato_lakcim, 
							hallgato_allampolgarsag, 
							hallgato_kepzesi_forma, 
							hallgato_penzugyi_kod) 
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

	$sql = "INSERT INTO kolibri_felvettek (
							tanev_id,
							kollegium_id,
							hallgato_id,
							szobaba_beosztva)
							VALUES(:tanev,:kollegium,:hallgato, '0')";

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->bindParam(':kollegium',  $kollID);
	$sth->bindParam(':hallgato', $hallgID);
	$sth->execute();
}


function getStudentBeKikoltoztetLista($dbh, $name, $kollID){

	$sql = 'SELECT *, kh.hallgato_id as hallg_azon
					FROM kolibri_felvettek kf
    				inner join kolibri_hallgatok kh on kf.hallgato_id = kh.hallgato_id
    				left join kolibri_szoba_reszletek ksr on kh.hallgato_id = ksr.hallgato_id and not exists(
            		select 1
            		from kolibri_szoba_reszletek ksr2
            		where ksr.hallgato_id = ksr2.hallgato_id
                	and ksr2.reszletek_id > ksr.reszletek_id
        			)
    				left join kolibri_szoba_definiciok ksd on ksr.szoba_id = ksd.szoba_def_id
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
	return  = $sth->fetch(PDO::FETCH_ASSOC);
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


/**
 *
 * Legal relationship = hallgató létező koll. jogviszonnyal
 * Enter description here ...
 */
function assignStudentWithLegalRelationshipToRoom($dbh, $studentID, $kollID, $roomID){

	$sql = "INSERT INTO kolibri_szoba_reszletek (
							hallgato_id,
							tanev_id,
							kollegium_id,
							szoba_id,
							beosztas_datuma,
							bekoltozes_datuma)
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

	$sql = "INSERT INTO kolibri_szoba_reszletek (
							hallgato_id,
							tanev_id,
							kollegium_id,
							szoba_id,
							beosztas_datuma)
							VALUES(:hallgato,:tanev,:kollegium, :szoba, :beosztas_datuma)";

	$sth = $dbh->prepare($sql);

	$sth->bindParam(':hallgato', $studentID);
	$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->bindParam(':kollegium', $kollID);
	$sth->bindParam(':szoba', $roomID);
	$sth->bindParam(':beosztas_datuma', date('Y-m-d H:i:s'));
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
	$sth->bindParam(':status', $status);
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




?>
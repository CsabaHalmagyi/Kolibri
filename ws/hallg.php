<?php
require_once '../includes/connection.inc.php';
require_once '../includes/dbservice.inc.php';
//is_logged_out();
require_once '../settings/db.php';

$errorCode = 0;
$message = "";

function printResponse($errorCode, $message, $response1, $response2){
	$responseArray = array (
			'serviceName' => 'kolibri_rest',
			'errorCode' => $errorCode,
			'message' => $message,
			'data' => $response1,
			'data2' => $response2
	);

	header ( "Access-Control-Allow-Origin: *" );
	header ( 'Content-Type: application/json; charset=utf-8');
	echo json_encode ( $responseArray );
	die();
}


function apartmanSzam($szoba){

	return str_replace("0","/", $szoba);
}

if (!isset($_SESSION['felhasznalo']['felhasznalo_id'])) printResponse(1000, "Logout", null, null);

if(count($_POST)>0){

	$action = $_POST['action'];

	//ACTION: createNewStudent
	if($action == "createNewStudent"){

		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);
		if(isset($_POST['nk']) && strlen($_POST['nk']) == 6){

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
			}

			$student = getStudentByNeptun($dbh, $_POST['nk']);

			//if the student exists already, update her
			if($student != null) {
				$hid = $student['hallgato_id'];
				$felvett = getEnrollmentDetails($dbh, $hid);

				if($felvett['kollegium_id'] == $_POST['kollegium']){

					updateStudent($dbh, $_POST['nk'], $_POST['nev'], $_POST['email'], $_POST['telefon'],
					$_POST['lakcim'], $_POST['allampolgarsag'], $_POST['kepzesiforma'],
					$_POST['penzugyikod'], $hid);

					printResponse(0,"A hallgató már szerepel a hallgató adatbázisban, frissítem az adatait",null,null);
				}
				else if(strlen($felvett['kollegium_id'])>0 && $felvett['kollegium_id'] != $_POST['kollegium']){

					$message = "A hallgató jelenleg egy másik kollégiumba van felvéve.";
					printResponse(1,$message,null,null);
				}
				else{
					//A hallgató már szerepel a hallgató adatbázisban, frissítem az adatait

					updateStudent($dbh, $_POST['nk'], $_POST['nev'], $_POST['email'], $_POST['telefon'],
					$_POST['lakcim'], $_POST['allampolgarsag'], $_POST['kepzesiforma'],
					$_POST['penzugyikod'], $hid);

					//hozzáadás a felvettekhez
					addStudentToEnrollmentList($dbh, $_POST['kollegium'], $hid);
					printResponse(0,"A hallgató már szerepel a hallgató adatbázisban, frissítem az adatait",null,null);
				}
			}
			//this is a new student, insert and add to felvettek table
			else if(count($student) == 0){

				$hid = createNewStudent($dbh, $_POST['nk'], $_POST['nev'], $_POST['email'], $_POST['telefon'],
				$_POST['lakcim'], $_POST['allampolgarsag'], $_POST['kepzesiforma'],
				$_POST['penzugyikod']);

				//if inserting the new student was successful
				if($hid){
					addStudentToEnrollmentList($dbh, $_POST['kollegium'], $hid);

					$message = 'Hallgató adatai rögzítve';
					printResponse(0,$message,null,null);
				}
				else{

					$message = 'Hallgató létrehozása sikertelen.';
					printResponse(1,$message,null,null);
				}
					
			}
			else{

				$message = "Adatbázis hiba!";
				printResponse(18,$message,null,null);
			}
		}
		else{

			$message = "Hibás neptun kód!";
			printResponse(21,$message,null,null);
		}
	}
	//ACTION: lookupStudentWithoutRoom
	else if($action == "lookupStudentWithoutRoom"){
		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
		}

		if(isset($_POST['hallgato']) && strlen($_POST['hallgato'])>0){

			$result = getStudentsWithoutRoomNameContains($dbh, $_POST['kollegium'], $_POST['hallgato']);
		}
		else{

			$result = getStudentsWithoutRoom($dbh, $_POST['kollegium']);
		}

		$message = "Hallgatók szoba nélkül.";
		printResponse(0,$message,$result,null);
	}

	//ACTION: hallgatoBeKikoltoztetLista
	else if($action == "hallgatoBeKikoltoztetLista"){
		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
		}

		if(isset($_POST['hallgato']) && strlen($_POST['hallgato'])>0 && strlen($_POST['kollegium'])>0){

			$result = getStudentBeKikoltoztetLista($dbh, $_POST['hallgato'], $_POST['kollegium']);

			$tablazat = '<table class="table table-bordered table-hover"><thead><th>Neptun</th><th>Hallgató neve</th><th>Szoba</th></thead><tbody>';

			foreach($result as $r){

				$tr = '<tr id="hallgato_'.$r['hallg_azon'].'" class="hallgatorow"><td>'.$r['hallgato_neptun_kod'].'</td><td>'.$r['hallgato_neve'].'</td><td>';
					
				if($_POST['kollegium'] == "2"){
					$tr .=	apartmanSzam($r['szoba_szam']);
				}
				else{
					$tr .=	$r['szoba_szam'];
				}
				$tr .= '</td></tr>';
				$tablazat.=$tr;
			}

			$tablazat.='</tbody></table>';
			printResponse(0,"Siker",$tablazat,null);
		}
		else{
			printResponse(3,"",null,null);

		}
	}

	//ACTION: hallgatoBeKikoltoztetAdat
	else if($action == "hallgatoBeKikoltoztetAdat"){
		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);


		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
		}

		if(isset($_POST['hallgato']) && strlen($_POST['hallgato'])>0 && strlen($_POST['kollegium'])>0){

			$r = getStudentBeKikoltoztetAdat($dbh, $_POST['hallgato'], $_POST['kollegium']);

			$tablazat = '<table class="table table-bordered">';
			$tablazat.= '<tr><td>Neptun kód</td><td>'.$r['hallgato_neptun_kod'].'</td></tr>';
			$tablazat.= '<tr><td>Név</td><td><a href="hallgatok.php?id='.$_POST['hallgato'].'">'.$r['hallgato_neve'].'</a></td></tr>';
			$tablazat.= '<tr><td>Szoba</td><td>';

			if($_POST['kollegium'] == "2"){
				$tablazat .= apartmanSzam($r['szoba_szam']);
			}
			else{
				$tablazat .= $r['szoba_szam'];
			}

			$tablazat.= '</td></tr>';

			if($r['bekoltozes_datuma'] == "0000-00-00 00:00:00"){
				$tablazat.= '<tr><td>Jogviszony létrehozása</td><td><button type="button" class="btn btn-primary jogviszony_letrehozasa" id="bekoltoztet_'.$r['reszletek_id'].'">Beköltöztet</button></td></tr>';

			}
			else{
				$tablazat.= '<tr><td>Jogviszony létrehozása</td><td><button type="button" class="btn btn-primary disabled jogviszony_letrehozasa" id="bekoltoztet_'.$r['reszletek_id'].'">Beköltöztet</button></td></tr>';
			}


			if($r['kikoltozes_datuma'] != "0000-00-00 00:00:00" || $r['szoba_szam'] ==null){
				$tablazat.= '<tr><td>Jogviszony megszűntetése</td><td><button type="button" class="btn btn-primary jogviszony_megszuntetese" id="kikoltoztet_'.$_POST['hallgato'].'">Kiköltöztet</button></td></tr>';
			}
			else{
				$tablazat.= '<tr><td>Jogviszony megszűntetése</td><td><button type="button" class="btn btn-primary disabled jogviszony_megszuntetese" id="kikoltoztet_'.$_POST['hallgato'].'">Kiköltöztet</button></td></tr>';
			}


			$tablazat.='</table>';



			printResponse(0,"Siker",$tablazat,null);
		}
		else{
			printResponse(0,"",null,null);

		}
	}
	//ACTION: studentToRoom
	else if($action == "studentToRoom"){
		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);

		$hallgato = $_POST['hallgato'];
		$kollegium = $_POST['kollegium'];
		$szoba = $_POST['szoba'];

		if(strlen($hallgato) == 0 || strlen($kollegium) == 0 || strlen($szoba) == 0){
			printResponse(1,"Hiányzó id!",null,null);
		}


		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null, null);
		}

		//hallgato szobahoz rendelese
		//van hely a szobaban?

		$szabad = getRoomDefinition($dbh, $szoba);

		$szabadhely = intval($szabad['szabad_ferohely']);

		//van hely, hallgato hozzarendelese
		if ($szabadhely > 0){
			//van e mar felevi bejegyzes? = van jogviszony = átköltözés

			$bekoltozes = getStudentSemesterEntries($dbh, $hallgato, $kollegium);

			$jogviszony = false;
			if(count($bekoltozes)>0) $jogviszony = true;

			if ($jogviszony){
				
				assignStudentWithLegalRelationshipToRoom($dbh, $hallgato, $kollegium, $szoba);
			}
			else{
				//nem lakott a felev soran meg itt, sima uj bejegyzes
				assignStudentToRoom($dbh, $hallgato, $kollegium, $szoba);
			}

			// update hallgato_felvettek
			updateRoomStatusOnStudentSemesterList($dbh, $hallgato, 1);
			
			$ujszabadhely = $szabadhely-1;
			
			if($ujszabadhely>=0){

				updateRoomDefFreeSpace($dbh, $szoba, $ujszabadhely);
				
				$errorCode = 0;
				$message = 'Sikeres hozzárendelés!';
				printResponse($errorCode,$message,array("szoba"=>$szoba,"szabad"=>$ujszabadhely),null);
			}
			else{
				$errorCode = 1;
				$message = 'Sikertelen hozzárendelés!';
				printResponse($errorCode,$message,null,null);
			}
		}
		else{
			$errorCode = 1;
			$message = 'Nincs hely a szobában!';
			printResponse($errorCode,$message,null,null);
		}
	}

	//ACTION: studentRemoveFromRoom
	else if($action == "studentRemoveFromRoom"){
		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);

		$hallgato = $_POST['hallgato'];
		$kollegium = $_POST['kollegium'];
		$szoba = $_POST['szoba'];

		if(strlen($hallgato) == 0 || strlen($kollegium) == 0 || strlen($szoba) == 0){
			printResponse(1,"Hiányzó id!",null,null);
		}

		try {
			$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
		}catch (PDOException $e) {
			echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
			die();
		}

		$dbh -> exec("SET CHARACTER SET utf8");
		$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");

		// korabbi bejegyzesek?

		$sql = 'SELECT reszletek_id, bekoltozes_datuma FROM kolibri_szoba_reszletek
						WHERE tanev_id = :tanev
						AND kollegium_id = :kollegium
						AND hallgato_id = :hallgato
						AND szoba_id = :szoba 
						ORDER BY reszletek_id DESC
						LIMIT 1';

		$sth2 = $dbh->prepare($sql);

		$sth2->bindParam(':hallgato', $hallgato);
		$sth2->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
		$sth2->bindParam(':kollegium', $kollegium);
		$sth2->bindParam(':szoba', $szoba);
		$sth2->execute();

		$bekoltozes = $sth2->fetch(PDO::FETCH_ASSOC);

		if($bekoltozes['bekoltozes_datuma'] == "0000-00-00 00:00:00"){
			//bejegyzes torlese
			$sql = "DELETE FROM kolibri_szoba_reszletek
				WHERE hallgato_id = :hallgato
				AND tanev_id = :tanev
				AND kollegium_id = :kollegium
				AND szoba_id = :szoba";
			$sth = $dbh->prepare($sql);

			$sth->bindParam(':hallgato', $hallgato);
			$sth->bindParam(':tanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
			$sth->bindParam(':kollegium', $kollegium);
			$sth->bindParam(':szoba', $szoba);
			$sth->execute();

		}
		else{
			$sql = 'UPDATE kolibri_szoba_reszletek SET kikoltozes_datuma = :kikoltozes WHERE reszletek_id = :reszletek';
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':kikoltozes', date('Y-m-d H:i:s'));
			$sth->bindParam(':reszletek', $bekoltozes['reszletek_id']);
			$sth->execute();
		}


		// update hallgato_felvettek

		$sqlu = "UPDATE kolibri_felvettek SET szobaba_beosztva = '0' WHERE hallgato_id = :hallgato";

		$sth2 = $dbh->prepare($sqlu);
		$sth2->bindParam(':hallgato', $hallgato, PDO::PARAM_INT);
		$sth2->execute();

		//update szabadhely!
		//mennyi hely van
		$sql = 'SELECT *
					FROM kolibri_szoba_definiciok
					WHERE szoba_def_id = :szoba';

		$sth = $dbh->prepare($sql);
		$sth->bindParam(':szoba', $szoba);
		$sth->execute();

		$szabad = $sth->fetch(PDO::FETCH_ASSOC);
		$szabadhely = intval($szabad['szabad_ferohely']);
		$maxhely = intval($szabad['max_ferohely']);

		$ujszabadhely = $szabadhely+1;

		if($ujszabadhely<=$maxhely){
			//update szabadhely
			$sqlu2 = "UPDATE kolibri_szoba_definiciok SET szabad_ferohely = :szabadhely WHERE szoba_def_id = :szoba";

			$sth3 = $dbh->prepare($sqlu2);
			$sth3->bindParam(':szabadhely', $ujszabadhely, PDO::PARAM_INT);
			$sth3->bindParam(':szoba', $szoba, PDO::PARAM_INT);
			$sth3->execute();

		}

		printResponse(0,"Hallgató eltávolítva a szobából.",array("szoba"=>$szoba,"szabad"=>$ujszabadhely),null);

	}
	//ACTION: hallgatoJogviszonyLetrehoz
	else if($action == "hallgatoJogviszonyLetrehoz"){
		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);

		$reszletek = $_POST['reszletek'];

		if(!isset($_POST['reszletek'])){
			printResponse(1,"Hiányzó id!",null,null);
		}

		try {
			$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
		}catch (PDOException $e) {
			echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
			die();
		}

		$dbh -> exec("SET CHARACTER SET utf8");
		$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");

		$sql = 'SELECT bekoltozes_datuma, kikoltozes_datuma
				FROM kolibri_szoba_reszletek
				WHERE reszletek_id = :reszletek';

		$sth = $dbh->prepare($sql);
		$sth->bindParam(':reszletek', $reszletek);
		$sth->execute();

		$bejegyzes = $sth->fetch(PDO::FETCH_ASSOC);
		if($bejegyzes['kikoltozes_datuma'] !="0000-00-00 00:00:00" &&  $bejegyzes['bekoltozes_datuma'] !="0000-00-00 00:00:00"){
			printResponse(2,"Hibás rekord!",null,null);

		}
		else{
			$sql = 'UPDATE kolibri_szoba_reszletek SET bekoltozes_datuma = :bekoltozes WHERE reszletek_id = :reszletek';
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':reszletek', $reszletek);
			$sth->bindParam(':bekoltozes', date('Y-m-d H:i:s'));
			$sth->execute();

			printResponse(0,"",null,null);
		}
	}
	//ACTION: hallgatoJogviszonyMegszuntet
	else if($action == "hallgatoJogviszonyMegszuntet"){

		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);

		$hallgato = $_POST['hallgato'];

		if(!isset($_POST['hallgato']) || strlen($_POST['hallgato'])<1){
			printResponse(1,"Hiányzó id!",null,null);
		}

		try {
			$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
		}catch (PDOException $e) {
			echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
			die();
		}

		$dbh -> exec("SET CHARACTER SET utf8");
		$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");

		$sql = 'SELECT szobaba_beosztva
				FROM kolibri_felvettek
				WHERE hallgato_id = :hallgato';

		$sth = $dbh->prepare($sql);
		$sth->bindParam(':hallgato', $hallgato);
		$sth->execute();

		$allapot = $sth->fetch(PDO::FETCH_ASSOC);

		if($allapot['szobaba_beosztva'] == "0"){

			$sql = 'DELETE FROM kolibri_felvettek
				WHERE hallgato_id = :hallgato';

			$sth = $dbh->prepare($sql);
			$sth->bindParam(':hallgato', $hallgato);
			$sth->execute();

			printResponse(0,"Kollégiumi jogviszony megszüntetve.",null,null);
		}
		else{
			printResponse(3,"Hallgató szobába van beosztva",null,null);
		}
	}
	else if($action == "updateStudent"){

		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);

		if(isset($_POST['hallgid']) && strlen($_POST['hallgid'])>0){


			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}

			$dbh -> exec("SET CHARACTER SET utf8");
			$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");

			$sql = 'SELECT * FROM kolibri_hallgatok WHERE hallgato_id = :hallgato';
			$sth = $dbh->prepare($sql);
			$sth->bindParam(':hallgato', $_POST['hallgid']);
			$sth->execute();

			$h = $sth->fetch(PDO::FETCH_ASSOC);

			if(empty($h['hallgato_id'])){
				printResponse(15,"Nem találom a hallgatót.",null,null);
			}

			if($h['hallgato_neve'] == $_POST['nev'] &&
			$h['hallgato_email'] == $_POST['email'] &&
			$h['hallgato_telefon'] == $_POST['telefon'] &&
			$h['hallgato_lakcim'] == $_POST['lakcim'] &&
			$h['hallgato_allampolgarsag'] == $_POST['allampolgarsag'] &&
			$h['hallgato_kepzesi_forma'] == $_POST['kepzesiforma'] &&
			$h['hallgato_penzugyi_kod'] == $_POST['penzugyikod']){


				printResponse(0,"Hallgató adatai nem változtak.",null,null);
			}
			else{

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
				$sth->bindParam(':nev', $_POST['nev']);
				$sth->bindParam(':email', $_POST['email']);
				$sth->bindParam(':telefon', $_POST['telefon']);
				$sth->bindParam(':lakcim', $_POST['lakcim']);
				$sth->bindParam(':allampolgarsag', $_POST['allampolgarsag']);
				$sth->bindParam(':kepzesiforma', $_POST['kepzesiforma']);
				$sth->bindParam(':penzugyikod', $_POST['penzugyikod']);
				$sth->bindParam(':hid', $_POST['hallgid']);
				$sth->execute();

				printResponse(0,"Hallgató adatai frissítve.",null,null);
			}

		}
		else{
			printResponse(15,"Hiányzó hallgató azonosító",null,null);
		}

	}

	else if($action == "hallgatoKeres"){

		if(isset($_POST['celcsoport']) && strlen($_POST['celcsoport'])>0 &&
		isset($_POST['mire']) && strlen($_POST['mire'])>0 &&
		isset($_POST['keresoszo']) && strlen($_POST['keresoszo'])>0){


			try {
				$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
			}catch (PDOException $e) {
				echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
				die();
			}

			$dbh -> exec("SET CHARACTER SET utf8");
			$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");


			$cel = $_POST['celcsoport'];
			$mire = $_POST['mire'];
			$szo = $_POST['keresoszo'];
			$kartya = false;


			if($cel == "Aktualis" && $mire == "Nev"){
				$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_felvettek
					INNER JOIN kolibri_hallgatok
					ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
					WHERE kolibri_hallgatok.hallgato_neve LIKE :nev
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 15';

				$sth = $dbh->prepare($sql);
				$keyword = "%$szo%";
				$sth->bindParam(':nev', $keyword);
			}
			else if($cel == "Aktualis" && $mire == "Neptun"){
				$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_felvettek
					INNER JOIN kolibri_hallgatok
					ON kolibri_felvettek.hallgato_id = kolibri_hallgatok.hallgato_id
					WHERE kolibri_hallgatok.hallgato_neptun_kod LIKE :neptun
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 15';

				$sth = $dbh->prepare($sql);
				$keyword = "%$szo%";
				$sth->bindParam(':neptun', $keyword);

			}
			else if($cel == "Aktualis" && $mire == "Kartya"){
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
				$keyword = "$szo%";
				$sth->bindParam(':kartya', $keyword);
				$kartya = true;
			}
			else if($cel == "Osszes" && $mire == "Nev"){
				$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_hallgatok
					WHERE kolibri_hallgatok.hallgato_neve LIKE :nev
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 15';
					
				$sth = $dbh->prepare($sql);
				$keyword = "%$szo%";
				$sth->bindParam(':nev', $keyword);
			}
			else if($cel == "Osszes" && $mire == "Neptun"){
				$sql = 'SELECT kolibri_hallgatok.hallgato_id, kolibri_hallgatok.hallgato_neptun_kod,
					kolibri_hallgatok.hallgato_neve
					FROM kolibri_hallgatok
					WHERE kolibri_hallgatok.hallgato_neptun_kod LIKE :neptun
					ORDER BY  kolibri_hallgatok.hallgato_neve
					LIMIT 15';
					
				$sth = $dbh->prepare($sql);
				$keyword = "%$szo%";
				$sth->bindParam(':neptun', $keyword);
					
			}
			else if($cel == "Osszes" && $mire == "Kartya"){
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
				$keyword = "$szo%";
				$sth->bindParam(':kartya', $keyword);
				$kartya = true;
			}




			$sth->execute();
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);



			$tablazat = '<table class="table table-bordered table-hover"><thead><th>Neptun</th><th>Hallgató neve</th>';
			if($kartya){
				$tablazat .= '<th>Kártyaszám</th>';
			}

			$tablazat .= '<th>Adatlap</th>';
			if ($_SESSION['jog']['hallgato_adatmodositas'] == '1') $tablazat.='<th>Mód.</th>';
			$tablazat .='<th>Kártya</th>';

			$tablazat .='</thead><tbody>';


			foreach($result as $r){
					
				$tr = '<tr><td>'.$r['hallgato_neptun_kod'].'</td><td>'.$r['hallgato_neve'].'</td>';

				if($kartya){
					$tr .= '<td>'.$r['kartya_szam'].'</td>';
				}

				$tr .='<td><a href="hallgatok.php?id='.$r['hallgato_id'].'"> Adatlap</a></td>';
					
				if ($_SESSION['jog']['hallgato_adatmodositas'] == '1') {
					$tr .= '<td><a href="adatmodositas.php?id='.$r['hallgato_id'].'">Adatmódosítás</a></td>';
				}
					
				$tr .= '<td><a href="kartya.php?id='.$r['hallgato_id'].'">Kártyák</td>';
				$tr .= '</tr>';
				$tablazat.=$tr;
			}

			$tablazat.='</tbody></table>';
			printResponse(0,"OK",$tablazat,null);


		}
		else{
			printResponse(15,"Hiányzó paraméter",null,null);
		}





	}








	else{
		$errorCode = 1;
		$message = 'Ismeretlen feladat!';
		printResponse($errorCode,$message,null,null);

	}



}







?>
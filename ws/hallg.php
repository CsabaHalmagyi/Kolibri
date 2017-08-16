<?php
require_once '../includes/connection.inc.php';
require_once '../includes/dbservice.inc.php';
//is_logged_out();


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
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
			}

			$student = getStudentByNeptun($dbh, $_POST['nk']);

			//if the student exists already, update her
			if($student != null) {
				$hid = $student['hallgato_id'];
				$felvett = getEnrollDetails($dbh, $hid);

				if($felvett['kollegium_id'] == $_POST['kollegium']){

					updateStudent($dbh, $_POST['nk'], $_POST['nev'], $_POST['email'], $_POST['telefon'],
					$_POST['lakcim'], $_POST['allampolgarsag'], $_POST['kepzesiforma'],
					$_POST['penzugyikod'], $hid);

					printResponse(0,"A hallgató már szerepel a hallgató adatbázisban, frissítem az adatait. ID:".$hid,null,null);
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
					printResponse(0,"A hallgató már szerepel a hallgató adatbázisban, frissítem az adatait. ID:".$hid,null,null);
				}
			}
			//this is a new student, insert and add to felvettek table
			else if(count($student) == 0){

				$hid = createNewStudent($dbh, $_POST['nk'], $_POST['nev'], $_POST['email'], $_POST['telefon'],
				$_POST['lakcim'], $_POST['allampolgarsag'], $_POST['kepzesiforma'],
				$_POST['penzugyikod']);

				//if inserting the new student was successful
				if($hid){
					$error = addStudentToEnrollmentList($dbh, $_POST['kollegium'], $hid);

					$message = 'Hallgató adatai rögzítve. ID:'.$hid;
					
					//file_put_contents('log.txt', print_r($error, true));
					
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
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
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
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
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
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
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


			if($r['kikoltozes_datuma'] != "0000-00-00 00:00:00" || $r['szoba_szam'] == null){
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
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
		}

		$doubleEntry = isStudentInRoom($dbh, $kollegium, $szoba, $hallgato);

		if($doubleEntry){
				
			printResponse(2,"A hallgató már hozzá van rendelve a szobához.", null, null);
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

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
		}


		// korabbi bejegyzesek?
		$bekoltozes = getStudentSemesterEntriesToRoom($dbh, $hallgato, $kollegium, $szoba);

		if($bekoltozes['bekoltozes_datuma'] == "0000-00-00 00:00:00"){
			//bejegyzes torlese
			removeStudentFromRoom($dbh, $hallgato, $kollegium, $szoba);
		}
		else{
				
			removeStudentWithLegalRelationshipFromRoom($dbh, $bekoltozes['reszletek_id']);
		}

		// update hallgato_felvettek
		updateRoomStatusOnStudentSemesterList($dbh, $hallgato, 0);

		//update szabadhely!
		//mennyi hely van
		$szabad = getRoomDefinition($dbh, $szoba);

		$szabadhely = intval($szabad['szabad_ferohely']);
		$maxhely = intval($szabad['max_ferohely']);

		$ujszabadhely = $szabadhely+1;

		if($ujszabadhely<=$maxhely){
			//update szabadhely
			updateRoomDefFreeSpace($dbh, $szoba, $ujszabadhely);
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

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
		}

		$bejegyzes = getRoomDetailsByID($dbh, $reszletek);

		if($bejegyzes['kikoltozes_datuma'] !="0000-00-00 00:00:00" &&  $bejegyzes['bekoltozes_datuma'] !="0000-00-00 00:00:00"){

			printResponse(2,"Hibás rekord!",null,null);
		}
		else{
			//Koll jogviszony letrehozas
			createLegalRelationshipByID($dbh, $reszletek);
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

		$dbh = connectToDB();
		if(!$dbh){
			printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
		}

		$allapot = getStudentRoomStatus($dbh, $hallgato);

		if($allapot['szobaba_beosztva'] == "0"){
			//ha nincs szobába beosztva, szabadon törölhető a felvettek listájáról
			removeStudentFromEnrollmentList($dbh, $hallgato);
			printResponse(0,"Kollégiumi jogviszony megszüntetve.",null,null);
		}
		else{
			printResponse(3,"Hallgató szobába van beosztva",null,null);
		}
	}
	else if($action == "updateStudent"){

		if ($_SESSION['jog']['bekoltoztetes'] != "1") printResponse(7, "Nincs jogosultságod a művelethez", null, null);

		if(isset($_POST['hallgid']) && strlen($_POST['hallgid'])>0){

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
			}

			$h = getStudentByID($dbh, $_POST['hallgid']);

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

				updateStudentByID($dbh, $_POST['nev'], $_POST['email'], $_POST['telefon'], $_POST['lakcim'],
				$_POST['allampolgarsag'], $_POST['kepzesiforma'], $_POST['penzugyikod'], $_POST['hallgid']);

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

			$dbh = connectToDB();
			if(!$dbh){
				printResponse(2,"Adatbázis kapcsolat nem jött létre", null, null);
			}

			$cel = $_POST['celcsoport'];
			$mire = $_POST['mire'];
			$szo = $_POST['keresoszo'];
			$kartya = false;
				
			// Cel: Aktualis = aktualis tanevre felvett hallgato
			// Cel: Osszes = az osszes, valaha bent lako hallgato
			if($cel == "Aktualis" && $mire == "Nev"){

				$result = searchForStudentOnEnrollmentListByName($dbh, $szo);
			}
			else if($cel == "Aktualis" && $mire == "Neptun"){

				$result = searchForStudentOnEnrollmentListByNeptun($dbh, $szo);
			}
			else if($cel == "Aktualis" && $mire == "Kartya"){

				$result = searchForStudentOnEnrollmentListByCard($dbh, $szo);
				$kartya = true;
			}
			else if($cel == "Osszes" && $mire == "Nev"){

				$result = searchForStudentByName($dbh, $szo);
			}
			else if($cel == "Osszes" && $mire == "Neptun"){

				$result = searchForStudentByNeptun($dbh, $szo);
			}
			else if($cel == "Osszes" && $mire == "Kartya"){

				$result = searchForStudentByCard($dbh, $szo);
				$kartya = true;
			}


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
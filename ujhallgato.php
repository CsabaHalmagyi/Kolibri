<?php
require_once 'includes/connection.inc.php';
is_logged_out ();
require_once 'includes/html_top.inc.php';
require_once 'includes/menu.inc.php';
require_once 'includes/PHPExcel.php';
require_once 'includes/dbservice.inc.php';

if ($_SESSION ['jog'] ['bekoltoztetes'] != "1") {
	echo '<div class="content">
        <div class="main-content">Nincs jogosultságod ehhez a művelethez.</div></div>';
	require_once "includes/html_bottom.inc.php";
	die ();
}


//file upload

if(count($_FILES)!=0){
	?>
<div class="content">
	<div class="main-content">

	<?php

	$target_dir = "uploads/";
	$target_dir = $target_dir . basename( "ujhallgatok.xls");
	//echo "OK";
	//print_r($_FILES);
	
	if (move_uploaded_file($_FILES["uploadFile"]["tmp_name"], $target_dir)) {
		echo '<div class="alert alert-success">A file '. $fname. ' sikeresen feltöltve.</div><br/><br/>';

		//load xls
		class SpecialValueBinder extends PHPExcel_Cell_DefaultValueBinder implements PHPExcel_Cell_IValueBinder
		{
			public function bindValue(PHPExcel_Cell $cell, $value = null)
			{

				$value = PHPExcel_Shared_String::SanitizeUTF8($value);
				$cell->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
				return true;
			}
		}

		/**  Tell PHPExcel that we want to use our Special Value Binder  **/
		PHPExcel_Cell::setValueBinder( new SpecialValueBinder() );

		$inputFileName = $target_dir;

		try {
			$inputFileType = PHPExcel_IOFactory::identify ( $inputFileName );
			$objReader = PHPExcel_IOFactory::createReader ( $inputFileType );
			$objPHPExcel = $objReader->load ( $inputFileName );
		} catch ( Exception $e ) {
			die ( 'Error loading file "' . pathinfo ( $inputFileName, PATHINFO_BASENAME ) . '": ' . $e->getMessage () );
			require_once "includes/html_bottom.inc.php";
		}

		// Get worksheet dimensions
		$sheet = $objPHPExcel->getSheet ( 0 );
		$highestRow = $sheet->getHighestRow ();
		$highestColumn = $sheet->getHighestColumn ();

		$excelData = array ();
		// Read all rows
		for($row = 1; $row <= $highestRow; $row ++) {

			$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, FALSE, FALSE, FALSE);
			array_push($excelData, $rowData[0]);
		}

		$errors = array();
		//validation

		if(strtolower($excelData[0][0]) != "neptun") $errors[] = 'A fejléc első mezője "neptun" kell legyen.';
		if(strtolower($excelData[0][1]) != "nev") $errors[] = 'A fejléc második mezője "nev" kell legyen.';
		if(strtolower($excelData[0][2]) != "email") $errors[] = 'A fejléc harmadik mezője "email" kell legyen.';
		if(strtolower($excelData[0][3]) != "telefon") $errors[] = 'A fejléc negyedik mezője "telefon" kell legyen.';
		if(strtolower($excelData[0][4]) != "lakcim") $errors[] = 'A fejléc ötödik mezője "lakcim" kell legyen.';
		if(strtolower($excelData[0][5]) != "allampolgarsag") $errors[] = 'A fejléc hatodik mezője "allampolgarsag" kell legyen.';
		if(strtolower($excelData[0][6]) != "kepzesiforma") $errors[] = 'A fejléc hetedik mezője "kepzesiforma" kell legyen.';
		if(strtolower($excelData[0][7]) != "felveve") $errors[] = 'A fejléc nyolcadik mezője "felveve" kell legyen.';
		if(strtolower($excelData[0][8]) != "finanszirozas") $errors[] = 'A fejléc kilencedik mezője "finanszirozas" kell legyen.';


		if(!empty($errors)){
			foreach($errors as $err){
				echo $err.'<br/>';
			}
			require_once "includes/html_bottom.inc.php";
			die();

		}

		//if validation is OK
		//var_dump($excelData);

		//connect to db
		$dbh = connectToDB();
		if(!$dbh){
			echo 'Adatbázis kapcsolat nem jött létre"</div></div>';
			require_once "includes/html_bottom.inc.php";
			die ();
		}
		
		//read required information for storing new students

		$kollespk = getDormsWithFinancialCodes($dbh);
		
		$koll = $kollespk['kollegiumok'];
		$kollegiumok = array();
		foreach($koll as $k){
			$kollegiumok[$k['kollegium_rovid_nev']]=$k['kollegium_id'];
		}

		$penzugyi_kodok = $kollespk['penzugyikodok'];

		$problemas = array();

		$ok = 0;

		for($i = 1; $i < $highestRow; $i ++) {

			$nk = trim($excelData[$i][0]);
			$nev = trim($excelData[$i][1]);
			$email = trim($excelData[$i][2]);
			$telefon = trim($excelData[$i][3]);
			$lakcim = trim($excelData[$i][4]);
			$allampolgarsag = trim($excelData[$i][5]);
			$kepzesiforma = trim($excelData[$i][6]);
			$felveve = trim($excelData[$i][7]);
			$finanszirozas = trim($excelData[$i][8]);

			if(empty($nk) || strlen($nk) != 6) {
				$problemas[] = array("sor"=>$i,"nk"=>$nk,"nev"=>$nev,"hibaok"=>"Hiányzó vagy hibás neptun kód.", "error"=>true);
				continue;
			}

			if(empty($nev) || strlen($nev) < 3) {
				$problemas[] = array("sor"=>$i, "nk"=>$nk,"nev"=>$nev,"hibaok"=>"Hiányzó vagy hibás név.","error"=>true);
				continue;
			}

			if(!array_key_exists($felveve, $kollegiumok)){
				$problemas[] = array("sor"=>$i, "nk"=>$nk,"nev"=>$nev,"hibaok"=>"Hibás kollégium név.", "error"=>true);
				continue;
			}

			if($finanszirozas != "A" && $finanszirozas != "K"){
				$problemas[] = array("sor"=>$i, "nk"=>$nk,"nev"=>$nev,"hibaok"=>"Hibás finanszírozási forma.", "error"=>true);
				continue;
			}

			$penzugyikod = null;

			foreach($penzugyi_kodok as $pk){
				if($pk['finanszirozas'] == $finanszirozas && $pk['kollegium_id'] == $kollegiumok[$felveve]){
					$penzugyikod = $pk['pk_id'];
				}

			}

			if($penzugyikod == null){
				$problemas[] = array("sor"=>$i, "nk"=>$nk,"nev"=>$nev,"hibaok"=>"Pénzügyi kód meghatározás sikertelen.", "error"=>true);
				continue;
			}

			//if the file passed the validation

			$hallgato = getStudentByNeptun($dbh, $nk);

			//if the student exists already, update her
			if($hallgato['hallgato_id'] != null) {
				$hid = $hallgato['hallgato_id'];

				$felvett =  getEnrollDetails($dbh, $hid);
				
				if($felvett['kollegium_id'] == $kollegiumok[$felveve]){


					//A hallgató már szerepel a felvettek között, frissítem az adatait

					updateStudent($dbh, $nk, $nev, $email, $telefon,
					$lakcim, $allampolgarsag, $kepzesiforma,
					$penzugyikod, $hid);
					
					$problemas[] = array("sor"=>$i, "nk"=>$nk,"nev"=>$nev,"hibaok"=>"A hallgató már szerepel a felvettek között, frissítem az adatait.", "error"=>false);

				}
				else if(strlen($felvett['kollegium_id'])>0 && $felvett['kollegium_id'] != $kollegiumok[$felveve]){
					//hiba, a hallgato masik koliba van felveve

					$problemas[] = array("sor"=>$i, "nk"=>$nk,"nev"=>$nev,"hibaok"=>"A hallgató jelenleg egy másik kollégiumba van felvéve.", "error"=>true);
					continue;

				}
				else{

					//A hallgató már szerepel a hallgató adatbázizisban, frissítem az adatait
					updateStudent($dbh, $nk, $nev, $email, $telefon,
					$lakcim, $allampolgarsag, $kepzesiforma,
					$penzugyikod, $hid);
					
					
					//hozzáadás a felvettekhez
					addStudentToEnrollmentList($dbh, $kollegiumok[$felveve], $hid);
					$ok++;
				}
			}else{
				//new student, insert kolibri_hallgato + insert kolibri_felvettek
				
				$hid = createNewStudent($dbh, $nk, $nev, $email, $telefon,
				$lakcim, $allampolgarsag, $kepzesiforma,
				$penzugyikod);
				
				if($hid){

					/**
					 * Update kollegium_felvettek table!!!
					 */
					addStudentToEnrollmentList($dbh, $kollegiumok[$felveve], $hid);

					$ok++;

				}
			}
		}



		if(count($problemas)>0){

			echo '<div class="panel panel-default">';
			echo '<div class="panel-heading no-collapse">Hallgató betöltés - eredmények</div>';
			echo '<div id="hallgatobetoltesDIV">';
			echo '<table class="table table-bordered"><tbody>';

			foreach($problemas as $p){
				echo '<tr class="';
				if($p['error']) {
					echo 'alert-danger"';
				}
				else{
					echo 'alert-warning"';
				}
				echo '><td>'.$p['sor'].'</td><td>'.$p['nk'].'</td><td>'.$p['nev'].'</td><td>'.$p['hibaok'].'</td></tr>';
			}

			echo '</tbody></table></div></div>';

		}

		if($ok>0){
			echo '<div class="alert alert-success">Betöltött hallgatók: <strong>'.$ok.'</strong></div>';
		}
		else{
			echo '<div class="alert alert-danger">Betöltött hallgatók: <strong>'.$ok.'</strong></div>';
		}
			



	} else {
		echo "<p>Nem sikerült feltölteni a filet.<br/>";

	}

	require_once "includes/html_bottom.inc.php";


}
else{




	?>
		<script type="text/javascript">
	var AktTanev = "<?php echo $_SESSION['beallitasok']['tanev_nev'];?>";
    </script>
		<script type="text/javascript">
        jQuery( document ).ready(function() {

        	jQuery('body').on('click', '#createNewStudent', function () {
        		jQuery(this).prop('disabled', true);
        		createNewStudent();
            	});
        	jQuery('body').on('click', '#cancelNewStudent', function () {
        		cancelNewStudent();
            	});

        	jQuery('body').on('click', '#uploadXLS', function () {
        		jQuery(this).prop('disabled', true);
        		uploadXLS();
            	});
        	
        	
        	jQuery( 'body' ).on('change', '#felveve', function () {
        		updateFinanceCodeList();
            	});

        	
			getDorms();        	
			

            });
        </script>

		<div class="content">
			<div class="main-content">

				<div class="row">
					<div class="col-sm-6 col-md-6">
						<div id="ujHallgatoDiv1"></div>
					</div>
					<div class="col-sm-6 col-md-6">
						<div id="ujHallgatoDiv2"></div>
						<div id="ujHallgatoFileDiv">
							<div class="panel panel-default">
								<div class="panel-heading no-collapse">Hallgatók felvitele XLS
									file-ból</div>
								<div class="panel-body">
									<form method="post" enctype="multipart/form-data"
										id="studentsFromXLS">
										<div class="form-group">
											<label>File feltöltés</label> <input type="file"
												name="uploadFile" id="uploadFile" />
										</div>

										<div class="form-group">
											<a class="btn btn-primary" id="uploadXLS">Feltöltés</a>
										</div>

									</form>
								</div>
							</div>
							<!-- 					<table id="uploader" class>
						<tr><td>Hallgatók felvétele XLS fileból:</td><td> <input type="file" name="uploadFile"></td></tr>
						<tr><td><input type="submit" value="Hallgatók felvétele"></td></tr>
					</table> -->
						</div>
					</div>
				</div>

				<div class="row"></div>


				<!--main content ends-->
				<?php
}

require_once "includes/html_bottom.inc.php";
?>
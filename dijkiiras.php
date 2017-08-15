<?php
require_once 'includes/connection.inc.php';
is_logged_out ();
require_once 'includes/html_top.inc.php';
require_once 'includes/menu.inc.php';
require_once 'includes/PHPExcel.php';
require_once 'includes/dbservice.inc.php';

if ($_SESSION ['jog']['hallgato_penzugy'] != "1") {
	echo '<div class="content">
        <div class="main-content">Nincs jogosultságod ehhez a művelethez.</div></div>';
	require_once "includes/html_bottom.inc.php";
	die ();
}





//connect to db
		$dbh = connectToDB();
		if(!$dbh){
			echo '<div class="content">
        <div class="main-content">Adatbázis kapcsolat nem jött létre"</div></div>';
			require_once "includes/html_bottom.inc.php";
			die ();
		}


	//if the form was submitted

	$koll = getDorms($dbh);
	
	?>
	
	<script type="text/javascript">
        jQuery( document ).ready(function() {

        	jQuery('body').on('click', '#dijkiiras', function () {
        		dijkiiras();
            	});
            });
        </script>
	<div class="content">
	<div class="main-content">
	
	<form id="kolidijform" method="post" enctype="multipart/form-data">
		<div class="row">
		<div class="col-sm-6 col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading no-collapse">Lista típusa</div>
					<div class="panel-body">
						<table class="table">
						<tr><td>Kollégiumi díj</td><td><input type="radio" name="tipus" value="kolidij" class="groupcb1" checked="checked"/></td></tr>
						<tr><td>Késedelmi díj</td><td><input type="radio" name="tipus" value="kesedelmidij" class="groupcb1"/></td></tr>
						<tr><td>Kulturális díj</td><td><input type="radio" name="tipus" value="kulturalisdij" class="groupcb1"/></td></tr>
						<tr><td>Kártérítési díj</td><td><input type="radio" name="tipus" value="karteritesidij" class="groupcb1"/></td></tr>
						<tr><td>Hallgató adatai</td><td><input type="radio" name="tipus" value="hallgatoadatai" class="groupcb1"/></td></tr>
						</table>
					</div>
				</div>
			</div>
		</div>
		
		

		<div class="row">
			<div class="col-sm-6 col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading no-collapse">Célcsoport</div>
				<div class="panel-body">
					<table class="table">
					<tr class="mindentr"><td>Minden hallgató</td><td>
					<select id="koli" name="koli">
					<?php foreach($koll as $k){
        			echo '<option value="'.$k['kollegium_id'].'"';
        			echo '>'.$k['kollegium_nev'].'</option>';
        			}
        			?>
        			</select>
        			</td><td><input type="radio" value="mindenkolis" name="celcsoport" class="groupcb2"  checked="checked"/></td></tr>
        			<tr class="filetr"><td>XLS fileból</td><td><input type="file" name="uploadFile" id="uploadFile"/></td>
        			<td><input type="radio" name="celcsoport" value="xlsfilebol" class="groupcb2"/></td></tr>
        			</table>
			
				</div>

			
			</div>
			<a class="btn btn-primary pull-right" id="dijkiiras">Lista készítése</a>
		</div>
		
		</div>
	</form>
	
	
	<!--main content ends-->
<?php

class SpecialValueBinder extends PHPExcel_Cell_DefaultValueBinder implements PHPExcel_Cell_IValueBinder
{
	public function bindValue(PHPExcel_Cell $cell, $value = null)
	{

		$value = PHPExcel_Shared_String::SanitizeUTF8($value);
		$cell->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
		return true;
	}
}



if(count($_POST)>0){

	echo '<div class="row">';
	echo '<div class="col-sm-6 col-md-6">';

	$tipus = $_POST['tipus'];
	$koli = $_POST['koli'];
	$celcsoport = $_POST['celcsoport'];

	if($tipus == "kolidij"){
		$tipus_sql = "pk.kollegiumi_dij";
		$osszeg = "pk.kollegiumi_dij_osszeg";
	}
	else if($tipus == "kesedelmidij"){
		$tipus_sql = "pk.kesedelmi_dij";
		$osszeg = "pk.kesedelmi_dij_osszeg";
	}//TODO: kulturalis dij beolvasas!
	else if($tipus == "kulturalisdij"){
		$tipus_sql = "pk.kulturalis";
		$osszeg = "pk.kesedelmi_dij_osszeg";
	}//TODO: karteritesi dij beolvasas
	else if($tipus == "karteritesidij"){
		$tipus_sql = "pk.karterites";
		$osszeg = "pk.kesedelmi_dij_osszeg";
	}
	else if($tipus == "hallgatoadatai"){
		$tipus_sql = "pk.kollegiumi_dij";
		$osszeg = "pk.kollegiumi_dij_osszeg";
		
	}
	else{
		die("Hiányzó díj típus!");
	}
	
	
	if ($celcsoport == "mindenkolis"){
        
        $lista = customReport($dbh, $tipus_sql, $osszeg, $koli);

	}
	else if($celcsoport == "xlsfilebol"){
	
		if(count($_FILES)!=0){
			
			$target_dir = "uploads/";
			
			$target_dir = $target_dir . basename( "dij".date("YmdHis").".xls");
			
			
			if (move_uploaded_file($_FILES["uploadFile"]["tmp_name"], $target_dir)) {
				echo '<div class="alert alert-success">A file '. basename( $_FILES["uploadFile"]["name"]). ' sikeresen feltöltve.</div>';
			}
			else{
				die('<div class="alert alert-danger">A file-t nem sikerült feltölteni!</div>');
			}
			
			PHPExcel_Cell::setValueBinder( new SpecialValueBinder() );
			
			$inputFileName = $target_dir;
			
			try {
				$inputFileType = PHPExcel_IOFactory::identify ( $inputFileName );
				$objReader = PHPExcel_IOFactory::createReader ( $inputFileType );
				$objPHPExcel = $objReader->load ( $inputFileName );
			} catch ( Exception $e ) {
				die ( 'Error loading file "' . pathinfo ( $inputFileName, PATHINFO_BASENAME ) . '": ' . $e->getMessage () );
					
			}
			
			$sheet = $objPHPExcel->getSheet(0);
			$highestRow = $sheet->getHighestRow();
			
			$neptunKodok = array();
			for($i=2;$i<=$highestRow;$i++){
					
				$neptunKodok[] = $sheet->getCell('A'.$i)->getValue();
			}
			
			$lista = array();
			
			
			
			foreach($neptunKodok as $nk){
                
                $lista[] = getReportUsingNeptun($dbh, $tipus_sql, $osszeg, $nk);
                

			}
		}
		else{ 
			die('<div class="alert alert-danger">Hiányzó file!</div>');
		}
	}
	else{
		die("Hiányzó célcsoport!");
	}

	if($tipus == "hallgatoadatai"){
        
        
        $res = getStudentDetailsByDormReport($dbh, $koli);
	
	$szobak = array();
	
	foreach($res as $r){
		if($koli == "2"){
			$szobak[$r['hallgato_neptun_kod']]=str_replace("0","/", $r['szoba_szam']);
		}
		else{
			$szobak[$r['hallgato_neptun_kod']]=$r['szoba_szam'];
		}
		
	}
		
	}
	
	
	
	
	
	
	
	
	//create xls file

	//var_dump($lista);


	PHPExcel_Cell::setValueBinder( new SpecialValueBinder() );

	
	
	if($tipus == "hallgatoadatai"){
		$inputFileName = "templates/hallgatolista.xls";
	}
	else{
		$inputFileName = "templates/kolidij.xls";
	}
	

	try {
		$inputFileType = PHPExcel_IOFactory::identify ( $inputFileName );
		$objReader = PHPExcel_IOFactory::createReader ( $inputFileType );
		$objPHPExcel = $objReader->load ( $inputFileName );
	} catch ( Exception $e ) {
		echo ( 'Error loading file "' . pathinfo ( $inputFileName, PATHINFO_BASENAME ) . '": ' . $e->getMessage () );
		require_once "includes/html_bottom.inc.php";
		die();
	}
	$row = 2;
	foreach($lista as $l){
		if($tipus == "hallgatoadatai"){
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A'.$row, $l['hallgato_neptun_kod'])
			->setCellValue('B'.$row, $l['hallgato_neve'])
			->setCellValue('C'.$row, $szobak[$l['hallgato_neptun_kod']])
			->setCellValue('D'.$row, $l['hallgato_email'])
			->setCellValue('E'.$row, $l['hallgato_telefon'])
			->setCellValue('F'.$row, $l['hallgato_lakcim'])
			->setCellValue('G'.$row, $l['hallgato_allampolgarsag'])
			->setCellValue('H'.$row, $l['penzugyikod'])
			->setCellValue('I'.$row, $l['osszeg']);
		}
		else{
			if(empty($l['penzugyikod'])) continue;
			
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A'.$row, $l['hallgato_neptun_kod'])
			->setCellValue('C'.$row, $l['penzugyikod'])
			->setCellValue('E'.$row, $l['osszeg']);
		}
		$row++;
	}
	$fileName = $lista[0]['kolinev'].date("YmdHis").".xls";

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
	$objWriter->save("dijkiirasok/".$fileName);


	echo '<div class="alert alert-success">Siker. Kattints a letöltéshez <a href="dijkiirasok/'.$fileName.'">IDE</a></div>';


echo '</div></div>';
}









require_once "includes/html_bottom.inc.php";
?>
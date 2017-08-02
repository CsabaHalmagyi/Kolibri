<?php
require_once 'includes/connection.inc.php';
is_logged_out ();
require_once 'includes/html_top.inc.php';
require_once 'includes/menu.inc.php';
require_once 'includes/PHPExcel.php';
require_once 'settings/db.php';


try {
	$dbh = new PDO ( "mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
} catch ( PDOException $e ) {
	echo '<div class="content">
        <div class="main-content">Adatbázis kapcsolat nem jött létre: ' . $e->getMessage ();
	die ();
	require_once "includes/html_bottom.inc.php";
}

$dbh->exec ( "SET CHARACTER SET utf8" );


$sql = 'SELECT * FROM kolibri_felvettek WHERE kollegium_id="1"';
$sth = $dbh->prepare ( $sql );
$sth->execute ();
$vpk_felvettek = $sth->fetchAll ( PDO::FETCH_ASSOC );

$sql = 'SELECT * FROM kolibri_felvettek WHERE kollegium_id = "2"';
$sth = $dbh->prepare ( $sql );
$sth->execute ();
$asd_felvettek= $sth->fetchAll ( PDO::FETCH_ASSOC );

$sql = 'SELECT * FROM kolibri_szoba_definiciok';
$sth = $dbh->prepare ( $sql );
$sth->execute ();
$szoba_definiciok = $sth->fetchAll ( PDO::FETCH_ASSOC );

$vpk_max_ferohely = 0;
$vpk_szabad_ferohely = 0;

$asd_max_ferohely = 0;
$asd_szabad_ferohely = 0;


foreach($szoba_definiciok as $sz){
	
	if($sz['kollegium_id']=="1"){
		$vpk_max_ferohely += intval($sz['max_ferohely']);
		$vpk_szabad_ferohely += intval($sz['szabad_ferohely']);
	}
	else if($sz['kollegium_id']=="2"){
		$asd_max_ferohely += intval($sz['max_ferohely']);
		$asd_szabad_ferohely += intval($sz['szabad_ferohely']);
	}
}

$vpk_szobaba_beosztott = 0;
$vpk_szoba_nelkul = 0;
foreach($vpk_felvettek as $f){
	if($f['szobaba_beosztva'] == "1") {
		$vpk_szobaba_beosztott++;
	}
	else{
		$vpk_szoba_nelkul++;
	}
}

$asd_szobaba_beosztott = 0;
$asd_szoba_nelkul = 0;
foreach($asd_felvettek as $f){
	if($f['szobaba_beosztva'] == "1") {
		$asd_szobaba_beosztott++;
	}
	else{
		$asd_szoba_nelkul++;
	}
}



$vpk_most = $vpk_max_ferohely - $vpk_szabad_ferohely;
$vpk_feltoltottseg = $vpk_most*1.0/$vpk_max_ferohely*100;
$vpk_szoba_nelkul_szazalek = $vpk_szoba_nelkul/($vpk_szoba_nelkul+$vpk_szobaba_beosztott)*100;
$vpk_szobaba_beosztott_szazalek = 100-$vpk_szoba_nelkul_szazalek;



$asd_most = $asd_max_ferohely - $asd_szabad_ferohely;
$asd_feltoltottseg = $asd_most*1.0/$asd_max_ferohely*100;
$asd_szoba_nelkul_szazalek = $asd_szoba_nelkul/($asd_szoba_nelkul+$asd_szobaba_beosztott)*100;
$asd_szobaba_beosztott_szazalek = 100-$asd_szoba_nelkul_szazalek;

?>


<!--main content starts-->
<div class="content">
	<div class="main-content">
	
		<div class="row">
			<div class="col-sm-6 col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading no-collapse">
						Veres Péter Kollégium
					</div>
					<div class="panel-body">
					<table class="table">
					<tr><td>Összes férőhely</td><td><?php echo $vpk_max_ferohely;?></td></tr>
					<tr><td>Kollégiumba felvett hallgató</td><td><?php echo count($vpk_felvettek);?></td></tr>
					<tr><td>Elfoglalt férőhely</td><td><?php echo $vpk_most;?></td></tr>
					<tr><td>Szabad férőhely</td><td><?php echo $vpk_szabad_ferohely;?></td></tr>
					</table>
					Feltöltöttség:
					<div class="progress">
  						<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $vpk_most;?>"
  						aria-valuemin="0" aria-valuemax="100" style="width:<?php echo number_format($vpk_feltoltottseg,0);?>%">
    					<?php echo number_format($vpk_feltoltottseg,0);?>%
  						</div>
					</div>
					<?php if(count($vpk_felvettek)>0){?>
					Szobához hozzárendelt / szoba nélkül
					<div class="progress">
					
					  <div class="progress-bar progress-bar-success" role="progressbar" 
					  style="width:<?php echo number_format($vpk_szobaba_beosztott_szazalek,0);?>%">
    					<?php echo number_format($vpk_szobaba_beosztott_szazalek,0)."%";?>
					  </div>
  					<div class="progress-bar progress-bar-warning" role="progressbar" style="width:<?php 
  					echo number_format($vpk_szoba_nelkul_szazalek,0);?>%">
    				<?php echo number_format($vpk_szoba_nelkul_szazalek,0)."%";?>
  					</div>
					</div>
					<?php }?>
					
					
					
				</div>
			</div>
			</div>
			<div class="col-sm-6 col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading no-collapse">
						Arany Sándor Diákapartman
					</div>
					<div class="panel-body">
					<table class="table">
					<tr><td>Összes férőhely</td><td><?php echo $asd_max_ferohely;?></td></tr>
					<tr><td>Kollégiumba felvett hallgató</td><td><?php echo count($asd_felvettek);?></td></tr>
					<tr><td>Elfoglalt férőhely</td><td><?php echo $asd_most;?></td></tr>
					<tr><td>Szabad férőhely</td><td><?php echo $asd_szabad_ferohely;?></td></tr>
					</table>
					Feltöltöttség:
					<div class="progress">
  						<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $vpk_most;?>"
  						aria-valuemin="0" aria-valuemax="100" style="width:<?php echo number_format($asd_feltoltottseg,0);?>%">
    					<?php echo number_format($asd_feltoltottseg,0);?>%
  						</div>
					</div>
					<?php if(count($asd_felvettek)>0){?>
					Szobához hozzárendelt / szoba nélkül
					<div class="progress">
					
					  <div class="progress-bar progress-bar-success" role="progressbar" 
					  style="width:<?php echo number_format($asd_szobaba_beosztott_szazalek,0);?>%">
    					<?php echo number_format($asd_szobaba_beosztott_szazalek,0)."%";?>
					  </div>
  					<div class="progress-bar progress-bar-warning" role="progressbar" style="width:<?php 
  					echo number_format($asd_szoba_nelkul_szazalek,0);?>%">
    				<?php echo number_format($asd_szoba_nelkul_szazalek,0)."%";?>
  					</div>
					</div>
					<?php }?>
					</div>
				</div>
			</div>
			
		</div>		
	</div>
</div>








		<!--main content ends-->
<?php
require_once "includes/html_bottom.inc.php";
?>

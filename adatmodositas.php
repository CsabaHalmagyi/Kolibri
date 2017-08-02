<?php
require_once 'includes/connection.inc.php';
is_logged_out ();
require_once 'settings/db.php';
require_once "includes/html_top.inc.php";
require_once "includes/menu.inc.php";

if ($_SESSION ['jog'] ['hallgato_adatmodositas'] != "1") {
	echo '<div class="content">
        <div class="main-content">Nincs jogosultságod ehhez a művelethez.</div></div>';
	require_once "includes/html_bottom.inc.php";
	die ();
}

if (isset ( $_GET ['id'] )) {
	
	$hallgid = intval ( $_GET ['id'] );
	
	try {
		$dbh = new PDO ( "mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
	} catch ( PDOException $e ) {
		echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage ();
		die ();
	}
	
	$dbh->exec ( "SET CHARACTER SET utf8" );
	$dbh->exec ( "SET collation_connection = 'utf8_hungarian_ci'" );
	
	$sql = 'SELECT *
				FROM kolibri_hallgatok
				WHERE hallgato_id = :hallgid';
	
	$sth_hallgato = $dbh->prepare ( $sql );
	$sth_hallgato->bindParam ( ':hallgid', $hallgid );
	$sth_hallgato->execute ();
	
	$hallgato = $sth_hallgato->fetch ( PDO::FETCH_ASSOC );
	
	if (strlen ( $hallgato ['hallgato_neptun_kod'] ) != 6) {
		
		echo '<div class="content">
        <div class="main-content">Ismeretlen hallgató.</div></div>';
		require_once "includes/html_bottom.inc.php";
		die ();
	}
	
	$sql = "SELECT kolibri_kollegiumok.kollegium_id, kolibri_kollegiumok.kollegium_nev
			FROM kolibri_felvettek
			INNER JOIN kolibri_kollegiumok
			ON kolibri_kollegiumok.kollegium_id = kolibri_felvettek.kollegium_id
			WHERE kolibri_felvettek.tanev_id = :tanev
			AND kolibri_felvettek.hallgato_id = :hallgato";
	
	$sth_koli = $dbh->prepare ( $sql );
	$sth_koli->bindParam ( ':tanev', $_SESSION ['beallitasok'] ['aktualis_tanev_id'] );
	$sth_koli->bindParam ( ':hallgato', $hallgid );
	$sth_koli->execute ();
	$koli = $sth_koli->fetch ( PDO::FETCH_ASSOC );
	
	$koliID = $koli ['kollegium_id'];
	
	if(empty($koliID)){
		echo '<div class="content">
        <div class="main-content">A hallgató nincs a felvettek listájában, így adatai nem módosíthatóak.</div></div>';
		require_once "includes/html_bottom.inc.php";
		die ();
	}
	
	
	
	
	$sql = "SELECT * FROM kolibri_penzugyi_kodok
			WHERE kollegium_id = :kollid";
	
	$sth_penzugy = $dbh->prepare ( $sql );
	$sth_penzugy->bindParam ( ':kollid', $koliID );
	$sth_penzugy->execute ();
	$penzugy = $sth_penzugy->fetchAll ( PDO::FETCH_ASSOC );
}

?>
<script type="text/javascript">
        jQuery( document ).ready(function() {

        	jQuery('body').on('click', '.updateStudent', function () {
        		var id = this.id;
        		updateStudent(id);
            	});

            });
        </script>

<div class="content">
	<div class="main-content">


		<div class="row">
			<div class="col-sm-6 col-md-6">
				<div id="ujHallgatoDiv1">
					<div class="panel panel-default">
						<div class="panel-heading no-collapse">
							Személyes adatok
						</div>
						<div class="panel-body">
							<form>
								<div class="form-group">
									<label>Neptun kód*</label> <input id="neptunkod"
										class="form-control span12" type="text" value="<?php echo $hallgato['hallgato_neptun_kod'];?>" disabled="">
								</div>
								<div class="form-group">
									<label>Név*</label> <input id="nev" class="form-control span12"
										type="text" value="<?php echo $hallgato['hallgato_neve'];?>">
								</div>
								<div class="form-group">
									<label>Email</label> <input id="email"
										class="form-control span12" type="text" value="<?php echo $hallgato['hallgato_email'];?>">
								</div>
								<div class="form-group">
									<label>Telefon</label> <input id="telefon"
										class="form-control span12" type="text" value="<?php echo $hallgato['hallgato_telefon'];?>">
								</div>
								<div class="form-group">
									<label>Lakcím</label>
									<textarea id="lakcim" class="form-control" rows="2"><?php echo $hallgato['hallgato_lakcim'];?></textarea>
								</div>
								<div class="form-group">
									<label>Állampolgárság</label> <input id="allampolgarsag"
										class="form-control span12" type="text" value="<?php echo $hallgato['hallgato_allampolgarsag'];?>">
								</div>
								<div class="form-group">
									<label>Képzési forma</label> <input id="kepzesiforma"
										class="form-control span12" type="text" value="<?php echo $hallgato['hallgato_kepzesi_forma'];?>">
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
			<div class="col-sm-6 col-md-6">
				<div id="ujHallgatoDiv2">
					<div class="panel panel-default">
						<div class="panel-heading no-collapse">Kollégiumi adatok</div>
						<div class="panel-body">
							<form>
								<div class="form-group">
									<label>Felvéve*</label> <select id="felveve"
										class="form-control">
										<option><?php echo $koli['kollegium_nev']?></option>
									</select>
								</div>
								<div class="form-group">
									<label>Pénzügyi kód*</label> <select id="penzugyikod"
										class="form-control">
										<?php 
										foreach($penzugy as $p){
											if($hallgato['hallgato_penzugyi_kod'] == $p['pk_id']){
												echo '<option value="'.$p['pk_id'].'" selected="selected">'.$p['kollegiumi_dij'].'</option>';
											}
											else{
												echo '<option value="'.$p['pk_id'].'">'.$p['kollegiumi_dij'].'</option>';
											}
										}
										
										if($hallgato['hallgato_penzugyi_kod'] == "0"){
											echo '<option value="0" selected="selected">Nincs, a hallgató nem a Neptunban fizet</option>';
										}
										else{
											echo '<option value="0">Nincs, a hallgató nem a Neptunban fizet</option>';
										}
										
										?>
									</select>
								</div>
								<div class="form-group">
									<label>Tanév*</label> <input id="tanev"
										class="form-control span12" type="text" disabled="" 
										value="<?php echo $_SESSION['beallitasok']['tanev_nev'];?>">
								</div>
								<div class="form-group">
									<a id="updatestudent_<?php echo $hallgato['hallgato_id'];?>" class="btn btn-primary pull-right updateStudent"
										href="#">Mentés!</a>
								</div>
							</form>
						</div>
					</div>
				</div>
				<div id="responseMessageDIV">
				</div>
			</div>
		</div>
        

</div>
</div>
<!--main content ends-->
<?php
require_once "includes/html_bottom.inc.php";
?>
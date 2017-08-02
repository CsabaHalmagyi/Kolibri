<?php
require_once 'includes/connection.inc.php';
is_logged_out ();
require_once 'includes/html_top.inc.php';
require_once 'includes/menu.inc.php';
require_once 'includes/PHPExcel.php';
require_once 'settings/db.php';

if ($_SESSION ['jog'] ['admin'] != "1") {
	echo '<div class="content">
        <div class="main-content">Nincs jogosultságod ehhez a művelethez.</div></div>';
	require_once "includes/html_bottom.inc.php";
	die ();
}

try {
	$dbh = new PDO ( "mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
} catch ( PDOException $e ) {
	echo '<div class="content">
        <div class="main-content">Adatbázis kapcsolat nem jött létre: ' . $e->getMessage ();
	die ();
	require_once "includes/html_bottom.inc.php";
}

$dbh->exec ( "SET CHARACTER SET utf8" );

$sql = 'SELECT * FROM kolibri_beallitasok';

$sth = $dbh->prepare ( $sql );
$sth->execute ();
$beallitasok = $sth->fetch ( PDO::FETCH_ASSOC );

$sql = "SELECT * FROM kolibri_tanevek";
$sth = $dbh->prepare ( $sql );
$sth->execute ();
$tanevek = $sth->fetchAll ( PDO::FETCH_ASSOC );

$sql = 'SELECT * FROM kolibri_felvettek WHERE szobaba_beosztva = "1"';
$sth = $dbh->prepare ( $sql );
$sth->execute ();
$felvettek_szobaval = $sth->fetchAll ( PDO::FETCH_ASSOC );

$sql = 'SELECT * FROM kolibri_felvettek WHERE szobaba_beosztva = "0"';
$sth = $dbh->prepare ( $sql );
$sth->execute ();
$felvettek_szoba_nelkul = $sth->fetchAll ( PDO::FETCH_ASSOC );


?>


<!--main content starts-->
<div class="content">
	<div class="main-content">
	<script type="text/javascript">
        jQuery( document ).ready(function() {

        	jQuery('body').on('click', '#tanevvaltas', function () {
        		tanevvaltas();
            	});
        	jQuery('body').on('click', '#tanevzaras', function () {
        		tanevzaras();
            	});


            });
        </script>
	
	
	
		<div class="row">
			<div class="col-md-4">
			
			<h3>KOLIBRI Beállítások</h3>
				<table class="table">
					<tr>
						<td>Tanév váltás</td>
						<td><select class="form-control" id="AktualisFelev"
							name="AktualisFelev">
			<?php
			
			foreach ( $tanevek as $t ) {
				
				echo '<option value="' . $t ['tanev_id'] . '" ';
				
				if ($t ['tanev_id'] == $_SESSION ['beallitasok'] ['aktualis_tanev_id']) {
					echo 'selected="selected" ';
				}
				echo '>' . $t ['tanev_nev'] . '</option>';
			}
			?>
            </select></td>
						<td><button 
							class="btn btn-primary <?php
							if (count ( $felvettek_szobaval ) > 0 || count ( $felvettek_szoba_nelkul ) > 0)
								echo 'disabled';
							?>"
							href="" id="tanevvaltas">Mentés</button></td>
					</tr>
					<tr>
						<td>Tanév lezárása</td>
						<td></td>
						<td><button 
							class="btn btn-primary <?php
							if (count ( $felvettek_szobaval ) > 0)
								echo 'disabled';
							?>"
							href="" id="tanevzaras">Lezárás</button></td>
					</tr>
				</table>

			</div>
		</div>








		<!--main content ends-->
<?php
require_once "includes/html_bottom.inc.php";
?>

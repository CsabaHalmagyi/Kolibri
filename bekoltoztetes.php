<?php
require_once 'includes/connection.inc.php';
is_logged_out();
require_once 'settings/db.php';
require_once "includes/html_top.inc.php";
require_once "includes/menu.inc.php";


?>
<script type="text/javascript">
jQuery( document ).ready(function() {

jQuery( 'body' ).on('keyup', '#hallgatoNeveKeres', function () {
	loadHallgato();
	});

jQuery('body').on('click', '.hallgatorow', function () {
	var id = this.id;
	getHallgatoAdatai(id);
	});

jQuery('body').on('click', '.jogviszony_letrehozasa', function () {
	jQuery(this).prop('disabled', true);
	var id = this.id;
	hallgatoJogviszonyLetrehoz(id);
	});

jQuery('body').on('click', '.jogviszony_megszuntetese', function () {
	jQuery(this).prop('disabled', true);
	var id = this.id;
	hallgatoJogviszonyMegszuntet(id);
	});


});
</script>

    <div class="content">
        <div class="main-content">
        
        
<?php 

if($_SESSION['jog']['bekoltoztetes'] != "1"){
	
	echo 'Nincs jogosultságod ehhez a művelethez.</div></div>';
	require_once "includes/html_bottom.inc.php";
	die();
}


try {
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
}catch (PDOException $e) {
	$message = "Adatbázis hiba - ".$e->getMessage();
	die($message);
}

$dbh -> exec("SET CHARACTER SET utf8");
$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");

$sql = 'SELECT * FROM kolibri_kollegiumok';

$sth = $dbh->prepare($sql);
$sth->execute();

$kollegiumok = $sth->fetchAll(PDO::FETCH_ASSOC);

if(count($kollegiumok) == 0) die("Nincs kollégium definiálva az adatbázisban");

if(isset($_GET['kollegium'])){
	$kollID = $_GET['kollegium'];
}
else{
	$kollID = $kollegiumok[0]['kollegium_id'];

}





?>        
                <div class="row">
        	<div class="col-sm-6 col-md-6">
        		<div class="panel panel-default">
					<div class="panel-heading no-collapse">
							Kollégium
							<select id="koliSelect">
						
							<?php 
							if (isset($_GET['kollegium'])){
								$kid = $_GET['kollegium'];
							}
							foreach($kollegiumok as $koll){
								 
								echo '<option value="'.$koll['kollegium_id'].'"';
							
								if($koll['kollegium_id'] == $kid){
									echo ' selected="selected"';
								}
								echo '>'.$koll['kollegium_nev'].'</option>';
							}
							?>
							</select> 
					</div>
					<div>
					<table class="table">
						<tbody>
							<tr><td>Hallgató neve</td><td><input type="text" id="hallgatoNeveKeres" class="form-control"/></td></tr>
						</tbody>
					</table>	
					</div>
				</div>
				<div id="bekoltoztetLista">
				</div>
        	</div>
        	<div class="col-sm-6 col-md-6">
        	<div id="hallgatoAdatai"></div>
        	</div>	
        </div>
    </div>
 </div>   
  
<!--main content ends-->
<?php 
require_once "includes/html_bottom.inc.php";
?>      
        
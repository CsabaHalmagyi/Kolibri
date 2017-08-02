<?php
require_once 'includes/connection.inc.php';
is_logged_out();
require_once 'settings/db.php';
require_once "includes/html_top.inc.php";
require_once "includes/menu.inc.php";
?>
<!--main content starts-->
<script type="text/javascript">
        jQuery( document ).ready(function() {

       	jQuery('body').on('click', '.kartya_visszavetel', function () {
        		var id = this.id;
        		kartyaVisszavetel(id);
            	});

        	jQuery('body').on('click', '.kartya_kiadas', function () {
        		var id = this.id;
        		kartyaKiadas(id);
            	});
        	

            });
        </script>

    <div class="content">
        <div class="main-content">
<?php 

function displayKartyak($hallgato,$kartyak){
	echo '<div class="row">';
	echo '<div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xs-offset-0 col-sm-offset-0 col-md-offset-3 col-lg-offset-3 toppad">';
	?>
		<div class="panel panel-info">
			<div class="panel-heading">
			<h3 class="panel-title">
			<b><?php echo $hallgato['hallgato_neve'];?></b>
			</h3>
			</div>
			<div class="panel-body">
			<div class="row">
				<div class="col-md-12 col-lg-12" align="center">
				<table class="table table-bordered"><thead><tr><th>Kártya</th><th>Felvétel dátuma</th><th>Kiadás/Visszavétel</th><tbody>
				<?php 
				foreach($kartyak as $k){
					echo '<tr><td>'.$k['kartya_szam'].'</td><td>'.$k['felvetel_datuma'].'</td><td><button id="kartyabejegyzes_'.$k['kartya_bejegyzes_id'].
					'" class="btn btn-primary kartya_visszavetel" type="button">Kártyát visszavesz</button></td></tr>';
				}
				?>
				<tr><td><input class="form-control" id="ujkartyaszam"/></td><td></td><td><button id="kartya_<?php echo $hallgato['hallgato_id'];?>" 
				class="btn btn-primary kartya_kiadas" type="button">Kártyát kiad</button></td></tr>				
				</tbody>
				</table>
				</div></div></div></div>
	
	<?php 
	echo '<div id="responseMessageDIV">
	</div>';
	
	echo '</div></div>';
}



if(isset($_GET['id'])){

	$hallgid = intval($_GET['id']);

	try {
		$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
	}catch (PDOException $e) {
		echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
		die();
	}

	$dbh -> exec("SET CHARACTER SET utf8");
	$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");

	$sql = 'SELECT *
				FROM kolibri_hallgatok
				WHERE hallgato_id = :hallgid';

	$sth_hallgato = $dbh->prepare($sql);
	$sth_hallgato->bindParam(':hallgid', $hallgid);
	$sth_hallgato->execute();

	$hallgato = $sth_hallgato->fetch(PDO::FETCH_ASSOC);


	if($sth_hallgato){

		//get kartya adatok
		$sql = 'SELECT *
        		FROM kolibri_belepokartyak kb
				WHERE kb.hallgato_id = :hallgid
    			AND kb.leadas_datuma = "0000-00-00 00:00:00"
				ORDER BY kb.felvetel_datuma';

		$sth_kartyak = $dbh->prepare($sql);
		$sth_kartyak->bindParam(':hallgid', $hallgid);
		$sth_kartyak->execute();

		$kartyak = $sth_kartyak->fetchAll(PDO::FETCH_ASSOC);

		//$sql = 'SELECT * FROM ';



		displayKartyak($hallgato,$kartyak);
	}


	require_once "includes/html_bottom.inc.php";


}
?>








<!--main content ends-->
<?php
require_once "includes/html_bottom.inc.php";
?>
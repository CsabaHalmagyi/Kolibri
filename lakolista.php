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

        	jQuery( 'body' ).on('change', '#koliSelect', function () {
        		koliSelect();
            	});

            });
        </script>

    <div class="content">
        <div class="main-content">

<?php 

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

	if (isset($_GET['kollegium'])){
		$kid = intval($_GET['kollegium']);
	}
	else{
		$kid = $kollegiumok[0]['kollegium_id'];
	}
	
	$sql = 'SELECT kolibri_hallgatok.hallgato_id, 
				kolibri_hallgatok.hallgato_neptun_kod, 
				kolibri_hallgatok.hallgato_neve,
				kolibri_szoba_definiciok.szoba_szam,
				kolibri_szoba_reszletek.bekoltozes_datuma, 
				kolibri_kollegiumok.kollegium_rovid_nev
			FROM kolibri_szoba_reszletek
			INNER JOIN 
			kolibri_hallgatok
			ON kolibri_szoba_reszletek.hallgato_id = kolibri_hallgatok.hallgato_id
			INNER JOIN
			kolibri_szoba_definiciok
			ON kolibri_szoba_reszletek.szoba_id = kolibri_szoba_definiciok.szoba_def_id
			INNER JOIN kolibri_kollegiumok
			ON kolibri_szoba_reszletek.kollegium_id = kolibri_kollegiumok.kollegium_id		
			WHERE kolibri_szoba_reszletek.kikoltozes_datuma = "0000-00-00 00:00:00"
			AND kolibri_szoba_reszletek.kollegium_id = :kollid
			AND kolibri_szoba_reszletek.tanev_id = :akttanev
			ORDER BY kolibri_szoba_definiciok.szoba_szam';
	
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':kollid', $kid);
	$sth->bindParam(':akttanev', $_SESSION['beallitasok']['aktualis_tanev_id']);
	$sth->execute();
	
	$lakok = $sth->fetchAll(PDO::FETCH_ASSOC);
	
	$sql_k = 'SELECT hallgato_id, kartya_szam FROM kolibri_belepokartyak
			WHERE leadas_datuma = "0000-00-00 00:00:00"';
	
	$sth_k = $dbh->prepare($sql_k);
	$sth_k->execute();
	$kartyak = $sth_k->fetchAll(PDO::FETCH_ASSOC);
	

	
?>

        <div class="row">
        	<div class="col-sm-6 col-md-6">
        		<select id="koliSelect">
        		<?php 

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
        </div>
	<div class="row">
	<div class="col-sm-8 col-md-8">
		<div class="panel panel-default">
			<div class="panel-heading no-collapse">
			Kollégium lakók
		</div>
	
	<table class="table table-bordered" id="lakolistaTable">
	<thead><tr><th>Név</th><th>Neptun kód</th><th>Szoba</th><th>Kártyaszám</th><th>Státusz</th></tr></thead>
	<?php 
	foreach($lakok as $l){
		if($l['bekoltozes_datuma'] == "0000-00-00 00:00:00"){
			echo '<tr class="bg-warning">';
		}
		else{
			echo '<tr class="bg-success">';
		}
		
		echo '<td><a href="hallgatok.php?id='.$l['hallgato_id'].'"><b>'.$l['hallgato_neve'].'</b></a></td><td>'.$l['hallgato_neptun_kod'].'</td><td>';
		
		if($l['kollegium_rovid_nev'] == "ASD"){
			echo str_replace("0","/", $l['szoba_szam']);
		}
		else{
			echo $l['szoba_szam'];
		}
		
		echo '</td><td>';
		$h_kartya = "";
		
		foreach($kartyak as $k){
			if($k['hallgato_id'] == $l['hallgato_id']){
				if(strlen($h_kartya) == 0){
					$h_kartya.=$k['kartya_szam'];
				}
				else{
					$h_kartya.= ', '.$k['kartya_szam'];
				}
				
			}
		}
		
		echo $h_kartya.'</td>';
		
		if($l['bekoltozes_datuma'] == "0000-00-00 00:00:00"){
			echo '<td>Szobához rendelve</td>';
		}
		else{
			echo '<td>Beköltözött</td>';
		}
		echo '</tr>';
	}
	
	?>
	</table>
	</div>
	</div>
	
	<div class="col-sm-4"></div>
	</div>
	
	
	
	
	
	
	
<!--main content ends-->
<?php 
require_once "includes/html_bottom.inc.php";
?>
	
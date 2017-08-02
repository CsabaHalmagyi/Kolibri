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
    
      
        <?php 
        	$szint = 0;
    		$elsoSzint = true;    	
        	foreach ($lakok as $l){
        		$szoba = intval($l['szoba_szam']);
        		$aktSzint = intval($szoba/100);
        		

        		
        		
        		
        		if($aktSzint != $szint){
        			$szint = $aktSzint;
        			
        			if($elsoSzint){
        				$elsoSzint = false;
        				
        				echo '<div class="panel panel-default">';
        				echo '<p class="panel-heading">';
        				echo $aktSzint;
        					if($l['kollegium_rovid_nev'] == "ASD") {
        						echo '. lépcsőház';
        					}
        					else{
        						echo '. szint';
        					}
        				echo '</p>';
        				echo '<div class="panel-body gallery">';
        				
        			}
        			else{
        				
        				echo '</div></div>';
        				echo '<div class="panel panel-default">';
        				echo '<p class="panel-heading">';
        				echo $aktSzint;
        				
        					if($l['kollegium_rovid_nev'] == "ASD") {
        						echo '. lépcsőház';
        					}
        					else{
        						echo '. szint';
        					}
        				echo '</p>';
        				echo '<div class="panel-body gallery">';
        			}
        		}
        		
        		$kep=strtoupper(htmlspecialchars($l['hallgato_neptun_kod'])).".JPG";
        		$kep="photos/".$kep;
        		if (!is_file($kep)) $kep="photos/NINCS.JPG";
        		
        		echo '<a href="hallgatok.php?id='.$l['hallgato_id'].'">';
        		echo '<img class="img-thumbnail" src="'.$kep.'" width="320" height="240"/>';
        		echo '</a>';
        		
        	}
        
        
        ?>
        
        </div></div>
	

	
	
	
	
	
	
	
<!--main content ends-->
<?php 
require_once "includes/html_bottom.inc.php";
?>
	
<?php
require_once 'includes/connection.inc.php';
is_logged_out();
require_once 'includes/dbservice.inc.php';
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

	$dbh = connectToDB();
	if(!$dbh){
		echo 'Adatbázis kapcsolat nem jött létre"</div></div>';
		require_once "includes/html_bottom.inc.php";
		die ();
	}

	$kollegiumok = getDorms($dbh);

	if(count($kollegiumok) == 0) die("Nincs kollégium definiálva az adatbázisban");

	if (isset($_GET['kollegium'])){
		$kid = intval($_GET['kollegium']);
	}
	else{
		$kid = $kollegiumok[0]['kollegium_id'];
	}

	$lakok = getAllStudentsFromEnrollmentList($dbh, $kid);

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

	</div>
</div>


<!--main content ends-->
		<?php
		require_once "includes/html_bottom.inc.php";
		?>

<?php
require_once 'includes/connection.inc.php';
is_logged_out();
require_once 'includes/dbservice.inc.php';
require_once "includes/html_top.inc.php";
require_once "includes/menu.inc.php";


if($_SESSION['jog']['bekoltoztetes'] != "1"){

	echo '<div class="content">
        <div class="main-content">Nincs jogosultságod ehhez a művelethez.</div></div>';
	require_once "includes/html_bottom.inc.php";
	die();
}

?>


<!--main content starts-->
<script type="text/javascript">
        jQuery( document ).ready(function() {

        	jQuery('body').on('click', '.vanhely', function () {
        		var id = this.id;
        		loadRoomData(id);
            	});
        	jQuery('body').on('click', '.nincshely', function () {
        		var id = this.id;
        		loadRoomData(id);
            	});

        	jQuery( 'body' ).on('change', '#koliSelect', function () {
        		fetchRooms();
            	});

        	jQuery("body").on("keyup", ":input", function() {
        		jQuery(this).attr('autocomplete', 'off');
        		var idcomp = this.id;
        		var idcomp = idcomp.split("_");
       			loadHallgatoWithoutRoom(jQuery(this).val(),idcomp[1]);
        	});
        	jQuery('body').on('click', '.bekoltoztet', function () {
        		var id = this.id;
        		jQuery(this).prop('disabled', true);
        		hallgatoSzobahozRendel(id);
            	});

        	jQuery('body').on('click', '.kikoltoztet', function () {
        		var id = this.id;
        		jQuery(this).prop('disabled', true);
        		hallgatoSzobabolKivesz(id);
            	});
        	
        	
			//getDorms();        	
			

            });
        </script>

<div class="content">
	<div class="main-content">

	<?php

	//ACTION: getRooms
	//


	$dbh = connectToDB();
	if(!$dbh){
		echo 'Adatbázis kapcsolat nem jött létre"</div></div>';
		require_once "includes/html_bottom.inc.php";
		die ();
	}


	$kollegiumok = getDorms($dbh);

	if(count($kollegiumok) == 0) die("Nincs kollégium definiálva az adatbázisban");

	if(isset($_GET['kollegium'])){
		$kollID = $_GET['kollegium'];
	}
	else{
		$kollID = $kollegiumok[0]['kollegium_id'];
	}

	$szobak = getAllRoomsInDorm($dbh, $kollID);

	if($szobak){
			
		$szobakRendezett = array();
		$max_em = 0;
		for($i=0;$i<count($szobak);$i++){
			$szobakRendezett[intval($szobak[$i]['szoba_szam']) % 100][$szobak[$i]['szoba_szam']] = array(
					"szobaid"=>$szobak[$i]['szoba_def_id'],
					"szobaszam"=>$szobak[$i]['szoba_szam'],
					"maxferohely"=>$szobak[$i]['max_ferohely'],
					"szabadferohely"=>$szobak[$i]['szabad_ferohely'],
					"koll_nev"=>$szobak[$i]['kollegium_rovid_nev']

			);
			$max_em = max($max_em, intval($szobak[$i]['szoba_szam'][0]));
		}
			
		//printResponse(0,$message,$szobakRendezett,null);
			
		$responseTable = "";

		foreach ($szobakRendezett as $pozicio=>$szint_szoba ) {
			$responseTable.= '<tr>';
			foreach ( $szint_szoba as $key=>$szoba ) {

				$szobaszam = $szoba['szobaszam'];
				//apartman check:
				if($szoba['koll_nev']=="ASD"){
					$szobaszam = str_replace("0","/",$szobaszam);
				}
					
				$max = intval($szoba['maxferohely']);
				$akt = intval($szoba['szabadferohely']);

				if ($szoba['maxferohely']=="0") {
					$responseTable.= '<td class="inaktivszoba"><b>'.$szobaszam.'</b></td>';
				}
				else {
					$responseTable.= '<td id="szobaTd_'.$szoba['szobaid'].'"';
					if ($akt == 0){
						$responseTable.=' class="nincshely"';
					}


					else {
						$responseTable.= ' class="vanhely"';
					}
					$responseTable.='><b>'.$szobaszam.'</b> ('.$akt.')</td>';


				}
			}
			$responseTable.= '</tr>';

		}
	}
	else{
		die("Szobák lekérdezése sikertelen.");
	}




	?>

		<div class="row">
			<div class="col-sm-6 col-md-6">
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
		</div>

		<div class="row">
			<div class="col-sm-6 col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading no-collapse">Szobák</div>
					<table class="table table-bordered" id="koliTable">
					<?php
					echo $responseTable;
					?>
					</table>
				</div>
			</div>
			<div class="col-sm-6 col-md-6" id="szobaLakokPanel">
				<!--         <div class="panel panel-default">
          <div class="panel-heading no-collapse">Részletek</div>
          	<div id="szobaLakokDIV"></div>
        </div>
        
        <div class="panel panel-default" id="hallgatokSzobaNelkulPanel">
          	<div class="panel-heading no-collapse">Felvett hallgatók szoba nélkül</div>
          	<div id="hallgatokSzobaNelkulDIV"></div>
        </div> -->
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6 col-md-6">
				<table class="table">
					<tr>
						<td>Szabad férőhelyek: <span class="label vanhely"> </span></td>
						<td>Szoba tele: <span class="label nincshely"> </span></td>
						<td>Inaktív szoba:<span class="label inaktivszoba"> </span></td>
						<td>Hallgató, kollégiumi jogviszony:<span class="fa fa-check"> </span>
						</td>
						<td>Hallgató, NINCS kollégiumi jogviszony:<span
							class="fa fa-exclamation"> </span></td>

					</tr>
				</table>
			</div>
		</div>






		<!--main content ends-->
		<?php
		require_once "includes/html_bottom.inc.php";
		?>
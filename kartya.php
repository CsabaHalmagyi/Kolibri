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
					<b><?php echo $hallgato['hallgato_neve'];?> </b>
				</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12 col-lg-12" align="center">
						<table class="table table-bordered">
							<thead>
								<tr>
									<th>Kártya</th>
									<th>Felvétel dátuma</th>
									<th>Kiadás/Visszavétel</th>
							
							
							<tbody>
							<?php
							foreach($kartyak as $k){
								echo '<tr><td>'.$k['kartya_szam'].'</td><td>'.$k['felvetel_datuma'].'</td><td><button id="kartyabejegyzes_'.$k['kartya_bejegyzes_id'].
					'" class="btn btn-primary kartya_visszavetel" type="button">Kártyát visszavesz</button></td></tr>';
							}
							?>
								<tr>
									<td><input class="form-control" id="ujkartyaszam" /></td>
									<td></td>
									<td><button id="kartya_<?php echo $hallgato['hallgato_id'];?>"
											class="btn btn-primary kartya_kiadas" type="button">Kártyát
											kiad</button></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

		<?php
		echo '<div id="responseMessageDIV">
	</div>';

		echo '</div></div>';
	}



	if(isset($_GET['id'])){

		$hallgid = intval($_GET['id']);

		$dbh = connectToDB();
		if(!$dbh){
			echo '<div class="content">
        <div class="main-content">Adatbázis kapcsolat nem jött létre"</div></div>';
			require_once "includes/html_bottom.inc.php";
			die ();
		}

		$hallgato = getStudentByID($dbh, $hallgid);

		if($hallgato){

			//get kartya adatok
			$kartyak = getStudentActiveCards($dbh, $hallgid);

			displayKartyak($hallgato,$kartyak);
		}

		require_once "includes/html_bottom.inc.php";
	}
	?>

		<!--main content ends-->
	<?php
	require_once "includes/html_bottom.inc.php";
?>
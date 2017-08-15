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

        	jQuery('body').on('click', '.hallgato_adatmodositas', function () {
        		var id = this.id;
        		hallgatoAdatmodositasRedirect(id);
            	});

        	jQuery('body').on('click', '.hallgato_kartya', function () {
        		var id = this.id;
        		hallgatoKartyaRedirect(id);
            	});

        	jQuery( 'body' ).on('keyup', '#keresoszo', function () {
        		hallgatoKeres();
        		});
        	
        	jQuery('#keresoszo').focus();

            });
        </script>

    <div class="content">
        <div class="main-content">
<?php 

function displayHallgato($hallgato,$adatok,$kartyak){
	
	if($hallgato == null){
		$hallgato = array("hallgato_neve"=>"Ismeretlen hallgató");
	}
	echo '<div class="row"><div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xs-offset-0 col-sm-offset-0 
		col-md-offset-3 col-lg-offset-3 toppad" >';
	echo '<div class="panel panel-info">
            <div class="panel-heading">
              <h3 class="panel-title"><b>'.$hallgato['hallgato_neve'].'</b></h3>
            </div>';
	echo '<div class="panel-body">
              <div class="row">
                <div class="col-md-12 col-lg-12" align="center">';
	if ($hallgato['hallgato_neptun_kod'] != null && file_exists('photos/'.$hallgato['hallgato_neptun_kod'].'.JPG')){
		echo'<img alt="User Pic" src="photos/'.$hallgato['hallgato_neptun_kod'].'.JPG'.'" class="img-thumbnail img-responsive">';
	}
	else{
		echo'<img alt="User Pic" src="photos/NINCS.JPG'.'" class="img-thumbnail img-responsive">';
	}
	echo ' </div></div><div class="row"><div class=" col-md-12 col-lg-12 ">
	<table class="table table-user-information">
	<tbody>
	<tr>
	<td>Neptun kód:</td>
	<td>'.$hallgato['hallgato_neptun_kod'].'</td>
	</tr>';

	if($adatok[0]['tanev_id']==$_SESSION['beallitasok']['aktualis_tanev_id']){
		echo '<tr>
				<td>Épület:</td>
				<td>'.$adatok[0]['kollegium_rovid_nev'].'</td>
			</tr>';
		if($adatok[0]['kollegium_rovid_nev'] == "ASD"){
			$szoba = str_replace("0","/", $adatok[0]['szoba_szam']);
				echo '<tr>
					<td>Szoba:</td>
					<td>'.$szoba.'</td>
				</tr>';
				
		}
		else{
			echo '<tr>
					<td>Szoba:</td>
					<td>'.$adatok[0]['szoba_szam'].'</td>
				</tr>';
				
		}
		
		if($adatok[0]['bekoltozes_datuma'] == "0000-00-00 00:00:00"){
			echo '<tr>
				<td>Státusz:</td>
				<td>Szobához rendelve</td>
			</tr>';
		}
		else if($adatok[0]['bekoltozes_datuma'] != "0000-00-00 00:00:00" && $adatok[0]['kikoltozes_datuma'] == "0000-00-00 00:00:00"){
			echo '<tr>
				<td>Státusz:</td>
				<td>Beköltözött</td>
			</tr>';
		}
		else if($adatok[0]['kikoltozes_datuma'] != "0000-00-00 00:00:00"){
			echo '<tr>
				<td>Státusz:</td>
				<td>Kiköltözött ekkor '.$adatok[0]['kikoltozes_datuma'].'</td>
			</tr>';
		}  
	}
	
	if($_SESSION['jog']['hallgato_telefonszam'] == "1"){
		echo '<tr>
	<td>Telefon:</td>
	<td>'.$hallgato['hallgato_telefon'].'</td>
	</tr>';
	}

	if($_SESSION['jog']['hallgato_cim'] == "1"){
		echo '<tr>
	<td>Email:</td>
	<td>'.$hallgato['hallgato_email'].'</td>
	</tr>';
	}

	if($_SESSION['jog']['hallgato_cim'] == "1"){
		echo '<tr>
	<td>Lakcím:</td>
	<td>'.$hallgato['hallgato_lakcim'].'</td>
	</tr>';
	}
	
	echo '<tr>
	<td>Állampolgárság:</td>
	<td>'.$hallgato['hallgato_allampolgarsag'].'</td>
	</tr>';
	
	if($_SESSION['jog']['hallgato_adatmodositas'] == "1"){
		echo '<tr>
	<td>Képzés:</td>
	<td>'.$hallgato['hallgato_kepzesi_forma'].'</td>
	</tr>';
	}	
	
	echo '</tbody></table>';
                  
	if($_SESSION['jog']['hallgato_adatmodositas']){
		echo '<p>';
		echo '<span class="label"><a href="#" class="btn btn-primary hallgato_adatmodositas" id="adatmod_'.$hallgato['hallgato_id'].'">Hallgató adatmódosítás</a></span>';
		echo '<span class="label"><a href="#" class="btn btn-primary hallgato_kartya" id="kartya_'.$hallgato['hallgato_id'].'">Belépőkártya</a></span>';
		echo '</p>';
	}
	
	echo '</div></div></div></div></div></div>';
	
	echo '<div class="row"><div class="col-sm-12">';
	
	echo '<div class="col-sm-6">';
	echo '<div class="panel panel-default"><a class="panel-heading" data-toggle="collapse" href="#hallgatotortenete">Hallgató története </a>';
	echo '<div id="hallgatotortenete" class="panel-body collapse" style="height: auto;">';
	echo '<table class="table table-bordered"><thead><th>Tanév</th><th>Épület</th><th>Szoba</th><th>Beköltözött</th><th>Kiköltözött</th></thead>';
	echo '<tbody>';
	
	foreach($adatok as $a){
		echo '<tr><td>'.$a['tanev_nev'].'</td><td>'.$a['kollegium_rovid_nev'].'</td><td>';
		
		if($a['kollegium_rovid_nev'] == "ASD"){
			echo str_replace("0","/", $a['szoba_szam']);
		}
		else{
			echo $a['szoba_szam'];
		}
		echo '</td><td>';
		
		if($a['bekoltozes_datuma'] == "0000-00-00 00:00:00"){
			echo '';
		}
		else{
			echo $a['bekoltozes_datuma'];	
		}
		echo '</td><td>';
		
		if($a['kikoltozes_datuma'] == "0000-00-00 00:00:00"){
			echo '';
		}
		else{
			echo $a['kikoltozes_datuma'];
		}
		echo '</td><tr>';
		
	}
			
	echo '</tbody></table>';		
	echo '</div></div></div>';
	
	echo '<div class="col-sm-6">';
	echo '<div class="panel panel-default"><a class="panel-heading" data-toggle="collapse" href="#hallgatokartyai">Hallgató kártyái </a>';
	echo '<div id="hallgatokartyai" class="panel-body collapse" style="height: auto;">';
	echo '<table class="table table-bordered"><thead><th>Kártyaszám</th><th>Felvétel dátuma</th><th>Leadás dátuma</th></thead>';
	echo '<tbody>';
	
	foreach($kartyak as $k){
		echo '<tr><td>'.$k['kartya_szam'].'</td><td>'.$k['felvetel_datuma'].'</td><td>';
		if($k['leadas_datuma'] == "0000-00-00 00:00:00"){
			echo '';
		}
		else{
			echo $k['leadas_datuma'];	
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	
	echo '</div></div></div>';
	
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
	
	if(hallgato){
		
		//get koll adatok
        $adatok = getStudentRoomHistory($dbh, $hallgid);

		$kartyak = getStudentCardHistory($dbh, $hallgid);

        displayHallgato($hallgato,$adatok,$kartyak,null);
	}
	else{
		displayHallgato(null,null,null);
	}
	
	
	require_once "includes/html_bottom.inc.php";


}
else{
?>

<div class="row">
<div id="keresobox" style=" margin-top:5px" class="mainbox col-md-8 col-md-offset-1 col-sm-8 col-sm-offset-2">
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="panel-title">Hallgató kereső</div>
            </div>  
            <div class="panel-body" >
                <form method="post" action=".">
                    <input type='hidden' name='csrfmiddlewaretoken' value='XFe2rTYl9WOpV8U6X5CfbIuOZOELJ97S' />

                    <form  class="form-horizontal" method="post" >
                        <input type='hidden' name='csrfmiddlewaretoken' value='XFe2rTYl9WOpV8U6X5CfbIuOZOELJ97S' />
                        
		                        <div id="div_id_celcsoport" class="form-group required">
                            <label for="id_select"  class="control-label col-md-4  requiredField"> Célcsoport<span class="asteriskField">*</span> </label>
                            <div class="controls col-md-8 "  style="margin-bottom: 10px">
                                <label class="radio-inline"><input type="radio" checked="checked" name="celcsoport" value="Aktualis"  style="margin-bottom: 10px">Aktuális tanévre felvett hallgató</label><br/>
                                <label class="radio-inline"> <input type="radio" name="celcsoport" value="Osszes"  style="margin-bottom: 10px">Összes hallgató</label><br/>
                            </div>
                        </div> 
                        <div id="div_id_mire" class="form-group required">
                            <label for="id_As"  class="control-label col-md-4  requiredField">Mire keres?<span class="asteriskField">*</span> </label>
                            <div class="controls col-md-8 "  style="margin-bottom: 10px">
                                <label class="radio-inline"> <input type="radio" checked="checked" name="mire" value="Nev"  style="margin-bottom: 10px">Hallgató nevére </label><br/>
                                <label class="radio-inline"> <input type="radio" name="mire" value="Neptun"  style="margin-bottom: 10px">Hallgató Neptun kódjára </label><br/>
                                <label class="radio-inline"> <input type="radio" name="mire" value="Kartya"  style="margin-bottom: 10px">Aktív kártyára </label><br/>
                            </div>
                        </div>
                        <div id="div_id_kereso" class="form-group required">
                            <label for="id_username" class="control-label col-md-4  requiredField"> Keresőszó<span class="asteriskField">*</span> </label>
                            <div class="controls col-md-8 ">
                                <input class="input-md  textinput textInput form-control" id="keresoszo" maxlength="30" name="keresoszo" placeholder="Keresőszó" style="margin-bottom: 10px" type="text" />
                            </div>
                        </div>

                    </form>

                </form>
            </div>
        </div>
        
        <div id="keresoEredmeny"></div>
        
    </div> 
</div>
    
    </div>




<!--main content ends-->
<?php

}
require_once "includes/html_bottom.inc.php";
?>
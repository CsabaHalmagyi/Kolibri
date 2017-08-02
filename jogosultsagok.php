<?php
require_once 'includes/connection.inc.php';
is_logged_out();
require_once "includes/html_top.inc.php";
require_once "includes/menu.inc.php";
require_once 'settings/db.php';


if($_SESSION['jog']['admin'] != "1"){
	echo '<div class="content">
        <div class="main-content">Nincs jogosultságod ehhez a művelethez.</div></div>';
	require_once "includes/html_bottom.inc.php";
	die();
} 

try {
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
}catch (PDOException $e) {
	echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
	die();
}

$dbh -> exec("SET CHARACTER SET utf8");

$sql = "SELECT * FROM kolibri_jogcsoportok ORDER BY id";

$sth = $dbh->prepare($sql);
$sth->execute();

$groups = $sth->fetchAll(PDO::FETCH_ASSOC);


?>


<!--main content starts-->
    <div class="content">
        <div class="main-content">
        <script type="text/javascript">
        jQuery( document ).ready(function() {

        	jQuery('body').on('click', '#addNewGroup', function () {
        		addNewGroup();
            	});
        	jQuery('body').on('click', '#cancelNewGroup', function () {
        		cancelNewGroup();
        		});
        	jQuery('body').on('click', '#createNewGroup', function () {
        		createNewGroup();
            	});
        	jQuery('body').on('click', '.deleteGroup', function () {
        		var id = this.id;
        		deleteGroup(id);
            	});

        	jQuery('body').on('click', '.editGroup', function () {
        		var id = this.id;
        		editGroup(id);
            	});

        	jQuery('body').on('click', '.updateGroup', function () {
        		var id = this.id;
        		updateGroup(id);
            	});

        	jQuery('body').on('click', '.cancelUpdateGroup', function () {
        		var id = this.id;
        		cancelUpdateGroup(id);
            	});        	
        	
        });


        </script>
        
        
        
        <div class="panel panel-default">
            <div class="panel-heading no-collapse">Jogosultságok</div>
            <table class="table table-bordered table-striped" id="jogosultsagtabla">
              <thead>
                <tr>
                  <th>Csoport név</th>
                  <th>H. adatmód.</th>
                  <th>H. név/szoba</th>
                  <th>H. tel</th>
                  <th>H. cím</th>
                  <th>H. pénzügy</th>
                  <th>Igazolás</th>
                  <th>Beköltöztetés</th>
                  <th>Lakólista</th>
                  <th>Statisztika</th>
                  <th>Admin</th>
                  <th></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              
              <?php
              foreach ($groups as $g){
              	$id = $g['id'];
              	
              	echo '<tr id="row'.$id.'"><td>'.$g['csoportnev'].'</td>';
              	
              	echo '<td><input disabled type="checkbox" id="'.$id.'_1" ';
              	if($g['hallgato_adatmodositas'] == "1") echo 'checked';
              	echo '/></td>';
              	
              	echo '<td><input disabled type="checkbox" id="'.$id.'_2" ';
              	if($g['hallgato_nev_szoba'] == "1") echo 'checked';
              	echo '/></td>';

              	echo '<td><input disabled type="checkbox" id="'.$id.'_3" ';
              	if($g['hallgato_telefonszam'] == "1") echo 'checked';
              	echo '/></td>';

              	echo '<td><input disabled type="checkbox" id="'.$id.'_4" ';
              	if($g['hallgato_cim'] == "1") echo 'checked';
              	echo '/></td>';

              	echo '<td><input disabled type="checkbox" id="'.$id.'_5" ';
              	if($g['hallgato_penzugy'] == "1") echo 'checked';
              	echo '/></td>';

              	echo '<td><input disabled type="checkbox" id="'.$id.'_6" ';
              	if($g['igazolas'] == "1") echo 'checked';
              	echo '/></td>';

              	echo '<td><input disabled type="checkbox" id="'.$id.'_7" ';
              	if($g['bekoltoztetes'] == "1") echo 'checked';
              	echo '/></td>';

              	echo '<td><input disabled type="checkbox" id="'.$id.'_8" ';
              	if($g['lakolista'] == "1") echo 'checked';
              	echo '/></td>';

              	echo '<td><input disabled type="checkbox" id="'.$id.'_9" ';
              	if($g['statisztika'] == "1") echo 'checked';
              	echo '/></td>';

              	echo '<td><input disabled type="checkbox" id="'.$id.'_10" ';
              	if($g['admin'] == "1") echo 'checked';
              	echo '/></td>';
              	
              	if($g['csoportnev']=="Admin"){
              		echo '<td colspan="2"></td>';
              		
              	}
              	else{
              		echo '<td id="edit_td_'.$id.'"><a href="#" id="edit_'.$id.'" class="editGroup"><i class="fa fa-pencil"></i></a></td>';
              		echo '<td id="delete_td_'.$id.'"><a href="#" id="delete_'.$id.'" class="deleteGroup"><i class="fa fa-trash-o"></i></a></td>';
              	}
              	 
              	echo '</tr>';

              	
              }
              ?>
              </tbody>
            </table>
            
        </div>    
		<a class="btn btn-primary pull-right" href="#" id="addNewGroup">Új csoport</a>
        

<!--main content ends-->
<?php 
require_once "includes/html_bottom.inc.php";
?>

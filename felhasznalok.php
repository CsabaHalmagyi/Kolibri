<?php
require_once 'includes/connection.inc.php';
is_logged_out();
require_once "includes/html_top.inc.php";
require_once "includes/menu.inc.php";

?>


<!--main content starts-->
    <div class="content">
        <div class="main-content">
                <script type="text/javascript">
        jQuery( document ).ready(function() {

        	jQuery('body').on('click', '.editUser', function () {
        		var id = this.id;
        		editUser(id);
            	});
        	jQuery('body').on('click', '.deleteUser', function () {
        		var id = this.id;
        		deleteUser(id);
            	});

        	jQuery('body').on('click', '#cancelSaving', function () {
        		getUsersFromDB();
            	});
        	jQuery('body').on('click', '#saveUser', function () {
        		updateUser();
            	});        	

        	jQuery('body').on('click', '#createNewUser', function () {
        		createNewUser();
            	});        	
        	
        	
        	getUsersFromDB();
            });
        </script>
        <div id="felhasznalokDiv">
        
<!--         <div class="panel panel-default">
            <div class="panel-heading no-collapse">Felhasználók</div>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Felhasználónév</th>
                  <th>Vezetéknév</th>
                  <th>Keresztnév</th>
                  <th>Jogosultság</th>
                  <th>Létrehozva</th>
                  <th>Utolsó belépés</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Andi</td>
                  <td>Szemán</td>
                  <td>Gáborné</td>
                  <td><select><option>Admin</option><option selected>Igazgató</option><option>Recepció</option></select></td>
                  <td>2016-03-30</td>
                  <td>2016-03-30</td>
                </tr>
                <tr>
                  <td>Pityu</td>
                  <td>Kovács</td>
                  <td>István</td>
                  <td><select><option>Admin</option><option>Igazgató</option><option selected>Recepció</option></select></td>
                  <td>2016-03-30</td>
                  <td>2016-03-30</td>
                </tr>
              </tbody>
            </table>
        </div>    
		<a class="btn btn-primary pull-right" href="">Mentés</a>
 -->		
        </div>

<!--main content ends-->
<?php 
require_once "includes/html_bottom.inc.php";
?>

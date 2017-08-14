<?php
require_once 'includes/connection.inc.php';
require_once 'includes/dbservice.inc.php';

is_logged_in();

//if the login form was submitted

if (count($_POST)!=0) {
	//create an empty error array
	$error = array();

	//check if the posted data meets the requirements
	if ((strlen($_POST['felhasznalonev'])<=3)) $error[]="A felhasználónév túl rövid!";


	//if the form was filled properly
	if (count($error)==0) {
		$md5pass=md5($_POST['jelszo']);
		$user = $_POST['felhasznalonev'];

		$dbh = connectToDB();
		if(!$dbh){
			echo '<div class="content">
        <div class="main-content">Adatbázis kapcsolat nem jött létre"</div></div>';
			require_once "includes/html_bottom.inc.php";
			die ();
		}

		$result = getUser($dbh, $user);
		
		if ($result['jelszo'] == $md5pass){
			if ($result['aktiv'] == "1"){
				$_SESSION['felhasznalo']=$result;
				$userID = $result['felhasznalo_id'];
				$lastLogin = date('Y-m-d H:i:s');
				$group = $result['csoport'];
				
				updateUserLastLogin($dbh, $userID);

				//read group permissions
				$_SESSION['jog'] = getGroupPermissions($dbh, $group);

				redirect("index.php");
			}
			else {
				$error[5] = "Ez a felhasználó le van tiltva.";
			}
		}
		else{
			$error[4]='Hibás felhasználónév vagy jelszó!';
		}
	}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<title>KOLIBRI 2.0 Bejelentkezés</title>
<meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="author" content="Csaba Halmagyi">

<!-- <link href="css/css.css" rel="stylesheet" type="text/css"> -->
<link rel="stylesheet" type="text/css" href="css/bootstrap.css">


<script src="js/jquery-1.js" type="text/javascript"></script>



<link rel="stylesheet" type="text/css" href="css/theme.css">

</head>
<body class=" theme-green">

	<!-- Demo page code -->

	<style type="text/css">
#line-chart {
	height: 300px;
	width: 800px;
	margin: 0px auto;
	margin-top: 1em;
}

.navbar-default .navbar-brand,.navbar-default .navbar-brand:hover {
	color: #fff;
}
</style>


	<div class="navbar navbar-default" role="navigation">
		<div class="navbar-header">
			<a class="" href=""><span class="navbar-brand">KOLIBRI</span> </a>
		</div>

		<div class="navbar-collapse collapse" style="height: 1px;"></div>
	</div>




	<div class="dialog">
		<div class="panel panel-default">
			<p class="panel-heading no-collapse">KOLIBRI Bejelentkezés</p>
			<div class="panel-body">
				<form action="bejelentkezes.php" method="POST">
					<div class="form-group">
						<label>Felhasználónév</label> <input class="form-control span12"
							type="text" id="felhasznalonev" name="felhasznalonev">
					</div>
					<div class="form-group">
						<label>Jelszó</label> <input
							class="form-control span12 form-control" type="password"
							id="jelszo" name="jelszo">
					</div>
					<!-- 					<a href="index.php" class="btn btn-primary pull-right">Bejelentkezés</a> -->
					<input type="submit" value="Bejelentkezés" class="btn btn-primary" />
					<div class="clearfix"></div>
				</form>
				<?php if(count($error)>0){

					foreach($error as $e){
						echo '<div class="alert-error">';
						echo $e;
						echo '</div>';
					}

				}
				?>
			</div>
		</div>

	</div>

	<?php

	?>
	<footer>
		<hr>


		<p class="pull-right">KOLIBRI @ Halmágyi Csaba, Varga Imre 2011 - 2016</p>
		<div style="clear: both;"></div>
	</footer>
</body>
</html>

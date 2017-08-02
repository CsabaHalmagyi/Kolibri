<?php
require_once 'includes/connection.inc.php';
is_logged_out();
require_once 'settings/db.php';
require_once 'includes/html_top.inc.php';
require_once 'includes/menu.inc.php';



try {
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
}catch (PDOException $e) {
	echo 'Adatbázis kapcsolat nem jött létre: ' . $e->getMessage();
}

$dbh -> exec("SET CHARACTER SET utf8");
$dbh -> exec("SET collation_connection = 'utf8_hungarian_ci'");

$sql = 'SELECT kolibri_beallitasok.*, kolibri_tanevek.tanev_nev
				FROM kolibri_beallitasok INNER JOIN kolibri_tanevek
				ON kolibri_beallitasok.aktualis_tanev_id=kolibri_tanevek.tanev_id';

$sth = $dbh->prepare($sql);
$sth->execute();
$beallitasok = $sth->fetchAll(PDO::FETCH_ASSOC);

$_SESSION['beallitasok'] = $beallitasok[0];
?>

<!--main content starts-->
    <div class="content">

<!--         <div class="header">
            <div class="stats">
    <p class="stat"><span class="label label-info">5</span> Tickets</p>
    <p class="stat"><span class="label label-success">27</span> Tasks</p>
    <p class="stat"><span class="label label-danger">15</span> Overdue</p>
</div>

            <h1 class="page-title">Dashboard</h1>
                    <ul class="breadcrumb">
            <li><a href="index.html">Home</a> </li>
            <li class="active">Dashboard</li>
        </ul>

        </div>
 -->        
        <div class="main-content">
<?php 
var_dump($_SESSION);

?>            


<div class="row">
    <div class="col-md-2 col-md-offset-3">
	<br/><br/>
<img src="templates/vpkasda.jpg" class="img-rounded"/>    
    </div>
</div>

<!--main content ends-->
<?php 
require_once "includes/html_bottom.inc.php";
?>

<?php
header("Content-Type: text/html; charset=utf-8");

?>
<!DOCTYPE html>
<html lang="hu"><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <title>KOLIBRI 2.0</title>
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Csaba Halmagyi">
<!--     <link href="css/css.css" rel="stylesheet" type="text/css"> -->
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/kolibri.css">
    

    <script src="js/jquery-1.js" type="text/javascript"></script>

    <script src="js/jquery.js" type="text/javascript"></script>
    <script src="js/jquery-ui.min.js" type="text/javascript"></script>
    <script src="js/bootstrap.js" type="text/javascript"></script>
    <?php 
    $module = basename($_SERVER["SCRIPT_FILENAME"], '.php');
    $jsFile = 'kolibri_'.$module.'.js';
    
    if(is_file('./js/'.$jsFile)){
    	echo '<script src="js/'.$jsFile.'" type="text/javascript"></script>'; 
    }
    ?>
    
        
    <link rel="stylesheet" type="text/css" href="css/theme.css">

</head>
<body class=" theme-green">
    <style type="text/css">
        #line-chart {
            height:300px;
            width:800px;
            margin: 0px auto;
            margin-top: 1em;
        }
        .navbar-default .navbar-brand, .navbar-default .navbar-brand:hover { 
            color: #fff;
        }
    </style>

    <div class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
          <a class="" href="index.php"><span class="navbar-brand">KOLIBRI</span></a></div>

        <div class="navbar-collapse collapse" style="height: 1px;">
          <ul id="main-menu" class="nav navbar-nav navbar-right">
            <li class="dropdown hidden-xs">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <span class="padding-right-small" style="position:relative;top: 3px;"></span> Felhasználó: <b> <?php echo $_SESSION['felhasznalo']['felhasznalonev']; ?></b>
                </a>
            </li>


        </div>
      </div>


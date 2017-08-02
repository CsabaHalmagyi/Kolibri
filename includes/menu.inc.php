            <!--html_menu starts-->

    <div class="sidebar-nav">
    <ul>
    	<li><a href="index.php" class="nav-header"><span class="fa fa-flash"> </span><b> Főoldal</b></a></li>
        <li><a href="hallgatok.php" class="nav-header"><span class="fa fa-user"> </span><b> Hallgatók</b></a></li>
        <li><ul class="nav nav-list collapse in">
           	<li <?php if($_SESSION['jog']['bekoltoztetes'] != "1") echo 'class="disabled"';?>>
           		<a href="<?php if($_SESSION['jog']['bekoltoztetes'] == "1") echo 'ujhallgato.php';?>"><span class="fa fa-caret-right"></span>Új hallgató</a></li>
            <li <?php if($_SESSION['jog']['igazolas'] != "1") echo 'class="disabled"';?>>
            <a href="<?php if($_SESSION['jog']['igazolas'] == "1") echo '';?>"><span class="fa fa-caret-right"></span>Igazolások</a></li>
	</ul></li>
    
    <li><a href="kollegium.php" class="nav-header"><span class="fa fa-building"> </span><b> Kollégium</b></a></li>
    <li><ul class="nav nav-list collapse in">
	<li <?php if($_SESSION['jog']['bekoltoztetes'] != "1") echo 'class="disabled"';?>><a href="<?php if($_SESSION['jog']['bekoltoztetes'] == "1") echo 'szobabeosztas.php';?>"><span class="fa fa-caret-right"></span>Szobabeosztás</a></li>
    <li <?php if($_SESSION['jog']['bekoltoztetes'] != "1") echo 'class="disabled"';?>><a href="<?php if($_SESSION['jog']['bekoltoztetes'] == "1") echo 'bekoltoztetes.php';?>"><span class="fa fa-caret-right"></span>Be/Kiköltöztetés</a></li>
    <li><a href="lakolista.php"><span class="fa fa-caret-right"></span>Lakólista</a></li>
	
	<li <?php if($_SESSION['jog']['hallgato_penzugy'] != "1") echo 'class="disabled"';?>><a href="<?php if($_SESSION['jog']['hallgato_penzugy'] == "1") echo 'dijkiiras.php';?>"><span class="fa fa-caret-right"></span>Díj kiírás</a></li>
	
    </ul></li>
	    
     <li><a href="#" class="nav-header"><span class="fa fa-bar-chart-o"> </span><b> Statisztika</b></a></li>
        <li><ul class="nav nav-list collapse in">
            <li <?php if($_SESSION['jog']['statisztika'] != "1") echo 'class="disabled"';?>><a href=""><span class="fa fa-caret-right"></span>Stat1</a></li>
    </ul></li>
	
	    
	
    <li><a href="#" class="nav-header"><span class="fa fa-gear"> </span><b> Admin</b></a></li>
        <li><ul class="nav nav-list collapse in">
            <li <?php if($_SESSION['jog']['admin'] != "1") echo 'class="disabled"';?>><a href="<?php if($_SESSION['jog']['admin'] == "1") echo 'felhasznalok.php';?>"><span class="fa fa-caret-right"></span>Felhasználók</a></li>
            <li <?php if($_SESSION['jog']['admin'] != "1") echo 'class="disabled"';?>><a href="<?php if($_SESSION['jog']['admin'] == "1") echo 'jogosultsagok.php';?>"><span class="fa fa-caret-right"></span>Jogosultságok</a></li>
            <li <?php if($_SESSION['jog']['admin'] != "1") echo 'class="disabled"';?>><a href="<?php if($_SESSION['jog']['admin'] == "1") echo '';?>"><span class="fa fa-caret-right"></span>Napló</a></li>
            <li <?php if($_SESSION['jog']['admin'] != "1") echo 'class="disabled"';?>><a href="<?php if($_SESSION['jog']['admin'] == "1") echo 'beallitasok.php';?>"><span class="fa fa-caret-right"></span>Beállítások</a></li>
    </ul></li>
	

        <li><a href="kijelentkezes.php" class="nav-header"><span class="fa fa-sign-out"> </span><b> Kijelentkezés</b></a></li>
                            
            </ul>
    </div>

<!--html_menu ends-->

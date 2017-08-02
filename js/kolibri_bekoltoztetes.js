/**
 * KOLIBRI Javascript Functions
 * 
 */

function loadHallgato() {

	var koll = jQuery("#koliSelect").children(":selected").val();
	var hallg = jQuery("#hallgatoNeveKeres").val();
	
	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "hallgatoBeKikoltoztetLista",
			hallgato : hallg,
			kollegium : koll
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			displayStudents(data.data);
		} 
		else {
			if(data.errorCode == 1000){
				location.reload();
			}
			else{
				console.log("Hiba: " + data.message);	
			}
			
		}
	}).fail(function() {
		console.log("Error");
	});
}





function displayStudents(data){
	var hallgatoLista = jQuery("#bekoltoztetLista");
	hallgatoLista.empty();
	hallgatoLista.append(data);
	
}

function getHallgatoAdatai(hid){

	var koll = jQuery("#koliSelect").children(":selected").val();
	var hallg = hid.split("_");
	
	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "hallgatoBeKikoltoztetAdat",
			hallgato : hallg[1],
			kollegium : koll
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			displayHallgatoAdatai(data.data);
		}
		else {
			if(data.errorCode == 1000){
				location.reload();
			}
			else{
				console.log("Hiba: " + data.message);	
			}
		}

	}).fail(function() {
		console.log("Error");
	});
	
}


function displayHallgatoAdatai(data){
	var hallgatoAdatai = jQuery("#hallgatoAdatai");
	hallgatoAdatai.empty();
	hallgatoAdatai.append(data);
}

function disableButton(reszletek_id){
	var button = jQuery("#"+reszletek_id);
	button.addClass("disabled");
}


function hallgatoJogviszonyLetrehoz(reszletek_id){

	var reszl = reszletek_id.split("_");
	
	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "hallgatoJogviszonyLetrehoz",
			reszletek : reszl[1]
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			disableButton(reszletek_id);
			alert("Kollégiumi jogviszony létrehozva!");
			
		}
		else {
			if(data.errorCode == 1000){
				location.reload();
			}
			else{
				console.log("Hiba: " + data.message);	
			}
			
		}
	}).fail(function() {
		console.log("Error");
	});
	
}


function hallgatoJogviszonyMegszuntet(hallgato_id){

	var hallg = hallgato_id.split("_");
	
	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "hallgatoJogviszonyMegszuntet",
			hallgato : hallg[1]
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			alert("Kollégiumi jogviszony megszüntetve!");
			location.reload(); 
			
		}
		else {
			if(data.errorCode == 1000){
				location.reload();
			}
			else{
				console.log("Hiba: " + data.message);	
			}
			
		}
	}).fail(function() {
		console.log("Error");
	});
	
}


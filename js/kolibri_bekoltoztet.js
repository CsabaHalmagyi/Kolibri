/**
 * KOLIBRI Javascript Functions
 * 
 */


/*
 * bekoltoztet.php
 * WS: hallg.php
 * WS: koll.php
 */

//GLOBALS
var Kollegiumok = [];
var Szobak = [];

function displayBuildings(){
	
}

function getDorms(){
	var jqxhr = jQuery.ajax({
		url : "ws/koll.php",
		type : "post",
		data : {
			action : "getDorms",
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			//adding data to globals
			Kollegiumok = data.data;
			updateKoliSelect();
			
		} else {
			console.log("Hiba: " + data.message);
		}
	}).fail(function() {
		console.log("Error");
	});
}

function updateKoliSelect(){
	var koliSelect = jQuery("#koliSelect");
	var koliLista = '';
	
	var aktKollId = -1;
	for(i=0;i<Kollegiumok.length;i++){
		if(aktKollId == -1) {
			aktKollId = Kollegiumok[i].kollegium_id;
		}
		koliLista += '<option value="'+Kollegiumok[i].kollegium_id+'">'+Kollegiumok[i].kollegium_nev+'</option>';
	}
	
	koliSelect.append(koliLista);
}




function getRooms(){
	var kollegium = "";
	alert();
}

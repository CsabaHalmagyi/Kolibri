/**
 * KOLIBRI Javascript Functions
 * 
 */

/*
 * bekoltoztet.php WS: hallg.php WS: koll.php
 */

// GLOBALS
var Kollegiumok = [];
var Szobak = "";
var aktSzoba = "";
var aktSzobaID = "";
var HallgatoLista = [];



function fetchRooms() {

	var koliID = jQuery("#koliSelect").children(":selected").val();

	window.location.href = "szobabeosztas.php?kollegium=" + koliID;

}



function loadRoomData(szobaid) {

	//keret eltavolitasa, ha van mashol
	jQuery("td").removeClass("activeRoom");
	
	
	var res = szobaid.split("_");
	var szid = res[1];
	var koliID = jQuery("#koliSelect").children(":selected").val();

	var szobaTD = jQuery("#"+szobaid);
	szobaTD.addClass("activeRoom");
	
	var szobacomp = szobaTD.text();
	var szobacomp2 = szobacomp.split("(");	
	
	var jqxhr = jQuery.ajax({
		url : "ws/koll.php",
		type : "post",
		data : {
			action : "getRoomDetails",
			koliID : koliID,
			szobaID : szid
		}
	}).done(function(data) {
		if (data.errorCode == 0) {

			if (jQuery("#" + szobaid).hasClass("vanhely")) {

				// displaying room details
				displayRoomDetails(data.data, szid, true, szobacomp2[0]);
				displayAvailableStudents(data.data2, szid)
			} else {
				displayRoomDetails(data.data, szid, false, szobacomp2[0]);

			}

		}
		else if(data.errorCode == 1000){
			location.reload();
		} 
		else {
			console.log("Hiba: " + data.message);
		}
	}).fail(function() {
		console.log("Error");
	});

}

function displayRoomDetails(data, szid, vanszabadhely,szobaszam) {

	var lakokPanel = jQuery("#szobaLakokPanel");
	lakokPanel.empty();

	var panel1 = jQuery("<div/>");
	panel1.addClass("panel panel-default");

	var panel1heading = jQuery("<div/>");
	panel1heading.addClass("panel-heading no-collapse");
	panel1heading.text("Szoba lakói - "+szobaszam);
	panel1.append(panel1heading);

	var lakokDiv = jQuery("<div/>");
	lakokDiv.attr("id", "szobaLakokDIV");

	var lakokTable = jQuery("<table>");
	lakokTable.addClass("table table-bordered");

	var koliID = jQuery("#koliSelect").children(":selected").val();
	
	for (i = 0; i < data.length; i++) {
		var tr = jQuery('<tr/>');

		var link = jQuery("<a>");
		link.text("Szobából eltávolít");
		link.addClass("kikoltoztet");
		link.attr("id", "kikoltoztet_" + koliID + "_" + szid + "_" + data[i].hallgato_id);
		
		tr.append("<td>" + data[i].hallgato_neptun_kod + "</td>");
		tr.append("<td>" + data[i].hallgato_neve + "</td>");
		tr.append('<td><a href="adatmodositas.php?id=' + data[i].hallgato_id+'"><i class="fa fa-pencil"></i></a></td>');
		
		if(data[i].bekoltozes_datuma == "0000-00-00 00:00:00") {
			tr.append('<td><span class="fa fa-exclamation"></span></td>');
		}
		else{
			tr.append('<td><span class="fa fa-check"></span></td>');
		}
		
		var td = jQuery('<td/>');
		td.append(link);
		tr.append(td);

		lakokTable.append(tr);
	}

	lakokDiv.append(lakokTable);
	panel1.append(lakokDiv);

	lakokPanel.append(panel1);

	if (vanszabadhely) {

		var szobaLakokDiv = jQuery("#szobaLakokDIV");

		
	}

}

function displayAvailableStudents(data, szid) {

	var lakokPanel = jQuery("#szobaLakokPanel");

	var koliID = jQuery("#koliSelect").children(":selected").val();
	
	if (jQuery("#hallgatokSzobaNelkulDIV").length) {

		var hallgatokDiv = jQuery("#hallgatokSzobaNelkulDIV");
		hallgatokDiv.empty();

		var hallgatokTable = jQuery("<table>");
		hallgatokTable.addClass("table table-bordered");
		
		if(data.length == 0){
			hallgatokTable.append('<tr><td rowspan=3>A keresőfeltételnek egyetlen hallgató sem felel meg.</td></tr>');
		}
		else{
			
			for (i = 0; i < data.length; i++) {
				var tr = jQuery('<tr/>');

				var link = jQuery("<a>");
				link.text("Szobához hozzárendel");
				link.addClass("bekoltoztet");
				link.attr("id", "bekoltoztet_" + koliID + "_" + szid + "_" + data[i].hallgato_id);

				tr.append("<td>" + data[i].hallgato_neptun_kod + "</td>");
				tr.append("<td>" + data[i].hallgato_neve + "</td>");

				var td = jQuery('<td/>');
				td.append(link);
				tr.append(td);

				hallgatokTable.append(tr);
			}
			
		}


		hallgatokDiv.append(hallgatokTable);

	} else {
		var panel1 = jQuery("<div/>");
		panel1.addClass("panel panel-default");

		var panel1heading = jQuery("<div/>");
		panel1heading.addClass("panel-heading no-collapse");
		panel1heading.text("Felvett hallgatók szoba nélkül ");
		panel1heading.append('<input type="text" class="hallgatoHozzarendel" id="input_' + szid + '"/>');
		
		panel1.append(panel1heading);

		var hallgatokDiv = jQuery("<div/>");
		hallgatokDiv.attr("id", "hallgatokSzobaNelkulDIV");

		var hallgatokTable = jQuery("<table>");
		hallgatokTable.addClass("table table-bordered");

		if(data.length == 0){
			hallgatokTable.append('<tr><td rowspan=3>A keresőfeltételnek egyetlen hallgató sem felel meg.</td></tr>');
		}
		else{
			for (i = 0; i < data.length; i++) {
				var tr = jQuery('<tr/>');

				var link = jQuery("<a>");
				link.text("Szobához hozzárendel");
				link.addClass("bekoltoztet");
				link.attr("id", "bekoltoztet_" + koliID + "_" + szid + "_" + data[i].hallgato_id);

				tr.append("<td>" + data[i].hallgato_neptun_kod + "</td>");
				tr.append("<td>" + data[i].hallgato_neve + "</td>");

				var td = jQuery('<td/>');
				td.append(link);
				tr.append(td);

				hallgatokTable.append(tr);
			}
		}
		


		hallgatokDiv.append(hallgatokTable);
		panel1.append(hallgatokDiv);

		lakokPanel.append(panel1);
	}

}


function loadHallgatoWithoutRoom(hallg, szobaid) {

	var koll = jQuery("#koliSelect").children(":selected").val();

	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "lookupStudentWithoutRoom",
			hallgato : hallg,
			kollegium : koll
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			displayAvailableStudents(data.data, szobaid);
		}
		else if(data.errorCode == 1000){
			location.reload();
		} 
		else {
			console.log("Hiba: " + data.message);
		}
	}).fail(function() {
		console.log("Error");
	});
}

function hallgatoSzobahozRendel(rendelid) {
	// alert(rendelid);
	var idcomp = rendelid.split("_");

	
	var kollegium = idcomp[1];
	var szoba = idcomp[2];
	var hallgato = idcomp[3];
	
	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "studentToRoom",
			hallgato : hallgato,
			kollegium : kollegium,
			szoba : szoba
		}
	}).done(function(data) {
		if (data.errorCode == 0) { 
			//update letszam + class // open szoba
			updateRoom(data.data);
			loadRoomData("szobaTd_"+szoba);
			
		}
		else if(data.errorCode == 1000){
			location.reload();
		} 
		else {
			console.log("Hiba: " + data.message);
		}
	}).fail(function() {
		console.log("Error");
	});
}

function hallgatoSzobabolKivesz(rendelid) {
	// alert(rendelid);
	var idcomp = rendelid.split("_");

	
	var kollegium = idcomp[1];
	var szoba = idcomp[2];
	var hallgato = idcomp[3];
	
	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "studentRemoveFromRoom",
			hallgato : hallgato,
			kollegium : kollegium,
			szoba : szoba
		}
	}).done(function(data) {
		if (data.errorCode == 0) { 
			//update letszam + class // open szoba
			updateRoom(data.data);
			loadRoomData("szobaTd_"+szoba);
			
		}
		else if(data.errorCode == 1000){
			location.reload();
		} 
		else {
			console.log("Hiba: " + data.message);
		}
	}).fail(function() {
		console.log("Error");
	});
}

function updateRoom(data){
	
	var szobaID = data.szoba;
	var szabad = data.szabad;
	var szobaTD = jQuery("#szobaTd_"+szobaID);
	var szobaText = szobaTD.html();
	
	var textcomp = szobaText.split("(");
	var szobaszam = textcomp[0];
	
	szobaTD.html(szobaszam+"("+szabad+")");
	
	if(szabad == "0"){
		szobaTD.removeClass("vanhely");
		szobaTD.addClass("nincshely");
	}
	else{
		szobaTD.removeClass("nincshely");
		szobaTD.addClass("vanhely");
		
	}
	
	
}

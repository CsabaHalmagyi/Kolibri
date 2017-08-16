
/*
 * ujhallgato.php
 * WS: hallg.php
 * WS: koll.php
 */

//GLOBALS
var Kollegiumok = [];
var Penzugyikodok = [];
var Tanev = [];

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
			Penzugyikodok = data.data2;
			Tanev = data.data3;
				displayNewStudentForm();
				displayStudentUploadForm();

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



function displayNewStudentForm(){
	var ujHallgatoDIV1 = jQuery("#ujHallgatoDiv1");
	ujHallgatoDIV1.empty();
	var ujHallgatoDIV2 = jQuery("#ujHallgatoDiv2");
	ujHallgatoDIV2.empty();

	
	var panelDIV = jQuery("<div/>");
	panelDIV.addClass("panel panel-default");
	
	var panelHeadingDIV = jQuery("<div/>");
	panelHeadingDIV.addClass("panel-heading no-collapse");
	panelHeadingDIV.append('<span class="panel-icon pull-right"><a id="checkStudent" href="#" data-original-title="Hallgató keresése"><i class="fa fa-search"></i></a></span> Személyes adatok');
	
	panelDIV.append(panelHeadingDIV);
	
	var panelBody = jQuery("<div/>");
	panelBody.addClass("panel-body");
	
	var studentForm = jQuery("<form/>");
		//studentForm.addClass("form-inline");
	studentForm.append('<div class="form-group"><label>Neptun kód*</label><input type="text" class="form-control span12" id="neptunkod"></div>');
	studentForm.append('<div class="form-group"><label>Név*</label><input type="text" class="form-control span12" id="nev"></div>');
	studentForm.append('<div class="form-group"><label>Email</label><input type="text" class="form-control span12" id="email"></div>');
	studentForm.append('<div class="form-group"><label>Telefon</label><input type="text" class="form-control span12" id="telefon"></div>');
	studentForm.append('<div class="form-group"><label>Lakcím</label><textarea class="form-control" rows="2" id="lakcim"></textarea></div>');
	studentForm.append('<div class="form-group"><label>Állampolgárság</label><input type="text" class="form-control span12" id="allampolgarsag"></div>');
	studentForm.append('<div class="form-group"><label>Képzési forma</label><input type="text" class="form-control span12" id="kepzesiforma"></div>');
	panelBody.append(studentForm);
	panelDIV.append(panelBody);
	ujHallgatoDIV1.append(panelDIV);
	
	
	
	var panel2DIV = jQuery("<div/>");
	panel2DIV.addClass("panel panel-default");
	
	var panel2HeadingDIV = jQuery("<div/>");
	panel2HeadingDIV.addClass("panel-heading no-collapse");
	panel2HeadingDIV.text("Kollégiumi adatok");
	panel2DIV.append(panel2HeadingDIV);

	var panel2Body = jQuery("<div/>");
	panel2Body.addClass("panel-body");

	var student2Form = jQuery("<form/>");

	
	var felveve = '<div class="form-group"><label>Felvéve*</label><select id="felveve" class="form-control">';
	var aktKollId = 0;
	for(i=0;i<Kollegiumok.length;i++){
		if(aktKollId == 0) {
			aktKollId = Kollegiumok[i].kollegium_id;
		}
		felveve += '<option value="'+Kollegiumok[i].kollegium_id+'">'+Kollegiumok[i].kollegium_nev+'</option>';
	}
	felveve += '</select></div>';
	student2Form.append(felveve);
	
	var penzugyikodok = '<div class="form-group"><label>Pénzügyi kód*</label><select id="penzugyikod" class="form-control">';

	for(i=0;i<Penzugyikodok.length;i++){
		if(aktKollId == Penzugyikodok[i].kollegium_id) {
			penzugyikodok += '<option value="'+Penzugyikodok[i].pk_id+'">'+Penzugyikodok[i].kollegiumi_dij+'</option>';
		}
	}
	penzugyikodok +='<option value="0">Nincs, a hallgató nem a Neptunban fizet</option>';
	penzugyikodok += '</select></div>';
	student2Form.append(penzugyikodok);
	student2Form.append('<div class="form-group"><label>Tanév*</label><input type="text" class="form-control span12" id="tanev" value="'+AktTanev+'" disabled></div>');
	student2Form.append('<div class="form-group"><a id="createNewStudent" class="btn btn-primary pull-right" href="#">Mentés!</a></div>');
	
	panel2Body.append(student2Form);
	panel2DIV.append(panel2Body);
	ujHallgatoDIV2.append(panel2DIV);
	
	
}

function updateFinanceCodeList(){
	var selectedDorm = jQuery("#felveve").children(":selected").val();
	
	var financeCodeList = jQuery("#penzugyikod");
	financeCodeList.empty();
	
	var newCodes = '';
	
	for(i=0;i<Penzugyikodok.length;i++){
		if(selectedDorm == Penzugyikodok[i].kollegium_id) {
			newCodes += '<option value="'+Penzugyikodok[i].pk_id+'">'+Penzugyikodok[i].kollegiumi_dij+'</option>';
		}
	}
	newCodes +='<option value="0">Nincs, a hallgató nem a Neptunban fizet</option>';
	
	financeCodeList.append(newCodes);
}


function createNewStudent(){
	
	var neptunkod = jQuery("#neptunkod").val();
	var nev = jQuery("#nev").val();
	var email = jQuery("#email").val();
	var telefon = jQuery("#telefon").val();
	var lakcim = jQuery("#lakcim").val();
	var allampolgarsag = jQuery("#allampolgarsag").val();
	var kepzesiforma = jQuery("#kepzesiforma").val();
	var kollegium = jQuery("#felveve").children(":selected").val();
	var penzugyikod = jQuery("#penzugyikod").children(":selected").val();
	
	neptunkod = neptunkod.toUpperCase();
	
	if(neptunkod.length != 6) {
		alert("Hibás neptun kód!");
		return 0;
	}
	
	if(nev.length < 4) {
		alert("Túl rövid név!");
		return 0;
	}
	

	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "createNewStudent",
			nk : neptunkod,
			nev : nev,
			email : email,
			telefon : telefon,
			lakcim : lakcim,
			allampolgarsag : allampolgarsag,
			kepzesiforma : kepzesiforma,
			kollegium : kollegium,
			penzugyikod : penzugyikod
			
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			alert(data.message);
			displayNewStudentForm();
			}
		else if(data.errorCode == 1000){
			location.reload();
		} 
		else {
			alert("Hiba: " + data.message);
		}
	}).fail(function() {
		alert("Error");
	});
}

/**
 * TODO: implement this
 */
function displayStudentUploadForm(){
	//form for uploading new students
}


function uploadXLS(){
	console.log(jQuery('#uploadFile').val());
	
	if(jQuery('#uploadFile').val() != "" && jQuery('#uploadFile').val() != undefined){
		jQuery('#studentsFromXLS')[0].submit();
	}
	
	
}




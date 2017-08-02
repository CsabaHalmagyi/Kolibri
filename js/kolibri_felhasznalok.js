

/*
 * felhasznalok.php
 * WS: felh.php
 */
//GLOBAL
var Users = [];
var Groups = [];
function getUsersFromDB(){
	
	var jqxhr = jQuery.ajax({
		url : "ws/felh.php",
		type : "post",
		data : {
			action : "getUsers",
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			//add users to 
			Users = data.data;
			Groups = data.message;
			displayUsersTable();
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


function displayUsersTable(){
	var userDIV = jQuery("#felhasznalokDiv");
	userDIV.empty();
	
	var panelDIV = jQuery('<div class="panel panel-default"/>');
	var panelHeadingDIV = jQuery('<div class="panel-heading no-collapse"/>');
	panelHeadingDIV.text("Felhasználók");
	
	var userTable = jQuery('<table id="userTable"/>');
	userTable.addClass("table table-bordered table-striped");
	
	userTable.append("<thead><tr><th>Felhasználónév</th><th>Vezetéknév</th><th>Keresztnév</th><th>Csoport</th><th>Létrehozva</th><th>Utolsó belépés</th><th></th><th></th></tr></thead>");
	
	var userTableContent = jQuery("<tbody/>");
	var userRows = '';
	for(i=0;i<Users.length;i++){
		userRows += '<tr id="row_'+Users[i].felhasznalo_id+'">'+
		'<td>'+Users[i].felhasznalonev+'</td>'+
		'<td>'+Users[i].vezeteknev+'</td>'+
		'<td>'+Users[i].keresztnev+'</td>'+
		'<td>'+Users[i].csoportnev+'</td>'+
		'<td>'+Users[i].regisztracio_idopontja+'</td>'+
		'<td>'+Users[i].utolso_belepes+'</td>';
		if(Users[i].felhasznalonev == 'kolibri'){
			userRows += '<td></td><td></td>';
		}
		else{
			userRows += '<td><a href="#" id="edit_user_'+Users[i].felhasznalo_id+'" class="editUser"><i class="fa fa-pencil"></i> Szerkeszt</a></td>'+
			'<td><a href="#" id="delete_user_'+Users[i].felhasznalo_id+'" class="deleteUser"><i class="fa fa-trash-o"></i> Töröl</a></td>';
		}
		userRows += '</tr>';
		
	}
	
	userTableContent.append(userRows);
	userTable.append(userTableContent);
	
	panelDIV.append(panelHeadingDIV);
	panelDIV.append(userTable);
	userDIV.append(panelDIV);
	
	var panel2DIV = jQuery('<div class="panel panel-default"/>');
	var panel2HeadingDIV = jQuery('<div class="panel-heading no-collapse"/>');
	panel2HeadingDIV.text("Új felhasználó létrehozása");
	
	var newUserTable = jQuery('<table id="newUserTable"/>');
	newUserTable.addClass("table table-bordered table-striped");
	newUserTable.append("<thead><tr><th>Felhasználónév</th><th>Vezetéknév</th><th>Keresztnév</th><th>Jelszó</th><th>Jelszó újra</th><th>Csoport</th><th></th></tr></thead>");
	
	var newUserTableContent = jQuery("<tbody/>");
	var newUserRow = '<tr><td><input type="text" id="felhasznalonev" class="newUser"/></td>'+
	'<td><input type="text" id="vezeteknev" class="newUser"/></td>'+
	'<td><input type="text" id="keresztnev" class="newUser"/></td>'+
	'<td><input type="password" id="jelszo" class="newUser"/></td>'+
	'<td><input type="password" id="jelszo2" class="newUser"/></td>'+
	'<td><select id="csoport" class="newUser">';
	
	for(g=0;g<Groups.length;g++){
		newUserRow += '<option id="'+Groups[g].id+'">'+Groups[g].csoportnev+'</option>';
	}
	
	newUserRow += '</select></td>';
	newUserRow += '<td><a href="#" id="createNewUser"><i class="fa fa-floppy-o"> Létrehoz</i></a></td>'
	+ '</tr>';
	
	newUserTableContent.append(newUserRow);
	newUserTable.append(newUserTableContent);
	
	panel2DIV.append(panel2HeadingDIV);
	panel2DIV.append(newUserTable);
	userDIV.append(panel2DIV);
	
	console.log(JSON.stringify(Users));
}

function editUser(userID){
	var res = userID.split("_");
	var uid = res[2];
	 
	var userDIV = jQuery("#felhasznalokDiv");
	userDIV.empty();
	
	var dialogDIV = jQuery('<div class="dialog">');
	var panelDIV = jQuery('<div class="panel panel-default"/>');
	
	var panelHeading = jQuery('<p class="panel-heading no-collapse">Felhasználó</p>');
	panelDIV.append(panelHeading);
	
	var panelBodyDIV = jQuery("<div/>");
	panelBodyDIV.addClass("panel-body");
	
	var panelForm = jQuery("<form/>");

	var userDetails = '';
	
	for(i=0;i<Users.length;i++){
		if(Users[i].felhasznalo_id == uid){
			userDetails += '<div class="form-group"><label>Felhasználónév</label>'+
			'<input id="felhnev" class="form-control span12" type="text" value="'+Users[i].felhasznalonev+'" disabled="disabled"/></div>';
			userDetails += '<div class="form-group"><label>Új jelszó</label>'+
			'<input id="jelszo" class="form-control span12" type="password" value=""/></div>';
			userDetails += '<div class="form-group"><label>Vezetéknév</label>'+
			'<input id="vezeteknev" class="form-control span12" type="text" value="'+Users[i].vezeteknev+'"/></div>';
			userDetails += '<div class="form-group"><label>Keresztnév</label>'+
			'<input id="keresztnev" class="form-control span12" type="text" value="'+Users[i].keresztnev+'"/></div>';
			userDetails += '<div class="form-group"><label>Csoport</label><select id="csoport" class="form-control">';

			
			for(j=0;j<Groups.length;j++){
				if(Groups[j].id == Users[i].csoport){
					userDetails += '<option value="'+Groups[j].id+'" selected="selected">'+Groups[j].csoportnev+'</option>';
				}
				else{
					userDetails += '<option value="'+Groups[j].id+'">'+Groups[j].csoportnev+'</option>';
				}
				
			}
			userDetails += '</select></div>';
			
			userDetails += '<div class="form-group"><label>Státusz</label><select id="statusz" class="form-control">';
			
			if(Users[i].aktiv == 1){
				userDetails += '<option value="1" selected="selected">Aktív</option><option value="0">Letiltott</option></select></div>';
			}
			else{
				userDetails += '<option value="1">Aktív</option><option value="0" selected="selected">Letiltott</option></select></div>';
			}
		}
		
	}
	
	userDetails += '<input type="hidden" id="edit_user_id" value="'+uid+'"/>';
	panelForm.append(userDetails);
	panelBodyDIV.append(panelForm);
	
	var buttonToolbar = jQuery('<div/>');
	buttonToolbar.addClass("btn-toolbar list-toolbar");
	
	var saveButton = jQuery('<a href="#" id="saveUser">');
	saveButton.addClass("btn btn-primary");
	saveButton.append('<i class="fa fa-floppy-o"></i> Mentés');
	
	var cancelButton = jQuery('<a href="#" id="cancelSaving">');
	cancelButton.addClass("btn btn-primary");
	cancelButton.append('<i class="fa fa-times"></i> Mégse');
	
	buttonToolbar.append(saveButton);
	buttonToolbar.append(cancelButton);
	
	panelBodyDIV.append(buttonToolbar);
	
	panelDIV.append(panelBodyDIV);
	dialogDIV.append(panelDIV);
	
	userDIV.append(dialogDIV);
	//alert(userID);
}

function createNewUser(){
	
	var userName = jQuery("#felhasznalonev").val();
	userName = userName.toLowerCase(); 
	var surName =  jQuery("#vezeteknev").val();
	var firstName = jQuery("#keresztnev").val();
	var pass1 = jQuery("#jelszo").val();
	var pass2 = jQuery("#jelszo2").val();
	var group = jQuery("#csoport").children(":selected").attr("id");
	
	//validate input
	if(userName.length<4 || !userName){
		readyToSubmit = false;
		alert("Túl rövid felhasználó név!");
		return 0;
	}
	for(i=0;i<Users.length;i++){
		if(Users[i].felhasznalonev == userName){
			alert("Ez a felhasználó már létezik!");
			return 0;
		}
	}

	if(surName.length<1 || !surName){
		alert("Hiányzó vezetéknév!");
		return 0;
	}	
	
	if(firstName.length<1 || !firstName){
		alert("Hiányzó keresztnév!");
		return 0;
	}	
	
	if(pass1.length<1 || !pass1){
		alert("Hiányzó jelszó!");
		return 0;
	}	
	
	if(pass1 != pass2){
		alert("A jelszavak nem egyeznek meg!");
		return 0;
	}	

	//submit values
	

	var jqxhr = jQuery.ajax({
		url : "ws/felh.php",
		type : "post",
		data : {
			action : "createNewUser",
			username : userName,
			password : pass1,
			firstname : firstName,
			surname : surName,
			group : group
			
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			getUsersFromDB();
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

function deleteUser(userID){
	
	var res = userID.split("_");
	var uid = res[2];
	var uname = '';
	for(i=0;i<Users.length;i++){
		if(Users[i].felhasznalo_id == uid){
			uname = Users[i].felhasznalonev;
		}
	}
	
	var confirmDel = confirm("Biztosan törlöd a(z) "+uname+" nevű felhasználót?");
	
	if(confirmDel){
		var jqxhr = jQuery.ajax({
			url : "ws/felh.php",
			type : "post",
			data : {
				action : "deleteUser",
				userid : uid
			}
		}).done(function(data) {
			if (data.errorCode == 0) {
				getUsersFromDB();
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

}



function updateUser(){
	var uid = jQuery("#edit_user_id").val();
	var jelszo = jQuery("#jelszo").val();
	var vezeteknev = jQuery("#vezeteknev").val();
	var keresztnev = jQuery("#keresztnev").val();
	var csoport = jQuery("#csoport").children(":selected").val();
	var statusz = jQuery("#statusz").children(":selected").val();
	
	if(jelszo.length > 0 && jelszo.length<=3){
		alert("Túl rövid jelszó!");
		return 0;
	}
	
	if(vezeteknev.length<2 || keresztnev.length<2){
		alert("Érvénytelen név!");
		return 0;
	}
	
	console.log(uid+" "+jelszo+" "+vezeteknev+" "+keresztnev+" "+csoport+" "+statusz);
	
	var jqxhr = jQuery.ajax({
		url : "ws/felh.php",
		type : "post",
		data : {
			action : "updateUser",
			userid : uid,
			pass : jelszo,
			surname : vezeteknev,
			firstname : keresztnev,
			group : csoport,
			status: statusz
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			getUsersFromDB();
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

/*
 * jogosultsagok.php
 * WS: jog.php
 */

function editGroup(groupID) {
	if (!jQuery(".cancelUpdateGroup")[0]) {
		var res = groupID.split("_");
		var numID = res[1];
		var rowID = 'row'+numID;
		
		jQuery("#"+rowID+" input:checkbox").removeAttr("disabled");
		jQuery("#"+numID+"_2").attr("disabled", true);
		
		jQuery("#edit_"+numID).attr('id', 'update_'+numID);
		
		var saveImage = jQuery("<i/>");
		saveImage.addClass("fa fa-floppy-o");
		
		var saveLink = jQuery("<a/>");
		saveLink.attr('id','update_'+numID);
		saveLink.addClass("updateGroup");
		//saveLink.html(image);
		
		jQuery("#edit_td_"+numID).empty();
		jQuery("#edit_td_"+numID).append(saveLink);
		jQuery("#update_"+numID).append(saveImage);
		
		
		var cancelImage = jQuery("<i/>");
		cancelImage.addClass("fa fa-times");
		var cancelLink = jQuery("<a/>");
		cancelLink.attr('id','cancel_update_'+numID);
		cancelLink.addClass("cancelUpdateGroup");
		
		jQuery("#delete_td_"+numID).empty();
		jQuery("#delete_td_"+numID).append(cancelLink);
		jQuery("#cancel_update_"+numID).append(cancelImage);
		
	}
}

function cancelUpdateGroup(groupID){
	
	location.reload();
}

function updateGroup(groupID){
	var res = groupID.split("_");
	var numID = res[1];
	
	var rowID = 'row'+numID;
	var jogok = new Object;

	jQuery("#"+rowID+" input:checkbox").each(function() {
		var id = jQuery(this).attr("id");
		var val = jQuery(this).is(':checked');

		jogok[id] = val;
	});

	var jqxhr = jQuery.ajax({
		url : "ws/jog.php",
		type : "post",
		data : {
			action : "updateGroup",
			groupID : numID,
			permissions : JSON.stringify(jogok)
		}
	}).done(function(data) {
		if (data.errorCode == 0) {
			location.reload();
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


function addNewGroup() {
	if (!jQuery(".ujcsoport")[0]) {
		jQuery('#jogosultsagtabla tr:last')
				.after(
						'<tr class="ujcsoport">'
								+ '<td><input type="text" id="csoportnev"/></td>'
								+ '<td><input id="uj_1" class="uj" type="checkbox"/></td>'
								+ '<td><input id="uj_2" class="uj" type="checkbox" checked="" disabled=""/></td>'
								+ '<td><input id="uj_3" class="uj" type="checkbox"/></td>'
								+ '<td><input id="uj_4" class="uj" type="checkbox"/></td>'
								+ '<td><input id="uj_5" class="uj" type="checkbox"/></td>'
								+ '<td><input id="uj_6" class="uj" type="checkbox"/></td>'
								+ '<td><input id="uj_7" class="uj" type="checkbox"/></td>'
								+ '<td><input id="uj_8" class="uj" type="checkbox"/></td>'
								+ '<td><input id="uj_9" class="uj" type="checkbox"/></td>'
								+ '<td><input id="uj_10" class="uj" type="checkbox"/></td>'
								+ '<td><a href="#" id="createNewGroup"><i class="fa fa-floppy-o"></i></a></td>'
								+ '<td><a href="#" id="cancelNewGroup"><i class="fa fa-times"></i></a></td>'
								+

								'</tr>');
		jQuery("#csoportnev").focus();

	} else {
		jQuery("#csoportnev").focus();

	}
}

function cancelNewGroup() {
	if (jQuery(".ujcsoport")[0]) {

		jQuery(".ujcsoport").remove();
	}

}

function createNewGroup() {
	var csoportNev = jQuery('#csoportnev').val();
	var id2 = jQuery('#uj_2').is(':checked');

	if (csoportNev.length < 2) {
		alert("Túl rövid csoportnév!");

	} else if (!id2) {
		alert("Hiányzó alapvető jogosultság!");
	} else {
		var jogok = new Object;
		jQuery('.uj').each(function() {
			var id = jQuery(this).attr("id");
			var val = jQuery(this).is(':checked');

			jogok[id] = val;
		});

		var jqxhr = jQuery.ajax({
			url : "ws/jog.php",
			type : "post",
			data : {
				action : "createNewGroup",
				name : csoportNev,
				permissions : JSON.stringify(jogok)
			}
		}).done(function(data) {
			if (data.errorCode == 0) {
				location.reload();
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

function deleteGroup(groupID) {
	var res = groupID.split("_");
	var id = res[1];
	// alert(id);
	var confirmDel = confirm("Biztosan törlöd ezt a csoportot?");

	if (confirmDel) {
		var jqxhr = jQuery.ajax({
			url : "ws/jog.php",
			type : "post",
			data : {
				action : "deleteGroup",
				groupID : id,
			}
		}).done(function(data) {
			console.log(data.errorCode);
			if (data.errorCode == 0) {
				location.reload();

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



function updateStudent(id){
	
	var hallgato=id.split("_");
	//var neptunkod = jQuery("#neptunkod").val();
	var nev = jQuery("#nev").val();
	var email = jQuery("#email").val();
	var telefon = jQuery("#telefon").val();
	var lakcim = jQuery("#lakcim").val();
	var allampolgarsag = jQuery("#allampolgarsag").val();
	var kepzesiforma = jQuery("#kepzesiforma").val();
	var kollegium = jQuery("#felveve").children(":selected").val();
	var penzugyikod = jQuery("#penzugyikod").children(":selected").val();
	
	if(nev.length < 4) {
		alert("Túl rövid név!");
		return 0;
	}
	

	var jqxhr = jQuery.ajax({
		url : "ws/hallg.php",
		type : "post",
		data : {
			action : "updateStudent",
			hallgid : hallgato[1],
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
			var div = jQuery("#responseMessageDIV");
			div.empty();
			div.append('<div class="alert alert-success">'+data.message+'</div>');
			}
		else if(data.errorCode == 1000){
			location.reload();
		} 
		else {
			var div = jQuery("#responseMessageDIV");
			div.empty();
			div.append('<div class="alert alert-danger">'+data.message+'</div>');
		}
	}).fail(function() {
		alert("Error");
	});
}
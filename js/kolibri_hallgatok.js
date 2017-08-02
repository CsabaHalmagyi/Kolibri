

function hallgatoAdatmodositasRedirect(hallgid){
	
	var hallgato = hallgid.split("_");
	window.location.href = "adatmodositas.php?id=" + hallgato[1];
}

function hallgatoKartyaRedirect(hallgid){
	
	var hallgato = hallgid.split("_");
	window.location.href = "kartya.php?id=" + hallgato[1];
}

function hallgatoKeres() {

	var celcsoport = jQuery("input[name=celcsoport]:checked").val();
	var mire = jQuery("input[name=mire]:checked").val();
	var keresoszo = jQuery("#keresoszo").val();
	
	if(keresoszo.length>0){
		var jqxhr = jQuery.ajax({
			url : "ws/hallg.php",
			type : "post",
			data : {
				action : "hallgatoKeres",
				celcsoport : celcsoport,
				mire : mire,
				keresoszo : keresoszo
			}
		}).done(function(data) {
			if (data.errorCode == 0) {
				eredmenyMegjelenit(data.data);
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

}





function eredmenyMegjelenit(data){
	var eredmenyDiv = jQuery("#keresoEredmeny");
	eredmenyDiv.empty();
	eredmenyDiv.append(data);
	
}
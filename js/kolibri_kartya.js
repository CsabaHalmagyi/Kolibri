

function kartyaVisszavetel(id){
	
	var bejegyzes = id.split("_");
	
	if(bejegyzes[1] != ""){
		
		var jqxhr = jQuery.ajax({
			url : "ws/koll.php",
			type : "post",
			data : {
				action : "kartyaVisszavetel",
				bejegyzes : bejegyzes[1],
			}
		}).done(function(data) {
			if (data.errorCode == 0) {
				location.reload();
			} 
			else {
				if(data.errorCode == 1000){
					location.reload();
				}
				else{
					var div = jQuery("#responseMessageDIV");
					div.empty();
					div.append('<div class="alert alert-danger">'+data.message+'</div>');
					
				}
				
			}
		}).fail(function() {
			console.log("Error");
		});
		
		
		
	}
	
	
	
}



function kartyaKiadas(id){
	
	var hallgato = id.split("_");
	var kartyaszam = jQuery("#ujkartyaszam").val();
	
	if(kartyaszam != ""){
		
		var jqxhr = jQuery.ajax({
			url : "ws/koll.php",
			type : "post",
			data : {
				action : "kartyaKiadas",
				hallgato : hallgato[1],
				kartya : kartyaszam
			}
		}).done(function(data) {
			if (data.errorCode == 0) {
				location.reload();
			} 
			else {
				if(data.errorCode == 1000){
					location.reload();
				}
				else{
					var div = jQuery("#responseMessageDIV");
					div.empty();
					div.append('<div class="alert alert-danger">'+data.message+'</div>');
					
				}
				
			}
		}).fail(function() {
			console.log("Error");
		});
		
		
		
	}
	
	
}
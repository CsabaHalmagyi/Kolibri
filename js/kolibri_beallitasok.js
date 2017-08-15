

function tanevvaltas(){
	
	var ujtanev = jQuery("#AktualisFelev").children(":selected").val();
	
	
	if(1==1){
		
		var jqxhr = jQuery.ajax({
			url : "ws/koll.php",
			type : "post",
			data : {
				action : "tanevValtas",
				tanev: ujtanev
			}
		}).done(function(data) {
			if (data.errorCode == 0) {
				alert("Tanév váltás sikeres.");
			
			}
			else if(data.errorCode == 1000){
				location.reload();
			} 
			else {
				alert(data.message);
			
			
			}
		}).fail(function() {
			alert("Error");
		});
		
		
		
	}
	
	
	
}



function tanevzaras(){
	
	var megerosit = confirm("Biztosan lezárod az aktuális tanévet?");
	
	if(megerosit){
		
		var jqxhr = jQuery.ajax({
			url : "ws/koll.php",
			type : "post",
			data : {
				action : "tanevZaras"
			}
		}).done(function(data) {
			if (data.errorCode == 0) {
				
				alert(data.message);
			
			}
			else if(data.errorCode == 1000){
				location.reload();
			} 
			else {
				
				alert(data.message);
			
			}
		}).fail(function() {
			alert("Error");
		});
		
		
		
	}
	
}
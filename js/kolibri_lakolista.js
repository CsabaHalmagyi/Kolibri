


function koliSelect() {

	var koliID = jQuery("#koliSelect").children(":selected").val();

	window.location.href = "lakolista.php?kollegium=" + koliID;

}
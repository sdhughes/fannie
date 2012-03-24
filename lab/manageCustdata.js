$(document).ready(function(event) {
alert('hi');
	$('button.deleteDupe').live('click',function(event){
		
		var cardNo = $(this).parents().siblings('.cardNo').html();

		alert(cardNo);

	});



});

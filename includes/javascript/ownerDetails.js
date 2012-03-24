$(document).ready(function() {

	//extra/ownerDetails.php
	$('.label').add('.results').hide();
	$('.owner_row').children().show();

	$('.owner_row').live('click', function(){

		$(this).siblings().children().toggle();
			
	});
});

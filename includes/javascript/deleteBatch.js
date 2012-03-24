$(document).ready(function() {



	$('.deleteBatchButton').live('click', function(event){
		var row = $(this).parent().parent();

		var batchID = row.children().children('.batchID').attr('value');
		//alert(batchID);	

		$.post('./deleteBatch.php', {'batchID': batchID,'submit': 'submit'},function(data){
			//$('#acg_main').css('background','red');
//			alert(data);
//			header('Location: http://192.168.1.103/batches/index.php');
	
 		});
		row.hide();

		event.preventDefault();
		event.stopPropagation();
		
		//alert("I don't work yet! (but soon...)");
		
	});


	$('#toggleBatches').click(function(event) {

		var value = $(this).attr('value');


		if (value == 'Show Inactive') {
			$(this).attr('value','Hide Inactive');
            $('#showinactive').attr('value','hide');
			$(".activeFlag:contains('InActive')").parent().show();
		} else {
			$(this).attr('value','Show Inactive');
            $('#showinactive').attr('value','show');
			$(".activeFlag:contains('InActive')").parent().hide();

		}
		event.stopPropagation();
	});
	
	$(".activeFlag:contains('InActive')").parent().hide();
});

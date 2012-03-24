$(document).ready(function() {



	$('.deletebutton').live('click', function(event){
		var row = $(this).parent().parent();

		row.css('background','red');
		var qty = row.children('.quantity').html();	
		var stamp = row.children('.timestamp').html();	
		var UPC = row.children('.UPC').html();	

		$.post('./deleteShrunkenItem.php', {'quantity': qty, 'datetime': stamp, 'upc': UPC},function(data){
			//$('#acg_main').css('background','red');
			alert(data);
			//header('Location: http://192.168.1.103/extra/shrinkTool.php');
            window.location.reload();
	
 		});

		event.preventDefault();
		event.stopPropagation();
		
		//alert("I don't work yet! (but soon...)");
		
	});
});

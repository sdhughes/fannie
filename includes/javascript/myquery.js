$(document).ready(function(){
//alert('hi steve');
	//ownerDetails stuff
	$('.label').add('.results').hide();
	$('.owner_row').children().show();

	$('.owner_row').click(function(){

		$(this).siblings().children().toggle();
		
	});

	//dept picker show/hide handler
	$('#dept_picker').hide();

        $('#picker_toggle').click(function(){
                $('#dept_picker').toggle();
        });

	//colorize the inUse and out of use items on the item Maintenance screen
	$('.itemMaint_inUse').addClass('green');
	$(".itemMaint_inUse:contains('0')").removeClass('green').addClass('red').html('no');
	$(".itemMaint_inUse:contains('1')").html('yes');


	//itemMaint function: toggle all the items in the search list.
	$('input#toggleAll').click(function(){

		
		var text = $(this).attr('value');

		//alert(text);
	
		var allCheckboxes = $('.itemMaint_checkbox');
		
		if (text == 'Select All') {
	
			allCheckboxes.attr('checked','checked');
			$(this).attr('value','Select None');
		} else {

			allCheckboxes.removeAttr('checked');
			$(this).attr('value','Select All');
		}
	});

	var itemResultRows= $('table#item_results tbody tr');
	var evenRows= $('table#item_results tbody tr:even');

	evenRows.addClass('even');

	itemResultRows.mouseover(function(){
                $(this).addClass('over');
        });

       	itemResultRows.mouseout(function(){
                $(this).removeClass('over');
        });
/*
	var placeholder = $('#placeholder');
	var data = [];
	//var options = {series: {width: 300px, height: 200px}};


	$.plot(placeholder,data);

	$('#graph').click(function(event){

		
		var date1 = $('#date1').val();
		var date2 = $('#date2').val();
		var upc = $('#upc').val();
//		var options = {series: {}};
		
		var baseURL = "./returnDailySales.php";
//		var dataURL = "?date1=" + date1 +"&date2=" + date2 + "&upc=" + upc  + "&submit=Submit";
		var dataURL = {
			date1: $('#date1').val(),
			date2: $('#date2').val(),
			upc: $('#upc').val(),
			submit: 'submit'
		};

		$.ajax({
			url: baseURL,
			method: 'get',
			dataType: 'json',
			data: dataURL,
			success: function(data) {
		//		alert(baseURL + dataURL);
				$.plot(placeholder,[data]);
				
			}
		});
//		alert(baseURL + dataURL );
//		event.stopPropagation();

	});
*/

});




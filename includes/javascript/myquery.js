$(document).ready(function(){

	//includes/dept_picker_generator.php
        $('#picker_toggle').click(function(){
                $('#dept_picker').slideToggle('fast');
        });

	//item/itemMaint.php
	$('.itemMaint_inUse:contains("1")').html("YES").addClass('green');
	$('.itemMaint_inUse:contains("0")').html("NO").addClass('red');;


	$('.itemMaint_checkbox').change(function(event) {
		
		event.stopPropogation();
	});

	$('#toggleAll').click(function(event){
		var text = $(this).val();
		if (text == 'Select All') {

			$('.itemMaint_checkbox').attr('checked','true');
			$(this).val('Deselect All');
			$(event).stopPropagation();
		} else {
			$('.itemMaint_checkbox').removeAttr('checked');
			$(this).val('Select All');
			$(event).stopPropagation();
		}

	});


    $('input#upc').focus();
/*
    $('.itemMaint_link').children('a').click(function(event) {
       
       var upc = $(this).html();
       alert(upc);
    //$(this).post('itemMaint.php',{'upc':upc,'submitted':'search'},function(){
        
        
      //  });  
       //event.stopPropogation();
       event.preventDefault();
       
        
    });
*/
	function toggleInUse ($checked) {
		
		


	}
/*
    $('.admin_menu_item').click(function(event) {
       event.stopPropagation();
       event.preventDefault();
       var loadURL = $(this).children('a').attr('href'); 
       
  
    });*/
    //$('.admin_menu_item').children('a').click(function(event) {
      // event.stopPropagation();
      // event.preventDefault();
    //});


	//timesheet/viewsheet.php and admin/employees.php
	$('.wage_element').hide();

        
      //          var date1 = $('#date1').html();
        //        var date2 = $('#date2').html();


         //       $.post('../reports/storeCharges.php',{'date1':date1,'date2':date2,'submit':'submit'},function(date){});

        
    //$('#chargeDetail').wrapInner("<a href=''></a>");


});

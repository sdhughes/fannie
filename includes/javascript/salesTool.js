$(document).ready(function () {
//alert('jquery works!');


	$('.deptCheck').click(function(event){
        event.stopPropagation();

 		//event.preventDefault();
        //alert($(this).attr('checked'));
		//if ($(this).attr('checked') == 'true') dept.removeAttr('checked');
		//else { $(this).attr('checked','true');}

		var subDept_pane = $('#subdeptPane');
        var alertText = $(this).attr('value');

//alert(alertText);
		//this tiles deptno
		var dept = $(this).children('input');
		var deptNo = $(this).attr('value');
		var deptName_raw = $(this).siblings('label').html();
		var deptName = deptName_raw.replace(/ /g,'');
		var deptNameID = "#" + deptName + "_tile.subdept_tile";
//	alert(dept + deptNo + deptName + deptNameID);	


		//if there are any elements of that particular name
		if ($(deptNameID).length > 0) {
//			alert($("#"+deptName+"_tile.subdept_tile").length);
			$(deptNameID).toggle();

		} else { 
		//otherwise, call a php file that creates a "tile" string & appends it
			$.post('../../includes/returnSubdepts.php',{'department': deptNo, 'deptName': deptName, 'deptName_raw': deptName_raw}, function (data) {
//					alert('data: ' + data);
					subDept_pane.append(data);
				});//end $.post
		}//end if $(deptNameID).length > 0
	});

});

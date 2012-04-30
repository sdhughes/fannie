$(document).ready(function(){
        var col_head = "<th>Delete?</th>";
        $('#announcement_display tr').first().append(col_head);

        var buttons = "<td class='buttonbox'><input type='button' class='deleteButton' name='deleteMessage' value='delete' /><br /><input type='button' class='updateButton' name='updateMessage' value='update' /></td>";
    $('#announcement_display .ann_row').each(function() { $(this).append(buttons)});



/*******************
 * deleteButton action
 *
 * author: steve
 * date: 2011-10-15
 *******************/
	$('.deleteButton').live('click', function() {
		
		var thisRow = $(this).parent().parent();


		var idToDelete = thisRow.find('.id').html();
	
//	alert("idToDelete:  " + idToDelete);

		$.post('../includes/deleteAnnouncement.php',{id:idToDelete},function(data) {
//			alert(data);
			location.reload();
		});


	});
/*******************
 * updateButton action
 *
 * author: steve
 * date: 2011-10-15
 *******************/


	$('.updateButton').live('click', function() {
		
		var thisRow = $(this).parent().parent();

		var idToUpdate = thisRow.find('.id').html();	
        var authorToUpdate = thisRow.find('.authorbox > input').val();
        var titleToUpdate = thisRow.find('.titlebox > input').val();
        var messageToUpdate = thisRow.find('textarea').val();
        var enableToUpdate = thisRow.find('.enablebox > input').attr('checked');

    var enableIt = 0;

    if (enableToUpdate) enableIt = 1;


//	alert("id:" + idToUpdate + ", enabled:" + enableToUpdate + ":  " + enableIt);

		$.post('../includes/updateAnnouncement.php',{id:idToUpdate,author:authorToUpdate,title:titleToUpdate,message:messageToUpdate,enable:enableIt},function(data) {
//			alert(data);
			location.reload();
		});


	});
});

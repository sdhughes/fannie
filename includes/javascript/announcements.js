$(document).ready(function(){

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

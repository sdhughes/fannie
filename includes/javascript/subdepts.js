$(document).ready(function (){

    $('#editPane').hide();

/*    $('#pickDept').live('click', function(event) {
        event.preventDefault();
        event.stopPropagation();

        var chosenDept = $('select option:selected').val();

        $('#editPane').empty();

        //get the subdepts
        $('#subdeptPane').load('../includes/common.php',{'reason':'listSubdepts','dept':chosenDept,'actionFile':'subdept_mgmt.php'});
        

    });*/

        $('.myButton').live('click',function(event) {

            event.preventDefault();
            event.stopPropagation();

            var action = $(this).text();
            var loc = $(this).position();
            alert(action);
         
            switch (action) {
                case 'update':
                        
                    break;

                case 'cancel':
                    $('#editPane').empty();
                    break;
            }
        });

        $('.edit_subdept_button').live('click',function(event) {
            event.preventDefault();
            event.stopPropagation();

            var action = $(this).attr('value');
            var loc = $(this).position();

            var subdept_id = $(this).parents().siblings('.subdept_id').text(); 
            var subdept_dept = $(this).parents().siblings('.dept_ID').html(); 
            var subdept_name = $(this).parents().siblings('.subdept_name').text(); 


            //alert(loc.left + " " + loc.top);
            switch (action) {
                case 'edit':

                        var editor = "<div class='subdept_edit'><span>" + subdept_id + "</span><input id='update_name_field' type='text' name='update_name' value='" + subdept_name + "' /><div class='myButton'>update</div><div class='myButton' >cancel</div></div>";        
                        var new_left = loc.left + (100);
                            $('#editPane').css({'left':100,'top':loc.top});
                            $('#editPane').show();
                            $('#editPane').empty();
                            $('#editPane').animate({'left':new_left});
                            $("#editPane").append(editor);

                      //  alert(editor);
                   /* 
                        $.post('../includes/editSubdepts.php',{'subdept_id':subdept_id},function (data) {

                            var new_left = loc.left + (100);
                            $('#editPane').show();
                            $('#editPane').empty();
                            $('#editPane').animate({'left':new_left,'top':loc.top});
                            $('#editPane').append(data);

                        });
//                        alert(action);*/
                    break;
                case 'delete':
                        
                    //should this be implemented, or just hide the subdept?
                        alert(action);
                    break;
            }

        });

        //$(this).keypress(function(press) {alert(press.keyCode)});


});
$(window).load(function (){


});

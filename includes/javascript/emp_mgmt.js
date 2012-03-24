$(document).ready(function() {
    
//    alert('in');
    $('#show_schedule').live('click',function(event){
       
       event.preventDefault();
        event.stopPropagation();
        
        var emp_no = $('#emp_no_label').html();


//alert(emp_no);

        $.post('./getSchedule.php',{'submit':'submit','emp_no':emp_no},function(data){
            
                $('#schedule_pane').html(data);

            });
        
        });

    $('button.edit_employee').live('click',function(event){
        
            event.preventDefault();
            event.stopPropagation();

            var emp_no = $(this).parents().siblings('.emp_no').html();
            //alert(emp_no);
            window.location = '../admin/emp_mgmt.php?emp_no=' + emp_no;
        
        });

    $('#change_curr_record').live('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
       
            var emp_no = $('#curr_emp_no').val();
            //alert(emp_no);
            window.location = '../admin/emp_mgmt.php?emp_no=' + emp_no;
        
        
        });

    
    });

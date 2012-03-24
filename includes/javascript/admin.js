$(document).ready(function() {
    
        $('.admin_menu_item').click(function(event){
                //event.preventDefault();
                event.stopPropagation();
                var output = $(this).children('a').click();
                return false;
                //alert(output);
            });
    
        $('.admin_menu_item a').click(function(event){
                //event.preventDefault();
                //alert('hey');
                event.stopPropagation();
                window.location.href = $(this).attr('href');
            
            }); 
    
    
    });

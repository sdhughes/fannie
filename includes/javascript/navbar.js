$(document).ready(function () {
	//includes/navbar.html
	$('.menu_list').hide();

/*

$('#fannie').hoverIntent(function() {
        $(this).animate('color','red');
        alert('hover');
    },function() {
        $(this).animate('color','green');
        alert('intent');

    });
*/  	
    //For the Navigation bar
    function slideDownMenu() {

			//find all menu items in this container
            var menulist = $(this).find('.menu_list');

            //pretty up the menus, they won't be a good width for some reason
			//menulist.find('li').last().css('border-bottom','none');
			
            menulist.show();
			//$(this).find('.menu_list, ul, li').css('width', "190px");
    }

    function menuLeave(event) {
			$('.menu_list').hide();
            event.stopPropagation();
    }

    var settings = { sensitivity: 5,
                     interval: 150,
                     over: slideDownMenu,
                     out: menuLeave}


    $('.menu_category').hoverIntent(settings);

    $('.menu_category').live("mouseleave",function(){
        });


});

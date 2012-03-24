$(document).ready(function() {


	$(".lab_link").live('click',function(){
     		window.location=$(this).find("a").attr("href");
     		return false;
	});
});

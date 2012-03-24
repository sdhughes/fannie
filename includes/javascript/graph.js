$(document).ready(function(){
   
    var options = {
                    lines: { show: true },
                    points: { show: true, hoverable:true },
                    grid: { hoverable: true, clickable: true },
                    dataType: "json"
                };
    var data = [];
    
    $.plot($('#graphMe'),data,options);

    function alertMe() {alert('me');}


    var alreadyFetched = {};

        $('input[type=submit]').click(function(){
            var button = $(this);

            var dataURL = '../reports/returnRandomSales.php';

            var date1 = $('#date1').val();
            var date2 = $('#date2').val();
            var upc = $('#upc').val();

            function onDataReceived (series) {
                var myData =series.data;

                if (!alreadyFetched[series.label]) {
                    alreadyFetched[series.label] = true;
                    data.push(series);
                }
                alert(data);
                //$.plot($('#graphMe'),data,options);

		$.plot($('#graphMe'),[[0, 12], [7, 12], [7, 2.5], [12, 2.5]]);

            }

            //alert(dataurl);
            $.post(dataURL,{'upc':upc,'date1':date1,'date2':date2,'submit':'submit'}, onDataReceived );


        });
    
});

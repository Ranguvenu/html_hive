
function popup(name){
	// alert('hello');
	// $('#dialog').onclick(function(){
	// 	$('#page-course-view-topics').css({opacity:"0.5"});
	// });
	require(['jquery', 'jqueryui', 'block_userdashboard/jquery.dataTables'], function($) {
		var buttonname = $("#progressbardisplay_course").attr('data-name');
		$("#dialog").dialog({
							title: 'Activity Status For '+buttonname,
		                    resizable: 'true',
							width: '60%',
							modal: true,
		}); 
	// $("#scrolled").css({height:"300px", overflow:"auto"});
	// console.log($('#scrolltable'));
var table_rows = $('#scrolltable tr');
// console.log(table.length);
	if(table_rows.length>6){	
		$('#scrolltable').dataTable({
			"searching": false,
			"language": {
            	"paginate": {
                	"next": ">",
                	"previous": "<"
            	}
        	},
        	"pageLength": 5,
		});
	}
});

}
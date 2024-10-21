$(document).on('change','#id_costcenter', function() {
	var costcentervalue = $(this).find("option:selected").val();
	 if (costcentervalue !== null) {
		$.ajax({
			method: "GET",
			dataType: "json",
			url: M.cfg.wwwroot + "/local/users/ajax.php?action=departmentlist&costcenter="+costcentervalue,
      		success: function(data){
	          	var template = '<option value=0>Select Departments</option>';
	         	$.each( data.data, function( index, value) {
			         template +=	'<option value = ' + value.id + ' >' +value.fullname + '</option>';
		        });
	          	$("#id_department").html(template);
      		}
		});
	} 
});
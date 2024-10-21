$(document).ready(function() {
$("#supervisorid").select2();
$("#reportingmanagerid").select2();
$("#position").select2();
$("#country").select2();
$("#id_bands").select2();
$("#id_department").select2();
$("#id_subdepartment").select2();
$("#id_sub_sub_department").select2();
//$("#id_band").select2();

});
//Ajax Function For The Department By Ravi_369

function target_audience_toggle(target_field, id) {
	if (target_field == 'department') {
		$(".toggle_department_content_"+id).slideToggle("fast");
		$(".toggle_department_content_"+id).toggleClass("toggle_open");
		$(".toggle_department_"+id+" .view_less").toggleClass("hidden");
		$(".toggle_department_"+id+" .view_more").toggleClass("hidden");
	}else if (target_field == 'subdepartment') {
		$(".toggle_subdepartment_content_"+id).slideToggle("fast");
		$(".toggle_subdepartment_content_"+id).toggleClass("toggle_open");
		$(".toggle_subdepartment_"+id+" .view_less").toggleClass("hidden");
		$(".toggle_subdepartment_"+id+" .view_more").toggleClass("hidden");
	}else if(target_field == 'subsubdepartment'){
		$(".toggle_subsubdepartment_content_"+id).slideToggle("fast");
		$(".toggle_subsubdepartment_content_"+id).toggleClass("toggle_open");
		$(".toggle_subsubdepartment_"+id+" .view_less").toggleClass("hidden");
		$(".toggle_subsubdepartment_"+id+" .view_more").toggleClass("hidden");
	}else if (target_field == 'emp_band') {
		$(".emp_band_content_"+id).slideToggle("fast");
		$(".emp_band_content_"+id).toggleClass("toggle_open");
		$(".emp_band_"+id+" .view_less").toggleClass("hidden");
		$(".emp_band_"+id+" .view_more").toggleClass("hidden");
	}
}
	
var selText;
$('#id_costcenterid').on('change', function() {
	selText ="";
    
  $('#id_costcenterid option:selected').each(function () {
     var $this = $(this);
	 
     if(selText !==''){
    
      selText = selText.concat(","); 
      selText = selText.concat($this.val());
     }
     else 
     
     	selText=$this.val();
		    
  });
  //alert(selText);
  if(selText!=''){
	
  	$.ajax({
   
			method: "GET",
			dataType: "json",
			url: M.cfg.wwwroot + "/local/users/ajax.php?costcenter="+selText,
			
      success: function(data){
        
            var template = '';
            $.each( data.data, function( index, value) {
             console.log(index);
             console.log(value);
					template +=	'<option value = ' + value.id + ' >' +value.fullname + '</option>';
          
				});
                
                $("#id_department").html(template);
            }
    
			});
  }else{
  
   $('#id_subdepartment').val('').trigger('change');
   $('#id_subdepartment').html('').select2({data: {id:null, text: null}});

  }
 $('#id_subdepartment').html('').select2({data: {id:null, text: null}});
  //$('#id_subdepartment').trigger('change');
});



var selText;
$('#id_costcenterid').on('change', function() {
	selText ="";
    
  $('#id_costcenterid option:selected').each(function () {
     var $this = $(this);
	 
     if(selText !==''){
    
      selText = selText.concat(","); 
      selText = selText.concat($this.val());
     }
     else
     
     	selText=$this.val();
		    
  });
  //alert(selText);
  if(selText!=''){
	
  	$.ajax({
   
			method: "GET",
			dataType: "json",
			url: M.cfg.wwwroot + "/local/users/ajax.php?band="+selText,
			
      success: function(data){
        
            var template = '';
            $.each( data.data, function( index, value) {
             console.log(index);
             console.log(value);
					template +=	'<option value = ' + value.band + ' >' +value.band + '</option>';
          
				});
                
                $("#id_bands").html(template);
            }
    
			});
  }else{
  
   $('#id_subdepartment').val('').trigger('change');
   $('#id_subdepartment').html('').select2({data: {id:null, text: null}});

  }
 $('#id_subdepartment').html('').select2({data: {id:null, text: null}});
  //$('#id_subdepartment').trigger('change');
});





var selText;
$('#id_department').on('change', function() {
	selText ="";
  $('#id_department option:selected').each(function () {
     var $this = $(this);
	 
     if(selText !==''){
    
      selText = selText.concat(","); 
      selText = selText.concat($this.val());
     }
     else
     
     	selText=$this.val();
		    
  });
  //alert(selText);
  if(selText!=''){
	
  	$.ajax({
   
			method: "GET",
			dataType: "json",
			url: M.cfg.wwwroot + "/local/users/ajax.php?enroldept="+selText,
			
      success: function(data){
        
            var template = '';
            $.each( data.data, function( index, value) {
             console.log(index);
             console.log(value);
					template +=	'<option value = ' + value.id + ' >' +value.fullname + '</option>';
          
				});
                
                $("#id_subdepartment").html(template);
            }
    
			});
  }else{
  
   $('#id_subdepartment').val('').trigger('change');
   $('#id_subdepartment').html('').select2({data: {id:null, text: null}});

  }
 $('#id_subdepartment').html('').select2({data: {id:null, text: null}});
  //$('#id_subdepartment').trigger('change');
});
//Ajax Function For The Sub Department By Ravi_369
var selText;
$('#id_subdepartment').on('change', function() {
	selText ="";
  $('#id_subdepartment option:selected').each(function () {
     var $this = $(this);   
     if(selText !==''){
    
      selText = selText.concat(","); 
      selText = selText.concat($this.val());
     }
     else
     
     	selText=$this.val();
  });
  
  if(selText!=''){
  	$.ajax({
   
			method: "GET",
			dataType: "json",
			url: M.cfg.wwwroot + "/local/users/ajax.php?enrolsubdept="+selText,
			
      success: function(data){
        
        var template = '';
               	$.each( data.data, function( index, value) {
                console.log(index);
                console.log(value);
					      template +=	'<option value = ' + value.id + ' >' +value.fullname + '</option>';
          
				});
                 
                $("#id_sub_sub_department").html(template);
            }
    
			});
  }else{
	$('#id_sub_sub_department').val('').trigger('change');
    $('#id_sub_sub_department').html('').select2({data: {id:null, text: null}});

  }
   $('#id_sub_sub_department').html('').select2({data: {id:null, text: null}});
});

$(document).ready(function() {
  $("#id_skill,#id_level,#id_grade,#id_costcenterid").select2();

});

//Supervisor List
//$('#update').on('click', function() {
// alert("hi");
//        var origEmail = $('#next_val').val();
//        alert(origEmail);
//        var costcentervalue = $(this).find("option:selected").val();
//       
//   if (costcentervalue != 0) {
//		$.ajax({
//			method: "GET",
//			dataType: "json",
//			url: M.cfg.wwwroot + "/local/users/ajax.php?supervisor="+costcentervalue,
//			
//      success: function(data){
//        
//        var template = '<option value=0>select</option>';
//               	$.each( data.data, function( index, value) {
//             console.log(index);
//             console.log(value);
//					template +=	'<option value = ' + value.id + ' >' +value.username + '</option>';
//          
//				});
//                 
//                $("#supervisorid").html(template);
//            }
//    
//			});
//	} else {
//     $("#supervisorid").html(template);
//  }
////$('#id_department').trigger('change');
////$('#id_subdepartment').trigger('change');
////$('#id_sub_sub_department').trigger('change');
//});



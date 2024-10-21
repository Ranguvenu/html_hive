$(document).ready(function(){
    $(".assign_training_at").select2();
    
    $(".ilp-assign-course").select2({
        placeholder: "Select Courses"
    });
    $( ".assign_courses_container" ).hide();
    
    //$(".ilp-assign-users").select2({
    //    placeholder: "Select Users"
    //});
    $( ".assign_users_container" ).hide();
});

function assign_courses_form_toggle() {
    $( ".assign_courses_container" ).slideToggle( "slow" );
    $("#plan_courses .assigning").toggleClass('form_shown');
}

function assign_users_form_toggle() {
    $( ".assign_users_container" ).slideToggle( "slow" );
    $("#plan_users .assigning").toggleClass('form_shown');
}

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
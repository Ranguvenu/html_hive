//this js added by sharath for moduletype selection
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events'],
        function($, Str, ModalFactory) {
    return {
        init: function() {
            $(document).on('click', '.moduletype', function() {
              var moduletype = $("input[name='moduletype']:checked").val();
              var planid = $("input[name=planid]").val();
                $.ajax({
                    method: "GET",
                    dataType: "json",
                    url: M.cfg.wwwroot + "/local/learningplan/ajax.php?moduletype="+moduletype+"&planid="+planid,

                    success: function(data){
                      if(data){
                        var moduletypetemplate = '<option value="">--Select '+moduletype+'--</option>';
                      }else{
                        var moduletypetemplate = '';
                      }

                      $.each( data, function( index, value) {
                        moduletypetemplate += '<option value = ' + index + ' >' +value+ '</option>';
                      });

                      $("#id_learning_plan_courses_").html(moduletypetemplate);
                    }
                });
          });

        }
    };
});
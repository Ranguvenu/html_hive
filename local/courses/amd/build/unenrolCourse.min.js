/**
 * Add a create new group modal to the page.
 *
 * @module     local_courses/createCoursetype
 * @class      fmsapi
 * @package    local_fmsapi
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_courses/jquery.dataTables', 'jquery', 'core/str', 'core/ajax', 'core/yui', 'jqueryui'],
function (DataTable, $, Str, Ajax, Y) {

  return /** @alias module:local_fmsapi/fmsapi */ {
    // Public variables and functions.
    /**
    * @param {string} args
     * @return {Promise}
     */
      
          Datatable: function (args) {
            Str.get_strings([{
              key: 'search',
              //component: 'local_costcenter',
            }]).then(function (str) {
              $('#unenrol_courses').dataTable({
                "searching": true,
                "responsive": true,
                "aaSorting": [[ 0, "desc" ]],
                "lengthMenu": [[20, 40, 60, 80, 100, -1], [20, 40, 60, 80, 100,  "All"]],
                "aoColumnDefs": [{ 'bSortable': false, 'aTargets': [0,1,2,3,4] }],
                language: {
                  search: "_INPUT_",
                  searchPlaceholder: str[0],
                  "paginate": {
                    "next": ">",
                    "previous": "<"
                  }
                }
              });
            }.bind(this));
          },
         unenrolSelf: function (args) {
            //unenrolerror
            var reason = $('.reason').val();
          
            if (args.confirmStatus == 1 && reason.trim()!=''){
                //if(!$.trim(reason)) {
                    var params = {};
                    params.courseid = args.courseid;
                    params.confirmStatus = args.confirmStatus;
                    params.contextid = 1;
                    params.enrolid = args.enrolid;
                    params.reason = reason;
                    var promise = Ajax.call([{
                        methodname: 'local_courses_unenrol_course',
                        args: params
                    }]);
                    promise[0].done(function(resp) {
                       var resp = JSON.parse(resp);
                       if(resp == 0){
                        window.location.href = M.cfg.wwwroot + "/";
                       }else{
                        window.location.href = M.cfg.wwwroot + "/course/view.php?id="+args.courseid;
                       }                       
                    }).fail(function(ex){
                        // do something with the exception
                        console.log(ex);
                    });
               /*  }else{
                    $(".unenrolerror").attr("style", "display:block");
                    return false;
                } */
            }else  if (args.confirmStatus == 1 && reason.trim()==''){
                $(".unenrolerror").attr("style", "display:block;color:red");
            }else if (args.confirmStatus == 0){
                window.location.href = M.cfg.wwwroot + "/course/view.php?id="+args.courseid;
            }
           
         }
     };
 });

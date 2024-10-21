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
         $('#fmsapi_list').dataTable({
           "searching": true,
           "responsive": true,
           "aaSorting": [[ 0, "desc" ]],
           "lengthMenu": [[20, 40, 60, 80, 100, -1], [20, 40, 60, 80, 100,  "All"]],
           "aoColumnDefs": [{ 'bSortable': false, 'aTargets': [0] }],
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
     }
   };

 });
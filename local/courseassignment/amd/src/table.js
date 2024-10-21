// $(document).ready(function() {
//     $('#tab').DataTable();
//     // alert('HELLO');
// });

/**
 * Add a create new group modal to the page.
 *
 * @module     local/message
 * @package    local_message
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 define(['jquery', 'core/ajax', 'local_courseassignment/dataTables'],
 function($, Ajax, DataTable) {
return {
 load : function(){
     let data = $("#tab").data();
     var table = $("#tab").DataTable({
       autoWidth: true,
        columnDefs: [
            {
                targets : ['_all'],
                className : 'mdc-data-table__cell'
            }
          ]
     });
 }
}
});

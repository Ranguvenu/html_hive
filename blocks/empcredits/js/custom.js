//$(document).ready(function() {
//    $('#coursesinfo').DataTable( {
//        "iDisplayLength": 10,
//       "lengthMenu": [[10,20, 30, 40, 50, -1], [10,20, 30, 40, 50, "All"]]
//    } );
//} );


//$(document).ready(function() {
//    $('#facilitatorinfo').DataTable( {
//        "iDisplayLength": 10,
//       "lengthMenu": [[10,20, 30, 40, 50, -1], [10,20, 30, 40, 50, "All"]]
//    } );
//} );
//
// $('[data-toggle="tabajax"]').click(function(e) {
//     var $this = $(this),
//         loadurl = $this.attr('href'),
//         targ = $this.attr('data-target');

//     $.get(loadurl, function(data) {
//         $(targ).html(data);
//     });

//     $this.tab('show');
//     return false;
// });
$( function() {
    $( "#tabs" ).tabs({
      beforeLoad: function( event, ui ) {
        ui.jqXHR.fail(function() {
          ui.panel.html(
            "Couldn't load this tab. We'll try to fix this as soon as possible. " +
            "If this wouldn't be a demo." );
        });
      }
    });
  } );
// $(document).ready(function() {

// } );
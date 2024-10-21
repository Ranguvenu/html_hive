define([
    'local_courses/jquery.dataTables',
    'jquery','core/str','core/modal_factory', 'core/ajax'
], function(DataTable, $,Str,ModalFactory, Ajax){
    return {
        usersdatatable : function(args) {
            params = [];
            params.action = args.action;
            params.courseid = args.courseid;
            var oTable = $('#course_users').dataTable({
                'bInfo': false,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/courses/ajax.php',
                    "data": params
                },
                "bLengthChange": false,
                "language": {
                    "paginate": {
                        "next": ">",
                        "previous": "<"
                    },
                    'processing': '<img src='+M.cfg.wwwroot + '/local/ajax-loader.svg>'
                },
                "oLanguage": {
                  "sZeroRecords": "No users enrolled to this course"
                },
            });
        },
        smedatatable: function (args) {
            Str.get_strings([{
              key: 'search',
              //component: 'local_costcenter',
            }]).then(function (str) {
              $('#sme_course_users').dataTable({
                "searching": true,
                "responsive": true,
                "aaSorting": [[ 1, "desc" ]],
                "lengthMenu": [[10, 15, 25, 50, 100, -1], [10, 15, 25, 50, 100, "All"]],
                "aoColumnDefs": [{ 'bSortable': false, 'aTargets': [1] }],
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
        load: function(){
        },//                mallikarjun added for reissue certificate
          reissueCertificate: function(element) {
          var enrollmentid = element.userid;
          // console.log(element);
            return Str.get_strings([{
                key: 'confirm',
            }, {
                key: 'reissue_certificate',
                component: 'local_courses',
                param : element
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.DEFAULT,
                    body: s[1],
                    footer: '<button type="button" class="btn btn-primary" data-action="save">'+M.util.get_string("yes", "moodle")+'</button>&nbsp;' +
            '<button type="button" class="btn btn-secondary" data-action="cancel">'+M.util.get_string("no", "moodle")+'</button>'
                }).done(function(modal) {
                    this.modal = modal;
                     modal.getRoot().find('[data-action="save"]').on('click', function() {
                        element.confirm = true;
                        console.log(element);
                        var params = {};
                        params.id = element.id;
                        params.fullname = element.fullname;
                        params.userid = element.userid;
                        params.moduleid = element.moduleid;
                        var promise = Ajax.call([{
                            methodname: 'local_courses_' + element.action,
                            args: params
                        }]);
                        promise[0].done(function() {
                            window.location.href = window.location.href;
                        }).fail(function(ex) {
                            // do something with the exception
                             console.log(ex);
                        });
                    }.bind(this));
                    modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                        modal.setBody('');
                        modal.hide();
                    });
                    modal.show();
                }.bind(this));
            }.bind(this));
        },
    };
});

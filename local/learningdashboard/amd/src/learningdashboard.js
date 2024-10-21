/**
 * Add a create new group modal to the page.
 *
 * @module     local_learningdashboard/learningdashboard
 * @class      learningdashboard
 * @package    local_learningdashboard
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
define(['local_courses/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui', 'core/templates', 'local_learningdashboard/graph'],
    function (dataTable, $, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y, Templates, Graph) {

        /**
        * Constructor
        *
        * @param {object} args
        *
        * Each call to init gets it's own instance of this class.
        */
        var learningdashboard = function (args) {
            this.contextid = args.contextid ? args.contextid : 1;
            this.args = args;
            this.init(args);
            this.tunit = args.tunit;
        };

        /**
        * @var {Modal} modal
        * @private
        */
        learningdashboard.prototype.modal = null;

        /**
        * @var {int} contextid
        * @private
        */
        learningdashboard.prototype.contextid = -1;

        /**
        * Initialise the class.
        *
        * @param {String} selector used to find triggers for the new group modal.
        * @private
        * @return {Promise}
        */
        learningdashboard.prototype.init = function (args) {
            // Fetch the title string.
            var self = this;
            var popuptittle = this.args.ismanager ? this.args.userfullname : this.args.creditstype
            var coursecount = this.args.coursecount;
            var params = {title : popuptittle, count: coursecount}
            var head = {
                key: 'popuptittle', component: 'local_learningdashboard',param : params
            };
            customstrings = Str.get_strings([head,
                {
                    key: 'squads', component: 'local_learningdashboard'
                },
                ]);



            return customstrings.then(function (strings) {
                // Create the modal.
                var title = '';
                if (this.args.callback == 'coursespopup') {
                    title = strings[0];
                } else if (this.args.callback == 'squads') {
                    title = strings[1];
                } else if (this.args.callback == 'users') {
                    title = strings[2];
                } else if (this.args.callback == 'instructors') {
                    title = strings[3];
                }else if (this.args.callback == 'reviews') {
                    title = strings[5];
                }else if (this.args.callback == 'gradeshistory') {
                    title = strings[6];
                }
                return ModalFactory.create({
                    type: ModalFactory.types.CANCEL,
                    title: title,
                    body: this.getBody(),
                });
            }.bind(this)).then(function (modal) {
                // Keep a reference to the modal.
                this.modal = modal;
                // Forms are big, we want a big modal.
                self.modal.setLarge();

                // We want to reset the form every time it is opened.
                self.modal.getRoot().on(ModalEvents.hidden, function () {
                    self.modal.setBody('');
                    self.modal.hide();
                    self.modal.destroy();
                }.bind(this));

                // We want to reset the form every time it is opened.
                self.modal.getRoot().on(ModalEvents.cancel, function () {
                    self.modal.setBody('');
                    self.modal.hide();
                    self.modal.destroy();
                }.bind(this));
                this.modal.getRoot().on(ModalEvents.bodyRendered, function () {
                    this.dataTableshow(args.tunit);
                }.bind(this));
                self.modal.show();
                return this.modal;
            }.bind(this));
        };
        learningdashboard.prototype.dataTableshow = function (tunit) {
            // console.log(tunit);
            $.fn.dataTable.ext.errMode = 'none';
            $('.managementpopuptable_details').dataTable({
                'bPaginate': true,
                'bFilter': true,
                'bLengthChange': true,
                'lengthMenu': [
                    [5, 10, 25, 50, 100, -1],
                    [5, 10, 25, 50, 100, 'All']
                ],
                'language': {
                    'emptyTable': 'No Records Found',
                    'paginate': {
                        'previous': '<',
                        'next': '>'
                    }
                },

                'bProcessing': true,
            });
        };
        /**
        * @method getBody
        * @private
        * @return {Promise}
        */
        learningdashboard.prototype.getBody = function (args) {
            // Get the content of the modal.
            console.log(this.args);
            return Fragment.loadFragment(this.args.component, this.args.callback, 1, this.args);
        };
        /**
        * @method getFooter
        * @private
        * @return {Promise}
        */
        learningdashboard.prototype.getFooter = function (customstrings) {
            var footer = '';
            footer = '<button type="button" class="btn btn-secondary" data-action="cancel">' + customstrings[0] + '</button>';
            return footer;
            // }.bind(this));
        };
        /**
        * @method getFooter
        * @private
        * @return {Promise}
        */
        learningdashboard.prototype.getcontentFooter = function () {
            return Str.get_strings([{
                key: 'cancel'
            }]).then(function (s) {
                $footer = '<button type="button" class="btn btn-secondary" data-action="cancel">' + s[1] + '</button>';
                return $footer;
            }.bind(this));
        };
        var users;
        return /** @alias module:core_group/learningdashboard */users= {
            // Public variables and functions.
            /**
             * Attach event listeners to initialise this module.
             *
             * @method init
             * @param {string} selector The CSS selector used to find nodes that will trigger this module.
             * @param {int} contextid The contextid for the course.
             * @return {Promise}
             */
            init: function (args) {
                // this.Datatable();
                return new learningdashboard(args);
            },
            Datatable: function () {

            },
            teamsstatus: function (args) {
                $('.learningdashboard_team_status').dataTable({
                    'bPaginate': true,
                    'bFilter': true,
                    'bLengthChange': false,
                    "pageLength" : 5,
                    // 'lengthMenu': [
                    //     [5, 10, 25, 50, 100, -1],
                    //     [5, 10, 25, 50, 100, 'All']
                    // ],
                    'language': {
                        'emptyTable': 'No Records Found',
                        'paginate': {
                            'previous': '<',
                            'next': '>'
                        }
                    },

                    'bProcessing': true,
                    'ordering': false,
                });

                $.fn.dataTable.ext.errMode = 'none';
            },
            creditsdata: function (args) {
                $(document).on('click', '.completed, .pending', function () {
                    creditsview(this);
                });
                function creditsview(obj){
                    var status = 'completed'
                        if($(obj).hasClass("pending")){
                            status = 'pending'
                            $(obj).addClass('active');
                            $('.completed').removeClass('active');
                        }
                        if(status == 'completed'){
                            $(obj).addClass('active');
                            $('.pending').removeClass('active');
                        }        
                    const params = { status: status};
                    var promise = Ajax.call([{
                        methodname: 'local_learningdashboard_creditsdata_view',
                        args: params,
                        dataType : 'json'
                    }]);
                    $("#creditstables").empty();
                    promise[0].done(function (resp) {
                        console.log(resp);
                        if (resp.totalcount == 0) {
                            $('#creditstables').html('<div class="text-center calendar_events attempt_text"><h4>No Events Available on this Date.</></h4></div>');
                        } else {
                            var data = Templates.render('local_learningdashboard/creditsdata_view',  resp.records );
                            data.then(function (html, js) {
                                $('#creditstables').html(html);
                            });
                        }
                        Graph.init(resp.graphdata);

                        const obj = JSON.parse(resp.records.data);
                        $('head').append(obj.javascript);
                    }).fail(function (ex) {
                        // do something with the exception
                        console.log(ex);
                    });
                }
                creditsview();                
            },
            
            load: function () {
                // $(".completed").trigger("click");
            }
        };
    });

/**
 * Add a create new group modal to the page.
 *
 * @module     local_onlinetests/NewOnlinetest
 * @class      NewOnlinetest
 * @package    local_onlinetests
 * @copyright  2019 Sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui', 'local_onlinetests/jquery.dataTables'],
        function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
 
    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var NewOnlinetest = function(selector, contextid, testid) {
        this.contextid = contextid;
        this.testid = testid;
        var self = this;
        self.init(selector);
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    NewOnlinetest.prototype.modal = null;
 
    /**
     * @var {int} contextid
     * @private
     */
    NewOnlinetest.prototype.contextid = -1;
 
    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewOnlinetest.prototype.init = function(selector) {
        var self = this;
        $(document).on('click', selector, function(){
            
            var editid = $(this).data("value");
            if (editid) {
                self.testid = editid;
                update_string = Str.get_string('editonlinetests', 'local_onlinetests');
            } else {
                self.testid = -1;
                update_string = Str.get_string('createonlinetest', 'local_onlinetests');
            }
            return update_string.then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: title,
                    body: self.getBody()
                });
            }.bind(self)).then(function(modal) {
                
                // Keep a reference to the modal.
                self.modal = modal;
                self.modal.show();
                // Forms are big, we want a big modal.
                self.modal.setLarge();
                self.modal.getRoot().addClass('openLMStransition local_onlinetests');
                self.modal.getRoot().animate({"right":"0%"}, 500);
     
                // We want to reset the form every time it is opened.
                self.modal.getRoot().on(ModalEvents.hidden, function() {
                    self.modal.getRoot().animate({"right":"-85%"}, 500);
                    setTimeout(function(){
                        modal.destroy();
                    }, 1000);
                    self.modal.setBody('');
                }.bind(this));
    
                // We want to hide the submit buttons every time it is opened.
                self.modal.getRoot().on(ModalEvents.shown, function() {
                    self.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
                }.bind(this));
     
                // We catch the modal save event, and use it to submit the form inside the modal.
                // Triggering a form submission will give JS validation scripts a chance to check for errors.
                self.modal.getRoot().on(ModalEvents.save, self.submitForm.bind(self));
                // We also catch the form submit event and use it to submit the form with ajax.
                self.modal.getRoot().on('submit', 'form', self.submitFormAjax.bind(self));
                
                return this.modal;
            }.bind(this));
        });        
    };
 
    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    NewOnlinetest.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal.
        var params = {testid:this.testid, jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment('local_onlinetests', 'new_onlinetest_form', this.contextid, params);
    };
 
    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {modal}
     */
    NewOnlinetest.prototype.handleFormSubmissionResponse = function(testid) {
        this.modal.hide();
        // We could trigger an event instead.
        // Yuk.
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });

        // modal to show the procedure thereof
        var params = { id: testid, sesskey: M.cfg.sesskey};
        var returndata =  Fragment.loadFragment('local_onlinetests', 'addquestions_or_enrol', this.contextid, params);


        ModalFactory.create({
            title: Str.get_string('pluginname', 'local_onlinetests'),
            body: returndata
        }).done(function(modal) {
            // Do what you want with your new modal.
            modal.show();
            modal.getRoot().find('[data-action="hide"]').on('click', function() {
            modal.hide();
            setTimeout(function(){
                 window.location.reload();
            }, 500);
            });
        });
    };
 
    /**
     * @method handleFormSubmissionFailure
     * @private
     * @return {Promise}
     */
    NewOnlinetest.prototype.handleFormSubmissionFailure = function(data) {
        // Oh noes! Epic fail :(
        // Ah wait - this is normal. We need to re-display the form with errors!
        this.modal.setBody(this.getBody(data));
    };
 
    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {Event} e Form submission event.
     */
    NewOnlinetest.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
 
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();

        // Now we can continue...
        var promise = Ajax.call([{
            methodname: 'local_onlinetests_submit_create_onlinetest_form',
            args: {contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }]);
        var self =this;
        promise[0].done(function(resp){
            self.handleFormSubmissionResponse(resp);        
        });
    };
 
    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    NewOnlinetest.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };

    return /** @alias module:local_onlinetests/NewOnlinetest */ {
        // Public variables and functions.
        /**
         * Attach event listeners to initialise this module.
         *
         * @method init
         * @param {string} selector The CSS selector used to find nodes that will trigger this module.
         * @param {int} contextid The contextid for the test.
         * @param {int} testid examid
         * @return {Promise}
         */
        init: function(selector, contextid, testid) {
            return new NewOnlinetest(selector, contextid, testid);
        },
        getdepartmentlist: function() {
            // $(document).on('change', '#id_costcenterid', function() {
            //     var costcentervalue = $(this).find("option:selected").val();
            //     var title = M.util.get_string("select_department", "local_onlinetests");
            //     if (costcentervalue && costcentervalue != 'null') {
            //         var promise = Ajax.call([{
            //             methodname: 'local_costcenter_departmentlist',
            //             args: {
            //                 orgid: costcentervalue
            //             },
            //         }]);
            //         promise[0].done(function(resp) {
            //             var template =  '<option value=null>Select Department</option>';                                    
            //             $.each(JSON.parse(resp.departments), function( index, value) {
            //                 template += '<option value = ' + value.id + ' >' +value.fullname + '</option>';
            //             });
            //             $('#id_departmentid').html(template);
            //         }).fail(function() {
            //                 // do something with the exception
            //             alert('Error occured while processing request');
            //             window.location.reload();
            //         });
            //     } else {
            //         var template =  '<option value=0>All</option>';
            //         $('#id_departmentid').html(template);
            //     }
            // });
            // $(document).on('change', '#id_costcenterid', function() {
            //     var costcentervalue = $(this).find("option:selected").val();
            //     var title = M.util.get_string("select_department", "local_onlinetests");
            //     if (costcentervalue && costcentervalue != 'null') {
            //         var promise = Ajax.call([{
            //             methodname: 'local_costcenter_subdepartmentlist',
            //             args: {
            //                 orgid: costcentervalue
            //             },
            //         }]);
            //         promise[0].done(function(resp) {
            //             var template =  '<option value=null>All</option>';                                    
            //             $.each(JSON.parse(resp.departments), function( index, value) {
            //                 template += '<option value = ' + value.id + ' >' +value.fullname + '</option>';
            //             });
            //             $('#id_departmentid').html(template);
            //         }).fail(function() {
            //                 // do something with the exception
            //             alert('Error occured while processing request');
            //             window.location.reload();
            //         });
            //     } else {
            //         var template =  '<option value=0>All</option>';
            //         $('#id_departmentid').html(template);
            //     }
            // });
            // $(document).on('change', '#id_departmentid', function() {
            //     var departmentvalue = $(this).find("option:selected").val();
            //     var title = M.util.get_string("select_department", "local_onlinetests");
            //     if (departmentvalue && departmentvalue != 'null') {
            //         var promise = Ajax.call([{
            //             methodname: 'local_costcenter_subdepartmentlist',
            //             args: {
            //                 orgid: costcentervalue
            //             },
            //         }]);
            //         promise[0].done(function(resp) {
            //             var template =  '<option value=0>All</option>';                                    
            //             $.each(JSON.parse(resp.subdepartments), function( index, value) {
            //                 template += '<option value = ' + value.id + ' >' +value.fullname + '</option>';
            //             });
            //             $('#id_departmentid').html(template);
            //         }).fail(function() {
            //                 // do something with the exception
            //             alert('Error occured while processing request');
            //             window.location.reload();
            //         });
            //     } else {
            //         var template =  '<option value=null>Select Department</option>';
            //         $('#id_departmentid').html(template);
            //     }
            // });
        },
        enrolledusers: function(args) {
            var clicked = $('.onlinetest_users_count').hasClass('clicked');
            $('.onlinetest_users_count').addClass('clicked');
            if(!clicked){
                // modal to show the procedure thereof
                var params = { testid: args.testid, type:args.type};
                var returndata =  Fragment.loadFragment('local_onlinetests', 'enrolled_users', args.contextid, params);

                ModalFactory.create({
                    title: args.testname,
                    body: returndata
                }).done(function(modal) {
                    // Do what you want with your new modal.
                    modal.show();
                    modal.setLarge();
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.setBody('');
                    }.bind(this));
                    modal.getRoot().find('[data-action="hide"]').on('click', function() {
                        modal.hide();
                        $('.onlinetest_users_count').removeClass('clicked');
                        setTimeout(function(){
                             modal.destroy();
                        }, 500);
                    });
                });
            }
        },
        deleteonlinetest: function(elem) {
            return Str.get_strings([{
                key: 'deleteonlinetest',
                component: 'local_onlinetests'
            }, {
                key: 'confirmdelete',
                component: 'local_onlinetests'
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
                        window.location.href ='index.php?delete='+elem+'&confirm=1&sesskey=' + M.cfg.sesskey;
                    }.bind(this));
                    modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                        modal.setBody('');
                        modal.hide();
                    });
                    modal.show();
                }.bind(this));
            }.bind(this));
        },
          suspendonlinetest: function(elem,visible,args) {
            console.log
            return Str.get_strings([{
                key: 'suspendconfirm',
                component: 'local_onlinetests',
                param: args,
            },
            {
                key: 'inactiveconfirm',
                component: 'local_onlinetests',
                param: args,
             
            },
            {
                key: 'activeconfirm',
                component: 'local_onlinetests',
                param: args,
            }]).then(function(s) {
                if (elem.status == "enable") {
                    s[1] = s[1];
                   // var confirm = ModalFactory.types.CONFIRM;
                 } else if (elem.status == "disable") {
                    s[1] = s[2];
                    //var confirm = ModalFactory.types.CONFIRM;
                 }
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.DEFAULT,
                    body: s[1],
                    footer: '<button type="button" class="btn btn-primary" data-action="save">'+M.util.get_string("yes", "local_onlinetests")+'</button>&nbsp;' +
            '<button type="button" class="btn btn-secondary" data-action="cancel">'+M.util.get_string("no", "local_onlinetests")+'</button>'
                }).done(function(modal) {
                    this.modal = modal;
                    modal.getRoot().find('[data-action="save"]').on('click', function() {
                        window.location.href = M.cfg.wwwroot+'/local/onlinetests/index.php?id='+elem.id+'&visible='+elem.visible+'&hide=1&sesskey='+ M.cfg.sesskey;
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
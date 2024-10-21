/**
 * Add a create new group modal to the page.
 *
 * @module     local_costcenter/costcenter
 * @class      NewCostcenter
 * @package    local_costcenter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
        function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
 
    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    //var courseinfo = function(args) {
    //    this.courseid = args.courseid;
    //    //this.planid = args.planid;
    //    var self = this;
    //    self.init(args.selector);
    //};
    //
    ///**
    // * @var {Modal} modal
    // * @private
    // */
    //courseinfo.prototype.modal = null;
    //
    ///**
    // * @var {int} contextid
    // * @private
    // */
    ////courseinfo.prototype.courseid = -1;
    //
    ///**
    // * Initialise the class.
    // *
    // * @param {String} selector used to find triggers for the new group modal.
    // * @private
    // * @return {Promise}
    // */
    //courseinfo.prototype.init = function(args) {
    //    // console.log(args);
    //    //var triggers = $(selector);
    //    var self = this;
    //
    //    // Fetch the title string.
    //    // $('.'+args.selector).click(function(){
    //        
    //
    //        // var editid = $(this).data('value');
    //        // if (editid) {
    //            // self.planid = editid;
    //            //if(self.planid){
    //                //console.log(self.planid);
    //                var head = Str.get_string('viewmore', 'local_catalog');
    //            //}
    //            //else{
    //            //   var head = Str.get_string('adnewlearningplan', 'local_learningplan');
    //            //}
    //            //console.log(self.costcenterid);
    //            //alert(self.costcenterid);
    //        // }
    //        return head.then(function(title) {
    //            // Create the modal.
    //            return ModalFactory.create({
    //                type: ModalFactory.types.SAVE_CANCEL,
    //                title: title,
    //                body: self.getBody()
    //            });
    //        }.bind(self)).then(function(modal) {
    //            
    //            // Keep a reference to the modal.
    //            self.modal = modal;
    //           
    //            self.modal.getRoot().addClass('openLMStransition');
    //            // Forms are big, we want a big modal.
    //            self.modal.setLarge();
    // 
    //            // We want to reset the form every time it is opened.
    //            self.modal.getRoot().on(ModalEvents.hidden, function() {
    //                self.modal.setBody(self.getBody());
    //                self.modal.getRoot().animate({"right":"-85%"}, 500);
    //                setTimeout(function(){
    //                    modal.destroy();
    //                }, 1000);
    //                
    //            }.bind(this));
    //
    //            
    //            // We want to hide the submit buttons every time it is opened.
    //            self.modal.getRoot().on(ModalEvents.shown, function() {
    //                self.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
    //            }.bind(this));
    // 
    //
    //            // We catch the modal save event, and use it to submit the form inside the modal.
    //            // Triggering a form submission will give JS validation scripts a chance to check for errors.
    //            self.modal.getRoot().on(ModalEvents.save, self.submitForm.bind(self));
    //            // We also catch the form submit event and use it to submit the form with ajax.
    //            self.modal.getRoot().on('submit', 'form', self.submitFormAjax.bind(self));
    //
    //            this.modal.show();
    //            this.modal.getRoot().animate({"right":"0%"}, 500);
    //            return this.modal;
    //        }.bind(this));       
    //    
    //    
    //    // });
    //    
    //};
    //
    ///**
    // * @method getBody
    // * @private
    // * @return {Promise}
    // */
    //lpcreate.prototype.getBody = function(formdata) {
    //    if (typeof formdata === "undefined") {
    //        formdata = {};
    //    }
    //    // alert(JSON.stringify(formdata));
    //    // alert(this.planid);
    //    // alert(this.contextid);
    //    // Get the content of the modal.
    //    var params = {planid:this.planid, jsonformdata: JSON.stringify(formdata)};
    //    return Fragment.loadFragment('local_learningplan', 'new_learningplan',this.contextid, params);
    //};
    //
    ///**
    // * @method handleFormSubmissionResponse
    // * @private
    // * @return {Promise}
    // */
    //lpcreate.prototype.handleFormSubmissionResponse = function() {
    //    this.modal.hide();
    //    // We could trigger an event instead.
    //    // Yuk.
    //    Y.use('moodle-core-formchangechecker', function() {
    //        M.core_formchangechecker.reset_form_dirty_state();
    //    });
    //    document.location.reload();
    //};
    //
    ///**
    // * @method handleFormSubmissionFailure
    // * @private
    // * @return {Promise}
    // */
    //lpcreate.prototype.handleFormSubmissionFailure = function(data) {
    //    // Oh noes! Epic fail :(
    //    // Ah wait - this is normal. We need to re-display the form with errors!
    //    this.modal.setBody(this.getBody(data));
    //};
    //
    ///**
    // * Private method
    // *
    // * @method submitFormAjax
    // * @private
    // * @param {Event} e Form submission event.
    // */
    //lpcreate.prototype.submitFormAjax = function(e) {
    //    // We don't want to do a real form submission.
    //    e.preventDefault();
    //
    //    // Convert all the form elements values to a serialised string.
    //    var formData = this.modal.getRoot().find('form').serialize();
    //    // alert(this.contextid);
    //    // Now we can continue...
    //    Ajax.call([{
    //        methodname: 'local_learningplan_submit_learningplan_form',
    //        args: {id:this.planid, contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
    //        done: this.handleFormSubmissionResponse.bind(this, formData),
    //        fail: this.handleFormSubmissionFailure.bind(this, formData)
    //    }]);
    //};
    //
    ///**
    // * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
    // *
    // * @method submitForm
    // * @param {Event} e Form submission event.
    // * @private
    // */
    //lpcreate.prototype.submitForm = function(e) {
    //    e.preventDefault();
    //    var self = this;
    //    self.modal.getRoot().find('form').submit();
    //};
    //return /** @alias module:local_costcenter/newcostcenter */ {
    //        // Public variables and functions.
    //        /**
    //         * Attach event listeners to initialise this module.
    //         *
    //         * @method init
    //         * @param {string} selector The CSS selector used to find nodes that will trigger this module.
    //         * @param {int} contextid The contextid for the course.
    //         * @return {Promise}
    //         */
    //        init: function(args) {
    //          // console.log(args);
    //            // alert(args.contextid);
    //            //return new lpcreate(args);
    //            return new courseinfo(args);
    //        },
    //        load: function(){
    //
    //        },
    //        //deleteConfirm: function(args){
    //        //    // alert('mahesh');
    //        //    // console.log(args);
    //        //    return Str.get_strings([{
    //        //    key: 'confirm'
    //        //    },
    //        //    {
    //        //    key: 'deleteconfirm',
    //        //    component: 'local_learningplan',
    //        //    param : args
    //        //    },
    //        //    {
    //        //    key: 'deleteallconfirm',
    //        //    component: 'local_learningplan'
    //        //    },
    //        //    {   
    //        //        key: 'delete'
    //        //    }]).then(function(s) {
    //        //        ModalFactory.create({
    //        //            title: s[0],
    //        //            type: ModalFactory.types.SAVE_CANCEL,
    //        //            body: s[1]
    //        //        }).done(function(modal) {
    //        //            this.modal = modal;
    //        //            modal.setSaveButtonText(s[3]);
    //        //            modal.getRoot().on(ModalEvents.save, function(e) {
    //        //                e.preventDefault();
    //        //                args.confirm = true;
    //        //                var promise = Ajax.call([{
    //        //                    methodname: 'local_learningplan_' + args.action,
    //        //                    args: args
    //        //                }]);
    //        //                promise[0].done(function(resp) {
    //        //                    window.location.href = window.location.href;
    //        //                }).fail(function(ex) {
    //        //                    // do something with the exception
    //        //                     console.log(ex);
    //        //                });
    //        //            }.bind(this));
    //        //            modal.show();
    //        //        }.bind(this));
    //        //        modal.show();
    //        //    }.bind(this));
    //        //}, 
    //        toggleVisible: function(args){
    //            return Str.get_strings([{
    //                key: 'confirm'+args.visible,
    //                component: 'local_learningplan',
    //            },
    //            {
    //                key: 'activeconfirm'+args.visible,
    //                component: 'local_learningplan',
    //                param : args
    //            },
    //            {
    //                key: 'ok'
    //            }]).then(function(s) {
    //                ModalFactory.create({
    //                    title: s[0],
    //                    type: ModalFactory.types.SAVE_CANCEL,
    //                    body: s[1]
    //                }).done(function(modal) {
    //                    this.modal = modal;
    //                    modal.setSaveButtonText(s[2]);
    //                    // modal.setCancelButtonText(s[2]);
    //                    modal.getRoot().on(ModalEvents.save, function(e) {
    //                        e.preventDefault();
    //                        args.confirm = true;
    //                        var promise = Ajax.call([{
    //                            methodname: 'local_learningplan_' + args.action,
    //                            args: args
    //                        }]);
    //                        promise[0].done(function(resp) {
    //                            window.location.href = window.location.href;
    //                        }).fail(function(ex) {
    //                            // do something with the exception
    //                             console.log(ex);
    //                        });
    //                    }.bind(this));
    //                    modal.show();
    //                    }.bind(this));
    //            }.bind(this));
    //        },
    //};
    return /** @alias module:local_evaluation/newevaluation */ {
        // Public variables and functions.
        /**
         * Attach event listeners to initialise this module.
         *
         * @method init
         * @param {string} selector The CSS selector used to find nodes that will trigger this module.
         * @param {int} contextid The contextid for the course.
         */
        init: function(args) {
            console.log(args);
                $.ajax({
                    type: "GET",
                    url:  "courseinfo.php?id="+args.courseid+"",
                    success: function(returndata) {
                        //Var returned_data is ONLY available inside this fn!
                        console.log(returndata);
                         ModalFactory.create({
                            title: Str.get_string('courseinfo', 'local_catalog'),
                            body: returndata
                          }).done(function(modal) {
                            // Do what you want with your new modal.
                            modal.show();
                            modal.setLarge();
                            modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.getRoot().animate({"right":"-85%"}, 500);
                                setTimeout(function(){
                                modal.destroy();
                            }, 1000);
                            }.bind(this));
                        });    
                    }
                });
        },
        test: function(args){
            $.ajax({
                    type: "GET",
                    url:  "courseinfo.php?id="+args.courseid+"&enrol="+args.enroll+"",
                    success: function(returndata) {
                        console.log(returndata);
                        $(".enrolled"+args.courseid+"").html(returndata);
                    }
            });
        },
        classroominfo: function(args) {
            console.log(args);
                $.ajax({
                    type: "GET",
                    url:  "classrrominfo.php?crid="+args.crid+"",
                    success: function(returndata) {
                        //Var returned_data is ONLY available inside this fn!
                        console.log(returndata);
                         ModalFactory.create({
                            title: Str.get_string('classroom_info', 'local_classroom'),
                            body: returndata
                          }).done(function(modal) {
                            // Do what you want with your new modal.
                            modal.show();
                            modal.setLarge();
                            modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.getRoot().animate({"right":"-85%"}, 500);
                                setTimeout(function(){
                                modal.destroy();
                            }, 1000);
                            }.bind(this));
                        });    
                    }
                });
        },
        programinfo: function(args) {
            console.log(args);
                $.ajax({
                    type: "GET",
                    url:  "classrrominfo.php?programid="+args.programid+"",
                    success: function(returndata) {
                        //Var returned_data is ONLY available inside this fn!
                        console.log(returndata);
                         ModalFactory.create({
                            title: Str.get_string('program_info', 'local_program'),
                            body: returndata
                          }).done(function(modal) {
                            // Do what you want with your new modal.
                            modal.show();
                            modal.setLarge();
                            modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.getRoot().animate({"right":"-85%"}, 500);
                                setTimeout(function(){
                                modal.destroy();
                            }, 1000);
                            }.bind(this));
                        });    
                    }
                });
        },
         certificationinfo: function(args) {
            console.log(args);
                $.ajax({
                    type: "GET",
                    url:  "classrrominfo.php?certificationid="+args.certificationid+"",
                    success: function(returndata) {
                        //Var returned_data is ONLY available inside this fn!
                        console.log(returndata);
                         ModalFactory.create({
                            title: Str.get_string('certification_info', 'local_program'),
                            body: returndata
                          }).done(function(modal) {
                            // Do what you want with your new modal.
                            modal.show();
                            modal.setLarge();
                            modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.getRoot().animate({"right":"-85%"}, 500);
                                setTimeout(function(){
                                modal.destroy();
                            }, 1000);
                            }.bind(this));
                        });    
                    }
                });
        },
        learningplaninfo: function(args) {
            console.log(args);
                $.ajax({
                    type: "GET",
                    url:  "classrrominfo.php?learningplanid="+args.learningplanid+"",
                    success: function(returndata) {
                        //Var returned_data is ONLY available inside this fn!
                        console.log(returndata);
                         ModalFactory.create({
                            title: Str.get_string('learningplan_info', 'local_catalog'),
                            body: returndata
                          }).done(function(modal) {
                            // Do what you want with your new modal.
                            modal.show();
                            modal.setLarge();
                            modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.getRoot().animate({"right":"-85%"}, 500);
                                setTimeout(function(){
                                modal.destroy();
                            }, 1000);
                            }.bind(this));
                        });    
                    }
                });
        },
        load: function () {
            
        }
    };
});
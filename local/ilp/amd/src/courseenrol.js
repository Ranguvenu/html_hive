/**
 * Add a create new group modal to the page.
 *
 * @module     local_costcenter/costcenter
 * @class      courseenrol
 * @package    local_costcenter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'core/str',
        'core/modal_factory',
        'core/modal_events',
        'core/fragment',
        'core/ajax',
        'core/yui',
        'local_ilp/jquery.dataTables'],
        function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {

    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var courseenrol = function(args) {
        this.contextid = args.contextid;
        this.planid = args.planid;
        this.condition = args.condition;
        // alert(this.contextid);
        var self = this;
        self.init(args.selector);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    courseenrol.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    courseenrol.prototype.contextid = -1;

    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    courseenrol.prototype.init = function(args) {
        console.log(args);
        //var triggers = $(selector);
        var self = this;

        var head =  Str.get_string('enrolcourses', 'local_ilp');

       
            return head.then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: title,
                    body: self.getBody()
                });
            }.bind(self)).then(function(modal) {
                
                // Keep a reference to the modal.
                self.modal = modal;
               
                self.modal.getRoot().addClass('openLMStransition local_costcenter');
                // Forms are big, we want a big modal.
                self.modal.setLarge();
     
                // We want to reset the form every time it is opened.
                self.modal.getRoot().on(ModalEvents.hidden, function() {
                    self.modal.setBody(self.getBody());
                    self.modal.getRoot().animate({"right":"-85%"}, 500);
                    setTimeout(function(){
                        modal.destroy();
                    }, 1000);
                    
                }.bind(this));
                this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                        window.location.href =  window.location.href;
                });
                
                // We want to hide the submit buttons every time it is opened.
                self.modal.getRoot().on(ModalEvents.shown, function() {
                    self.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
                }.bind(this));
     
    
                // We catch the modal save event, and use it to submit the form inside the modal.
                // Triggering a form submission will give JS validation scripts a chance to check for errors.
                self.modal.getRoot().on(ModalEvents.save, self.submitForm.bind(self));
                // We also catch the form submit event and use it to submit the form with ajax.
                self.modal.getRoot().on('submit', 'form', self.submitFormAjax.bind(self));

                this.modal.show();
                this.modal.getRoot().animate({"right":"0%"}, 500);
                  $(".close").click(function(){
                    window.location.href =  window.location.href;
                  });
                return this.modal;
            }.bind(this));
        
    };

    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    courseenrol.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // alert(formdata);
        // Get the content of the modal.
        var params = {planid:this.planid, jsonformdata: JSON.stringify(formdata),condition: this.condition};
        return Fragment.loadFragment('local_ilp', 'lpcourse_enrol', this.contextid, params);
    };

    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    courseenrol.prototype.handleFormSubmissionResponse = function() {
        this.modal.hide();
        // We could trigger an event instead.
        // Yuk.
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
        document.location.reload();
    };

    /**
     * @method handleFormSubmissionFailure
     * @private
     * @return {Promise}
     */
    courseenrol.prototype.handleFormSubmissionFailure = function(data) {
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
    courseenrol.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
 
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        // alert(this.contextid);
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_ilp_lpcourse_enrol_form',
            args: {planid: this.planid, contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
            done: this.handleFormSubmissionResponse.bind(this, formData),
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }]);
    };
 
    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    courseenrol.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };

    return /** @alias module:local_costcenter/newcostcenter */ {
        // Public variables and functions.
        /**
         * Attach event listeners to initialise this module.
         *
         * @method init
         * @param {string} selector The CSS selector used to find nodes that will trigger this module.
         * @param {int} contextid The contextid for the course.
         * @return {Promise}
         */
        init: function(args) {
          
            // alert(args.contextid);
            return new courseenrol(args);
        },
        load: function(){

        },
        publishilp: function(args){
            console.log(args);
            var planvalue = args.planid;
            return Str.get_strings([{
                key: 'confirm'
            },
            {
                key: 'ilp_enrol_users',
                component: 'local_ilp',
                param :args
            },
            {
                key: 'confirmall',
                component: 'local_ilp'
            },
            {
                key: 'confirm'
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: s[1]
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText(s[3]);
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        // args.confirm = true;
                        $.ajax({
                            method: "GET",
                            dataType: "json",
                            url: M.cfg.wwwroot + "/local/ilp/ajax.php?action=publishilp&planid="+planvalue,
                            success: function(data){
                                modal.destroy();
                                window.location.href = window.location.href;
                            }
                        });
                    }.bind(this));
                    modal.show();
                }.bind(this));
            }.bind(this));
        },
        tabsFunction: function(args){
            // console.log(args);
            // alert(args.id);
            $('.ilp_tabs').click(function(){
                if ($(this).find('a').hasClass('active')){
                    return true;
                }
                var mylink = this;
                console.log(mylink);
                var ilptab = $(this).data('module');
                var id = $(this).data('id');
                // console.log(id);
                // alert(id);
                $.ajax({
                    method: 'GET',
                    // dataType: "json",
                    url: M.cfg.wwwroot + '/local/ilp/ajax.php',
                    data: {
                        action: "ilptab",
                        tab: ilptab,
                        id: id
                    },
                    success:function(resp){
                        var html = $.parseJSON(resp);
                        $('#ilptabscontent').html(html);
                        $('#ilptabscontent').find('div').addClass('active');
                        if(ilptab == 'users'){
                            $("table#ilp_users").dataTable({
                                language: {
                                    "paginate": {
                                        "next": ">",
                                        "previous": "<"
                                    },
                                    "search": "",
                                    "searchPlaceholder": "Search"
                                }
                            });
                        }else if(ilptab == 'requestedusers'){
                           require(['local_request/requestconfirm'], function(requestconfirm) {
                                requestconfirm.requestDatatable();
                            });
                        }
                        // console.log(mylink);
                    }
                });
            });
        },
        enrolUser : function(args){
            // console.log(args);
            // alert('here');
            var planvalue = args.planid;
            var userid = args.userid;
            return Str.get_strings([{
                key: 'confirm'
            },
            {
                key: 'ilp_self_enrol',
                component: 'local_ilp',
                param :args
            },
            {
                key: 'confirm'
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: s[1]
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText(s[2]);
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        // args.confirm = true;
                        $.ajax({
                            method: "GET",
                            dataType: "json",
                            url: M.cfg.wwwroot + "/local/ilp/ajax.php?action=userselfenrol&planid="+planvalue+"&userid="+userid,
                            success: function(data){
                                modal.destroy();
                                window.location.href = M.cfg.wwwroot + '/local/ilp/view.php?id='+planvalue;
                            }
                        });
                    }.bind(this));
                    modal.show();
                }.bind(this));
            }.bind(this));
        },
    };
});
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
     * @param {object} args
     *
     * Each call to init gets it's own instance of this class.
     */
    var licenceform = function(args) {
        this.contextid = args.contextid;
        var self = this;
        self.init();
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    licenceform.prototype.modal = null;
 
    /**
     * @var {int} contextid
     * @private
     */
    licenceform.prototype.contextid = -1;
 
    /**
     * Initialise the class.
     *
     * @private
     * @return {Promise}
     */
    licenceform.prototype.init = function() {
        var self = this;
        
        var head = Str.get_string('costcenterlicencesettings', 'local_costcenter');

        return head.then(function(title) {
            // Create the modal.
            return ModalFactory.create({
                type: ModalFactory.types.DEFAULT,
                title: title,
                body: self.getBody(),
                footer: self.getFooter(),
            });
        }.bind(self)).then(function(modal) {
            
            // Keep a reference to the modal.
            self.modal = modal;
            //to hide close button
            modal.getRoot().find('[data-action="hide"]').css('display', 'none');

            modal.getFooter().find('[data-action="save"]').on('click', this.submitForm.bind(this));

            modal.getRoot().on('submit', 'form', function(form) {
                self.submitFormAjax(form, self.args);
            });
            this.modal.show();

            // do not close the modal, if we click anywhere in the page
            modal.getRoot().click(function(e) {
                this.modal.show();
            }.bind(this));


            return this.modal;

        }.bind(this));
        
    };
 
    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    licenceform.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal.
        var params = {jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment('local_costcenter', 'licence_form', this.contextid, params);
    };

    licenceform.prototype.getFooter = function() {
        $footer = '<button type="button" class="btn btn-primary" data-action="save">Submit</button>&nbsp;';
        return $footer;
    };
 
    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    licenceform.prototype.handleFormSubmissionResponse = function() {
        this.modal.hide();
        // We could trigger an event instead.
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
    licenceform.prototype.handleFormSubmissionFailure = function(data) {
        // Ah wait - this is normal. We need to re-display the form with errors!
        this.modal.setBody(this.getBody(data));
    };

    licenceform.prototype.submitForm = function(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    };
 
    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {Event} e Form submission event.
     */
    licenceform.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
        var self = this;
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();

        // Ajax.call([{
        //     methodname: 'local_costcenter_submit_licenceform',
        //     args: {jsonformdata: JSON.stringify(formData)},
        //     done: this.handleFormSubmissionResponse.bind(this, formData),
        //     fail: this.handleFormSubmissionFailure.bind(this, formData)
        // }]);

        var methodname = 'local_costcenter_submit_licenceform',
        params = {};
        params.jsonformdata = JSON.stringify(formData);
        var promise = Ajax.call([{
            methodname: methodname,
            args: params
        }]);
        promise[0].done(function(resp){
            // self.handleFormSubmissionResponse(self.args);
            self.handleFormSubmissionResponse(formData);
        }).fail(function(){
            // self.handleFormSubmissionFailure(formData);
            self.handleFormSubmissionFailure(formData);
        });
    };
 
    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    licenceform.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };
    return /** @alias module:local_costcenter/licenceform */ {
        // Public variables and functions.
        /**
         * Attach event listeners to initialise this module.
         *
         * @method init
         * @param {object} args
         * @return {Promise}
        */
        init: function(args) {
            return new licenceform(args);
        },
        load: function(){
            // return new licenceform(args);
        },
    };
});
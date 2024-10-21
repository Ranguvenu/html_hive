/**
 * Add a create new group modal to the page.
 *
 * @module     local_external_certificate/external certificates
 * @class      external certificates
 * @package    local_external_certificate
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_courses/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events',
  'core/fragment', 'core/ajax', 'core/yui', 'jqueryui'],
  function (DataTable, $, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {

    /**
    * Constructor
    *
    * @param {object} args
    *
    * Each call to init gets it's own instance of this class.
    */
    var reason_form = function (args) {
        this.contextid = args.contextid;
        this.args = args;
        this.init(args);
    };

    /**
    * @var {Modal} modal
    * @private
    */
    reason_form.prototype.modal = null;

    /**
    * @var {int} contextid
    * @private
    */
    reason_form.prototype.contextid = -1;

    /**
    * Initialise the class.
    *
    * @param {String} selector used to find triggers for the new group modal.
    * @private
    * @return {Promise}
    */
    reason_form.prototype.init = function (args) {

      var self = this;
      if (args.id) {
        var head = Str.get_string('reason_form_title','local_externalcertificate');
      } else {
        var head = Str.get_string('reason_form_title','local_externalcertificate');
      }
      return head.then(function (title) {
       // Create the modal.
        return ModalFactory.create({
          type: ModalFactory.types.SAVE_CANCEL,
          title: title,
          body: self.getBody()
        });
      }.bind(self)).then(function (modal) {

        // Keep a reference to the modal.
        self.modal = modal;
        self.modal.setLarge();

        // We want to reset the form every time it is opened.
        this.modal.getRoot().on(ModalEvents.hidden, function () {
          this.modal.getRoot().animate({ "right": "-85%" }, 500);
          setTimeout(function () {
            modal.destroy();
          }, 1000);
          this.modal.setBody('');
        }.bind(this));

        this.modal.getFooter().find('[data-action="save"]').on('click', this.submitForm.bind(this));
            // We also catch the form submit event and use it to submit the form with ajax.

            this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                modal.setBody('');
                modal.hide();
                setTimeout(function(){
                    modal.destroy();
                }, 1000);
                if (args.form_status !== 0 ) {
                    window.location.reload();
                }
            });
            this.modal.getRoot().on('submit', 'form', function(form) {
                self.submitFormAjax(form, self.args);
            });
            this.modal.show();
            this.modal.getRoot().animate({"right":"0%"}, 500);
            return this.modal;
        }.bind(this));
    };



    /**
    * @method getBody
    * @private
    * @return {Promise}
    */
    reason_form.prototype.getBody = function (formdata) {
      if (typeof formdata === "undefined") {
        formdata = {};
      }
      this.args.jsonformdata = JSON.stringify(formdata);
      return Fragment.loadFragment('local_externalcertificate', 'reason_form', this.contextid, this.args);

    };
    /**
    * @method handleFormSubmissionResponse
    * @private
    * @return {Promise}
    */
    reason_form.prototype.handleFormSubmissionResponse = function () {
      this.modal.hide();
      // We could trigger an event instead.
      // Yuk.
      Y.use('moodle-core-formchangechecker', function () {
        M.core_formchangechecker.reset_form_dirty_state();
      });
      document.location.reload();
    };

    /**
     * @method handleFormSubmissionFailure
     * @private
     * @return {Promise}
     */
    reason_form.prototype.handleFormSubmissionFailure = function (data) {
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
    reason_form.prototype.submitFormAjax = function (e) {
      // We don't want to do a real form submission.
      e.preventDefault();
      // Convert all the form elements values to a serialised string.
      var formData = this.modal.getRoot().find('form').serialize();
      var params = {};
      params.contextid = this.contextid;
      params.jsonformdata = JSON.stringify(formData);
      // alert(this.contextid);
      // Now we can continue...
      Ajax.call([{
        methodname: 'local_externalcertificate_submit_reason_form',
        args: params,
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
    reason_form.prototype.submitForm = function (e) {
      e.preventDefault();
      var self = this;
      self.modal.getRoot().find('form').submit();
    };

    return /** @alias module:local_externalcertificate/reason_form */ {
      // Public variables and functions.
      /**
      * @param {string} args
       * @return {Promise}
       */
      init: function (args) {

        return new reason_form(args);
      },

      load: function () {
      }
    };

  });
/**
 * Add a create new group modal to the page.
 *
 * @module     local_externalcertificate/external certificates
 * @class      external certificates
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_courses/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events',
  'core/fragment', 'core/ajax', 'core/yui', 'core/notification', 'jqueryui'],
  function (DataTable, $, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y, Notification) {

    /**
    * Constructor
    *
    * @param {object} args
    *
    * Each call to init gets it's own instance of this class.
    */
    var certificate_form = function (args) {
      this.contextid = args.contextid;
      this.args = args;
      this.init(args);
    };

    /**
    * @var {Modal} modal
    * @private
    */
    certificate_form.prototype.modal = null;

    /**
    * @var {int} contextid
    * @private
    */
    certificate_form.prototype.contextid = -1;

    /**
    * Initialise the class.
    *
    * @param {String} selector used to find triggers for the new group modal.
    * @private
    * @return {Promise}
    */
    certificate_form.prototype.init = function (args) {

      var self = this;
      if (args.id) {
        var head = Str.get_string('amd_externalcert', 'local_externalcertificate');
      } else {
        var head = Str.get_string('amd_externalcert', 'local_externalcertificate');
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
        this.modal.getRoot().addClass('openLMStransition');

        // We want to reset the form every time it is opened.
        this.modal.getRoot().on(ModalEvents.hidden, function () {
          this.modal.getRoot().animate({ "right": "-85%" }, 500);
          setTimeout(function () {
            modal.destroy();
          }, 1000);
        }.bind(this));

        // We want to hide the submit buttons every time it is opened.
        self.modal.getRoot().on(ModalEvents.shown, function () {
          self.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
          this.modal.getFooter().find('[data-action="cancel"]').on('click', function () {
            modal.hide();
            setTimeout(function () {
              modal.destroy();
            }, 1000);
          });
        }.bind(this));


        // We catch the modal save event, and use it to submit the form inside the modal.
        // Triggering a form submission will give JS validation scripts a chance to check for errors.
        self.modal.getRoot().on(ModalEvents.save, self.submitForm.bind(self));
        // We also catch the form submit event and use it to submit the form with ajax.
        self.modal.getRoot().on('submit', 'form', self.submitFormAjax.bind(self));
        self.modal.show();
        this.modal.getRoot().animate({ "right": "0%" }, 500);
        return this.modal;
      }.bind(this));
    };



    /**
    * @method getBody
    * @private
    * @return {Promise}
    */
    certificate_form.prototype.getBody = function (formdata) {
      if (typeof formdata === "undefined") {
        formdata = {};
      }
      this.args.jsonformdata = JSON.stringify(formdata);
      return Fragment.loadFragment('local_externalcertificate', 'edit', this.contextid, this.args);

    };
    /**
    * @method handleFormSubmissionResponse
    * @private
    * @return {Promise}
    */
    certificate_form.prototype.handleFormSubmissionResponse = function () {
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
    certificate_form.prototype.handleFormSubmissionFailure = function (data) {
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
    certificate_form.prototype.submitFormAjax = function (e) {
      // We don't want to do a real form submission.
      e.preventDefault();
      // Convert all the form elements values to a serialised string.
      var formData = this.modal.getRoot().find('form').serialize();
      var params = {};
      params.contextid = this.contextid;
      params.jsonformdata = JSON.stringify(formData);

      // Now we can continue...
      Ajax.call([{
        methodname: 'local_externalcertificate_submit_certificates_form',
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
    certificate_form.prototype.submitForm = function (e) {
      e.preventDefault();
      var self = this;
      self.modal.getRoot().find('form').submit();
    };

    return /** @alias module:local_externalcertificate/certificate_form */ {
      // Public variables and functions.
      /**
      * @param {string} args
       * @return {Promise}
       */
      init: function (args) {

        return new certificate_form(args);
      },

      Datatable: function () {
        Str.get_strings([{
          key: 'search',

        }]).then(function (str) {
          $('.generaltable').dataTable({
            "searching": true,
            "responsive": true,
            "aaSorting": [[1, "desc"]],
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


      // change status.....
   /*    statusConfirm: function (args) {
        var promise = Ajax.call([{
          methodname: 'local_externalcertificate_changestatus',
          args: {
            id: args.id,
            status: args.status
          },
        }]);
        promise[0].done(function () {
          window.location.reload();
        });
      }, */

      statusConfirm : function(args){
        return Str.get_strings([{
            key: 'confirm'
        },
        {
            key: 'approve_certificate',
            component: 'local_externalcertificate',
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
                    var promise = Ajax.call([{
                      methodname: 'local_externalcertificate_changestatus',
                      args: {
                        id: args.id,
                        status: args.status
                      },
                    }]);
                    promise[0].done(function (res) {
                      console.log(res);
                      if (res == 'Other') {                        
                        Notification.confirm('confirm', 'Merge into the course to continue with the approval process.', 'Ok', 'OK', function() {
                          $('.modal-dialog').css('display','none');
                          window.location.href = M.cfg.wwwroot + '/local/externalcertificate/index.php';                      
                      });

                      } else {
                        window.location.reload();
                      }
                      
                    });
                }.bind(this));
                modal.show();
            }.bind(this));
        }.bind(this));
    },

      filter: function () {
        var from_date = $('.fromdate').val();
        var to_date = $('.todate').val();
        var request = $.ajax({
          url: M.cfg.wwwroot + "/local/externalcertificate/ajaxfile.php",
          method: "POST",
          data: { from_date: from_date, to_date: to_date },
          dataType: "json"
        });
        request.done(function (data) {
          $('#certtable').append(data);
        });
      },

      mergecoursestatus : function(args){
          var self = this;        
          if (args.id) {
            var head = Str.get_string('mergecoursename','local_externalcertificate');
          } else {
            var head = Str.get_string('mergecoursename','local_externalcertificate');
          }
          return head.then(function(title) {
              ModalFactory.create({
                  title:title,
                  type: ModalFactory.types.SAVE_CANCEL,
                  body:Fragment.loadFragment('local_externalcertificate', 'mastercourse_form', 1, args),
               
              }).done(function(modal) {
                  this.modal = modal;
                   this.modal.setLarge();
                   this.modal.getRoot().addClass('mastercourse_form');                 
                    modal.getRoot().find('[data-action="save"]').on('click', function() {
                        // Convert all the form elements values to a serialised string.
                    var formData = this.modal.getRoot().find('form').serialize();
                    var params = {};
                    params.contextid = args.contextid;
                    params.id = args.id;
                    params.status = args.status;
                    params.jsonformdata = JSON.stringify(formData);
                      //e.preventDefault();
                      var promise = Ajax.call([{
                        methodname: 'local_externalcertificate_mergecourserequest',
                         args: params,
                      }]);
                      promise[0].done(function () {
                       window.location.reload();
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

      mastercertificate_form : function(args){
          var self = this;        
          if (args.id) {
            var head = Str.get_string('editnewmasterextcourse','local_externalcertificate');
          } else {
            var head = Str.get_string('addnewmasterextcourse','local_externalcertificate');
          }
          return head.then(function(title) {
              ModalFactory.create({
                  title:title,
                  type: ModalFactory.types.SAVE_CANCEL,
                  body:Fragment.loadFragment('local_externalcertificate', 'mastercertificate_form', 1, args),
               
              }).done(function(modal) {
                  this.modal = modal;
                   this.modal.setLarge();
                   //this.modal.getRoot().addClass('mastercourse_form');                 
                    modal.getRoot().find('[data-action="save"]').on('click', function() {
                        // Convert all the form elements values to a serialised string.
                    var formData = this.modal.getRoot().find('form').serialize();
                    var params = {};
                    params.contextid = args.contextid;
                    params.id = args.id;
                    params.status = args.status;
                    params.jsonformdata = JSON.stringify(formData);
                      //e.preventDefault();
                      var promise = Ajax.call([{
                        methodname: 'local_externalcertificate_mastercertificate_form',
                         args: params,
                      }]);
                      promise[0].done(function () {
                       window.location.reload();
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
     deleteConfirm: function(args){
            return Str.get_strings([{

                key: 'confirm',
                component: 'local_externalcertificate',
            },
            {
                key: 'deletemastercertificate',
                component: 'local_externalcertificate',
                param : args
            },
            {
                key: 'delete'
            }
          ]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.DEFAULT,
                    body: s[1],
                    footer: '<button type="button" class="btn btn-primary" data-action="save">Yes</button>&nbsp;' +
            '<button type="button" class="btn btn-danger" data-action="cancel">Cancel</button>'
                }).done(function(modal) {
                    this.modal = modal;
                    modal.getRoot().find('[data-action="save"]').on('click', function() {
                        args.confirm = true;
                        var promise = Ajax.call([{
                            methodname: 'local_externalcertificate_deletemastercertificate',
                            args: {
                                id: args.id
                            },
                        }]);
                        promise[0].done(function() {
                            window.location.reload();
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

  
      load: function () {
    
      }
    };
  });

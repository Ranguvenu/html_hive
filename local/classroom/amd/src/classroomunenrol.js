/**
 * Add a create new group modal to the page.
 *
 * @module     local_classroom/classroomunenrol
 * @class      classroomunenrol
 * @package    local_classroom
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 define(['local_courses/jquery.dataTables','jquery', 'core/str', 'core/modal_factory', 'core/modal_events',
 'core/fragment', 'core/ajax', 'core/yui', 'jqueryui'],
 function ( DataTable,$, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {

   /**
   * Constructor
   *
   * @param {object} args
   *
   * Each call to init gets it's own instance of this class.
   */
   var classroomunenrol = function (args) {
 
     this.classroomid = args.classroomid;
     this.init(args);
   };

   /**
   * @var {Modal} modal
   * @private
   */
   classroomunenrol.prototype.modal = null;

   /**
   * @var {int} contextid
   * @private
   */
   classroomunenrol.prototype.contextid = -1;

   /**
   * Initialise the class.
   *
   * @param {String} selector used to find triggers for the new group modal.
   * @private
   * @return {Promise}
   */
   classroomunenrol.prototype.init = function (args) {
   /*  var head = Str.get_string([
        {
            key: 'unenrollclassroom',
            component: 'local_classroom',
            param: args.classroomname,
        }
        ]); */
     var self = this;
     var head = Str.get_string('unenrollclassroom','local_classroom',args.classroomname);
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
    
        // We want to reset the form every time it is opened.
        this.modal.getRoot().on(ModalEvents.hidden, function () {
            setTimeout(function () {
            modal.destroy();
            }, 1000);
        }.bind(this));
    
        // We want to hide the submit buttons every time it is opened.
        self.modal.getRoot().on(ModalEvents.shown, function () {
            this.modal.getFooter().find('[data-action="save"]').on('click', function () {
                var reason = $('.reason').val();
                if (reason.trim()!=''){
                    
                    var params = {};
                    params.classroomid = args.classroomid;
                    params.contextid = 1;
                    params.reason = reason;
                    params.confirm = true;
                    var promise = Ajax.call([{
                        methodname: 'local_classroom_' + args.action,
                        args: params
                    }]);
                    promise[0].done(function(resp) {
                        window.location.href = M.cfg.wwwroot + '/local/classroom/view.php?cid='+args.classroomid;
                       
                    }).fail(function(ex) {
                        // do something with the exception
                         console.log(ex);
                    }); 
                
                }else  if (reason.trim()==''){
                   $(".unenrolerror").attr("style", "display:block;color:red");
                }
            });
            this.modal.getFooter().find('[data-action="cancel"]').on('click', function () {
            modal.hide();
            setTimeout(function () {
                modal.destroy();
            }, 1000);
            // modal.destroy();
            });
        }.bind(this));
    
    
        // We catch the modal save event, and use it to submit the form inside the modal.
        // Triggering a form submission will give JS validation scripts a chance to check for errors.
        self.modal.getRoot().on(ModalEvents.save, self.submitForm.bind(self));
        // We also catch the form submit event and use it to submit the form with ajax.
        self.modal.getRoot().on('submit', 'form', self.submitFormAjax.bind(self));
        self.modal.show();
        this.modal.getRoot().animate({ "right": "10%" }, 800);
        return this.modal;
     }.bind(this));
   };

   /**
   * @method getBody
   * @private
   * @return {Promise}
   */
   classroomunenrol.prototype.getBody = function () {
     var params = {classroomid:this.classroomid};        
     return Fragment.loadFragment('local_classroom', 'classroom_unenrol',1, params);
   };
   /**
   * @method handleFormSubmissionResponse
   * @private
   * @return {Promise}
   */
   classroomunenrol.prototype.handleFormSubmissionResponse = function () {
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
   classroomunenrol.prototype.handleFormSubmissionFailure = function (data) {
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
   classroomunenrol.prototype.submitFormAjax = function (e) {
     // We don't want to do a real form submission.
     e.preventDefault();

   };


   /**
    * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
    *
    * @method submitForm
    * @param {Event} e Form submission event.
    * @private
    */
   classroomunenrol.prototype.submitForm = function (e) {
     e.preventDefault();
     var self = this;
     self.modal.getRoot().find('form').submit();
   };

   return /** @alias module:local_classroom/classroomunenrol */ {
     // Public variables and functions.
     /**
     * @param {string} args
      * @return {Promise}
      */
     deleteConfirm: function (args) {
       return new classroomunenrol(args);
     },
     
     Datatable: function (args) {
        Str.get_strings([{
          key: 'search',
          //component: 'local_costcenter',
        }]).then(function (str) {
          $('#unenrol_classrooms').dataTable({
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

     load: function () {
     }
   };

 });
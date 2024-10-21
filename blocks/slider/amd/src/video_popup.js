define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui', 'core/templates', 'core/notification'],
    function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y, Templates, Notification) {
        var video_popup = function(args) {
            this.contextid = args.contextid || 1;
            this.args = args;
            var self=this;
            self.init(args);
        };

        video_popup.prototype.modal = null;
        video_popup.prototype.contextid = -1;

        video_popup.prototype.init = function(args) {            
            var self = this;
            var head = Str.get_string('header');

            return head.then(function() {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.DEFAULT,
                    body: '<video width="100%" height="100%" controls><source src="'+this.args.videourl+'" type="video/mp4"></video>',
                });
            }.bind(this)).then(function(modal) {
                // Keep a reference to the modal.
                this.modal = modal;

                // Forms are big, we want a big modal.
                this.modal.setLarge();

                // We want to reset the form every time it is opened.
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    setTimeout(function(){
                        this.modal.hide();
                    }, 1000);
                    this.modal.setBody('');
                }.bind(this));

                this.modal.show();
                return this.modal;
            }.bind(this));
        };

        return  {
            init: function(args) {
                return new video_popup(args);
            },
            load:function () {}
        };
    }
);

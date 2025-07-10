define(['jquery','core/log','core/str'], function($,log,str) {
    "use strict"; // jshint ;_;

    log.debug('submission helper: initialising');

    return {

        uploadstate: false,
        togglestate: 0,
        strings: {},


        init:  function(opts) {

            this.component = opts['component'];
            this.filecontrolid = opts['filecontrolid'];
            this.togglingthing = opts['togglingthing'];
            this.safesave=opts['safesave'];
            this.register_controls();
            this.register_events();


            //we might want to expand current submission by default.
            if(this.togglingthing=='currentsubmission') {
                if (opts['expandcurrentsubmission']) {
                    this.toggle_currentsubmission(this);
                }
            }
        },

        register_controls: function(){
          var that=this;
          this.controls={};
          this.controls.formsubmitbutton = $('#id_submitbutton');
          this.controls.deletebutton = $('.' + this.component + '_deletesubmissionbutton');
          this.controls.updatecontrol =  $('#' + this.filecontrolid);
          this.controls.currentcontainer =  $('.' + this.component + '_currentsubmission');
          this.controls.reccontainer =  $('.' + this.component + '_recorder');
          this.controls.togglecontainer =  $('.' + this.component + '_togglecontainer');
          this.controls.togglebutton =  $('.' + this.component + '_togglecontainer .togglebutton');
          this.controls.toggletext =  $('.' + this.component + '_togglecontainer .toggletext');

            var that = this;
            var prassigsub = function (evt) {
                if(evt){
                    switch(evt[1]) {
                        case 'filesubmitted':
                            var filename = evt[2];
                            var updatecontrol = evt[3];
                            // post a custom event that a filter template might be interested in
                            var prassigsubUploaded = new CustomEvent("prassigsubUploaded", {details: evt});
                            document.dispatchEvent(prassigsubUploaded);

                            //if opts safe save
                            if(that.safesave==1) {
                                that.controls.formsubmitbutton.removeAttr('disabled');
                            }

                            //poke the filename
                            var upc = '';
                            if (typeof updatecontrol !== 'undefined' && updatecontrol !== '') {
                                upc = $('[id="' + updatecontrol + '"]');
                            }
                            if (upc.length < 1) {
                                upc = $('[id="' + updatecontrol + '"]', window.parent.document);
                            }
                            if (upc.length > 0) {
                                upc.get(0).value = filename;
                            } else {
                                log.debug('upload failed #2');
                                return false;
                            }
                            upc.trigger('change');
                            break;
                        case 'started':
                            //if opts safe save
                            if(that.safesave==1) {
                                that.controls.formsubmitbutton.attr('disabled', 'disabled');;
                            }

                            break;
                    }
                }
            }; //end of callback function
            window.prassigsub = prassigsub;



            str.get_string('clicktohide',that.component).done(function(s) {
                that.strings['clicktohide']=s;
            });
            str.get_string('clicktoshow',that.component).done(function(s) {
                that.strings['clicktoshow']=s;
            });
        },

        register_events: function(){
            var that =this;
            this.controls.deletebutton.click(function(){
                if(that.controls.updatecontrol){
                    if(confirm(M.util.get_string('reallydeletesubmission',that.component))){
                        that.controls.updatecontrol.val(-1);
                        that.controls.currentcontainer.html('');
                    }
                }
            });

            //we only toggle(hide/show) currentsubmission OR recorder, not both
            if(this.togglingthing==='currentsubmission') {
                this.controls.togglebutton.click(function () {
                    that.toggle_currentsubmission(that);
                });
                this.controls.toggletext.click(function () {
                    that.toggle_currentsubmission(that);
                });
            }else if(this.togglingthing==='recorder') {
                this.controls.togglebutton.click(function () {
                    that.toggle_recorder(that);
                });
                this.controls.toggletext.click(function () {
                    that.toggle_recorder(that);
                });
            }
        },

        do_toggle: function(that){
            if(that.togglestate===0){
                that.controls.togglebutton.removeClass('fa-toggle-off');
                that.controls.togglebutton.addClass('fa-toggle-on');
                that.controls.toggletext.text(that.strings['clicktohide']);
                that.togglestate=1;
            }else{
                that.controls.togglebutton.removeClass('fa-toggle-on');
                that.controls.togglebutton.addClass('fa-toggle-off');
                that.controls.toggletext.text(that.strings['clicktoshow']);
                that.togglestate=0;
            }
        },

        toggle_currentsubmission: function(that){
            that.controls.currentcontainer.toggle(
                {duration: 300, complete: function(){that.do_toggle(that);}}
            );
            return false;
        },

        toggle_recorder: function(that){
            that.controls.reccontainer.toggle(
                {duration: 300, complete: function(){that.do_toggle(that);}}
            );
            return false;
        }
    };//end of return object
});
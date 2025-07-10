(function ($) {
    var idleTime = 0;
    var timer;

    var contextparam = '&contextid=' + M.cfg.contextid;
    var siteroot = M.cfg.wwwroot;
    var pagewrapper = $('#page-wrapper');
    function isTabActive() {
        return document.visibilityState === 'visible';
    }
    function isModalOpen() {
        return $('#bsModal3').hasClass('in');
    }

    function trackActivities() {
        if (!isTabActive() || isModalOpen()) {
            return;
        }
        $.ajax({
            type: 'POST',
            url: siteroot + '/local/timetracker/track_activities.php',
            data: 'status=proceed' + contextparam,
            success: function (msg) {
                console.log(msg);
                if (msg == 'askquestion') {
                    securityQuestion(1, true);
                    return;
                }
                if(msg == 'proceed'){
                    console.log(msg);
                } else {
                    if(msg == 'smallbreak')
                        smallbreak();
                    else if(msg == 'coursebreak')
                        coursebreak();
                    else if(msg == 'endofday')
                        endofday();
                    console.log('redirect', msg);
                    setInterval(function() {
                        console.log('redirecting');
                        location.reload(true);
                    }, 6000);
                }
            }
        });

    }

    function timerIncrement() {

        idleTime = idleTime + 1;
        console.log('idleTime ' + idleTime);
        if (idleTime > 140000000) { // 15 minutes
            $.ajax({
                type: 'POST',
                url: siteroot + '/local/timetracker/track_activities.php',
                data: 'status=logout&page=false' + contextparam,
                success: function (msg) {
                    //console.log(msg);
                    if (msg == 'idletime') {
                        idletime();
                        setInterval(function () {
                            location.reload(true);
                        }, 6000);

                    }
                }
            });
        }
    }

    function idletime() {
        $("body").append('<div class="modal fade" id="idletime"  role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" data-backdrop="static"> <div class="modal-dialog modal-sm">  <div class="modal-content">     <div class="modal-header"> <h4 class="modal-title" id="mySmallModalLabel" style="margin-top: 20px;margin-left: 50px;">break</h4>   </div>  <div class="modal-body">   <p class="statusMsg">You were idle for 15 minutes. You will be logged out.</p>    </div> </div></div>');
        $('#idletime').modal('show');

    }
    function smallbreak() {
        $("body").append('<div class="modal fade" id="smallbreak"  role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" data-backdrop="static"> <div class="modal-dialog modal-sm">  <div class="modal-content">     <div class="modal-header"> <h4 class="modal-title" id="mySmallModalLabel" style="margin-top: 20px;margin-left: 50px;">break</h4>   </div>  <div class="modal-body">   <p class="statusMsg">Time to take a 10 minute break. You will be logged out.</p>    </div> </div></div>');
        $('#smallbreak').modal('show');

    }
    function coursebreak() {
        $("body").append('<div class="modal fade" id="coursebreak"  role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" data-backdrop="static"> <div class="modal-dialog modal-sm">  <div class="modal-content">     <div class="modal-header"> <h4 class="modal-title" id="mySmallModalLabel" style="margin-top: 20px;margin-left: 50px;">break</h4>   </div>  <div class="modal-body">   <p class="statusMsg">Working time on a course is 3 hours. You will be logged out.</p>    </div> </div></div>');
        $('#coursebreak').modal('show');

    }
    function endofday() {
        $("body").append('<div class="modal fade" id="endofday"  role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" data-backdrop="static"> <div class="modal-dialog modal-sm">  <div class="modal-content">     <div class="modal-header"> <h4 class="modal-title" id="mySmallModalLabel" style="margin-top: 20px;margin-left: 50px;">break</h4>   </div>  <div class="modal-body">   <p class="statusMsg">Working time on a course is 3 hours. You will be logged out.</p>    </div> </div></div>');
        $('#endofday').modal('show');

    }

    function securityQuestion(askquestion, force, frommodal) {
        console.log('askquestion ' + askquestion);

        var bsmodal = $('#bsModal3');

        if (force && !bsmodal.hasClass("in")) {
            bsmodal.remove();
        }

        if (!isTabActive()) {
            return false;
        }
        if (!frommodal && isModalOpen()) {
            return false;
        }

        if (force || !bsmodal.hasClass("modal")) {
            $(window).keydown(function(event){
                if(event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });
            $.getJSON(siteroot + "/local/timetracker/load_question.php?askquestion=" + askquestion + contextparam, function (data) {
                $.each(data, function (key, val) {

                    // set countdown timer
                    time = 1 * 60;
                    tmp = time;
                    clearInterval(timer);
                    timer = setInterval(function () {

                        var c = tmp--;
                        var m = (c / 60) >> 0;
                        var s = (c - m * 60) + '';

                        if (s == 1) {
                            submitContactForm(0);
                        }
                        $('#countdown').html('Time Remaining: ' + m + ':' + (s.length > 1 ? '' : '0') + s);
                        tmp != 0 || (tmp = time);

                    }, 1000);

                    pagewrapper.append(' <div class="modal fade" id="bsModal3"  role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" data-backdrop="static">  <div class="modal-dialog modal-sm">    <div class="modal-content">      <div class="modal-header">  <h4 class="modal-title" id="mySmallModalLabel" style="margin-top: 20px;margin-left: 50px;">Security Check</h4>      </div>      <div class="modal-body">   <p class="statusMsg"></p>   <div id="countdown"></div>               <form id="bsModalForm" role="form">  			 <div class="form-group">    <label class="control-label col-sm-2" for="email">Question: </label>    <div class="col-sm-10">      <p class="form-control-static">' + val + '</p>                </div>                <div class="form-group"> <input id="questionid" type="hidden" class="form-control" name="questionid" value="' + key + '" > <input id="answer" type="text" class="form-control" name="answer" >                    </div>				       </div>      <div class="modal-footer">          <button type="button" class="btn btn-primary submitForm" >Submit</button>      </div>    </div>  </div></div>');
                    $('#bsModal3').modal('show');
                    console.log('in...');
                });

            });

        }

    }

    function submitContactForm(auto) {

        var qid = $('#questionid').val();
        var answer = $('input[name=answer]').val();

        if (answer.trim() == '' && auto == 1) {
            alert('Please enter answer.');
            $('#questionid').focus();
            return false;

        } else {
            clearInterval(timer);
            //console.log('contactFrmSubmit=1&qid='+qid+'&answer='+answer+'&page='+page+'&activity='+activity);
            $.ajax({
                type: 'POST',
                url: siteroot + '/local/timetracker/submit_form.php',
                data: 'contactFrmSubmit=1&qid=' + qid + '&answer=' + answer + contextparam,
                beforeSend: function () {
                    console.log('submitContactForm..');
                },
                success: function (msg) {
                    //console.log(msg);
                    if (msg == 'logout') {
                        $('#countdown').html('');
                        $('.statusMsg').html('<span style="color:red;"><b>You have exceeded the number of attempts allowed contact the administrator to access your course</b></p>');
                        setInterval(function () {
                            location.reload(true);
                        }, 3000);

                    } else {
                        if (msg == 'wrong') {
                            $('.statusMsg').html('<span style="color:red;"><b>This is incorrect</b></p>');

                            setTimeout(function () {
                                $("#page-wrapper #bsModal3").remove();
                                securityQuestion(1, false, true);
                            }, 1000);

                        } else {
                            $('#bsModal3').modal('toggle').delay(1000).remove();
                            $(".modal-backdrop").remove();
                            $("body").removeClass('modal-open');
                        }
                        return false;
                    }


                }
            });
        }

    }

    function isLastActivity() {

        $.getJSON(siteroot + '/local/timetracker/is_lastactivity.php?' + contextparam, function (data) {
            var msg = data.status;
            var hours = data.hours;
            var returnurl = data.returnurl;
            if (msg == 'inprogress') {

                $("body").append('<div class="modal fade" id="bsModal1"  role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" data-backdrop="static"> <div class="modal-dialog modal-sm">  <div class="modal-content">     <div class="modal-header"> <h4 class="modal-title" id="mySmallModalLabel">Please spend 20 hours !!!</h4>   </div>  <div class="modal-body">   <p class="statusMsg">You didn\'t complete the minimum course hours.You only spend ' + hours + ' hours. Please have a look again <a href="' + returnurl + '">Back</a>  </p>    </div> </div></div>');
                $('#bsModal1').modal('show');
                /*setInterval(function() {
                        window.location = returnurl;
                    }, 6000);*/

            } else {
                //securityQuestion(1);
            }
        });

    }

    $(document).ready(function () {
        if (!document.body.classList.contains('pagelayout-embedded') &&
            'undefined' !== typeof scormplayerdata && scormplayerdata.popupoptions.length > 0) {
            console.log('skipping for scorm popup');
            return;
        }

        if ((window.location.href.indexOf("course") > -1) || (window.location.href.indexOf("mod") > -1)) {
            if ((window.location.href.indexOf("view") > -1) || (window.location.href.indexOf("scorm/player") > -1))
                setInterval(function () {
                    trackActivities();
                }, 10000);

        }


        $(window).on("unload", function (e) {
            if ((window.location.href.indexOf("course") > -1) || (window.location.href.indexOf("mod") > -1)) {
                if ((window.location.href.indexOf("view") > -1))
                    trackActivities();
            }
        });

        //Increment the idle time counter every minute.-15

        setInterval(timerIncrement, 60000); // 1 minute

        //Zero the idle timer on mouse movement.
        $(this).mousemove(function () {
            idleTime = 0;
        });
        $(this).keypress(function () {
            idleTime = 0;
        });

        if ((window.location.href.indexOf("mod") > -1)) {
            if ((window.location.href.indexOf("view") > -1)) {
                isLastActivity(window.location.href);
            }
        }

        if ((window.location.href.indexOf("course") > -1) || (window.location.href.indexOf("mod") > -1)) {
            if ((window.location.href.indexOf("view") > -1)) {
                setInterval(function () {
                    securityQuestion(0);
                }, 2000);

            }
        }


        function submitForm(e) {
            e.preventDefault();
            submitContactForm(1);
        }
        pagewrapper.on("click", ".submitForm", submitForm)
        .on("submit", "#bsModalForm", submitForm);


    });

}(jQuery));


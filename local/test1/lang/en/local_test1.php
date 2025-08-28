<?php


$string['search'] = 'Search';
$string['pluginname'] = 'Test 1';
$string['heading'] = 'Test 1';
$string['title'] = 'Test 1';
$string['nodatatoexport'] = 'No data to export';
$string['confirmuserdelete'] = 'Are you sure to delete user?';
$string['mailsubject'] = 'Thanks for purchasing with Investit!';
$string['email'] = 'Send mail';
$string['pdf'] = 'Export PDF';
$string['csv'] = 'Export CSV';
$string['test1task'] = 'Test 1 Cron Task';
$string['name'] = 'Fullname';
$string['mailer'] = 'Mailer';
$string['type'] = 'Mail type';
$string['sendtime'] = 'Send Time';
$string['email1'] = 'Email';
$string['selecttype'] = 'Select Type';
$string['starttime'] = 'Start Time';
$string['endtime'] = 'End Time';
$string['mailbody'] = '<body style="margin:0;padding:0;word-spacing:normal;background-color:#EFEEEE;">
<div role="article" aria-roledescription="email" lang="en" style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">   
    <table role="presentation" style="width:100%;border:none;border-spacing:0;">       
        <tr>
            <td align="center" style="padding: 30px;" >
                <table style="width:100%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:Arial,sans-serif;font-size:16px;line-height:24px;color:#2F4141; background-color: #d1d1d1;">
                    <tr>
                        <td align="center" style="padding: 20px;background-color:#ffffff;border-bottom: 1px solid;">
                             <img src="https://public.investitacademylms.com/moodle/images/IA_Logo.png" width="80%" height="80" alt="Investit">
                        </td>
                    </tr>
                </table>
                <table style="width:100%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:Arial,sans-serif;font-size:16px;line-height:24px;color:#2F4141; background-color: #ffffff;">
                    <tr>
                        <td align="left" style="padding: 20px">
                            <p>Hello {$a->firstname} {$a->lastname}!,</p>
                            <p style="text-align: center;"><strong>RAISING YOUR COMMERCIAL IQ</strong></p>
                            <p>Thank you for purchasing the following Investit Academy BCREA PDP accredited courses.</p>
                            <table class="shop-table" width="100%" border="1" style="border-left: 1px solid black;border-top: 1px solid black;border-collapse: collapse;border: 1px solid #111111;padding: 5rem;border-width: 100%;line-height: 2.0;" cellpadding="10" cellspacing="10">
                                <tr class="table-head" style="width: 60%;font-weight: bolder;">
                                    <td>  Course </td>
                                    <td class="text-center" style="text-align: center;"> PDP Hours </td>                                    
                                </tr>'.implode('','$order_table_data').'                                
                            </table>
                            <p>To access the  courses go to: <a href="http://public.investitacademylms.com/moodle/login/index.php">http://public.investitacademylms.com/moodle/login/index.php</a></p>
                            <p> Username : {$a->username} <br> 
                                Password : "password here"; </p>
                            <p><strong>RECOMMENDATION</strong></p>
                            <p>To get you off to a quick start we recommend you read the "Investit Academy BCREA PDP accredited courses <a style="text-decoration: underline;" href="https://investitacademy.com/bcrea-quick-start-video/">Quick Start Video (5 min)</a> and <a style="text-decoration: underline;" href="https://investitacademy.com/bcrea-quick-start-guide/">Quick Start Guide</a></p>
                            <p>Regards, <br>Investit Academy <br> 604-988-9964 </p>
                            <p>Organization : BCREA PDP Courses,Office : BCREA PDP Courses</p>
                        </td>
                    </tr>
                </table>               
            </td>
        </tr>
    </table>
</div>
</body>';
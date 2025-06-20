	
(function ($) {

	function checkLock() {
		
		$.ajax({
				type:'POST',
				url:wwwroot+'/local/timetracker/is_locked.php'
				
			});
		 
	 }
	$( document ).ready(function() {
			
			checkLock();

	});
					
}(jQuery));

var wwwroot = M.cfg.wwwroot ;


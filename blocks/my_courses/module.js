M.block_my_courses = M.block_my_courses || {};

M.block_my_courses.init = function(Y, cfg) {
	this.Y = Y;
	this.hideString = cfg.hideString;
	this.showString = cfg.showString;

};

M.block_my_courses.expand = function(item) {
	if(M.block_my_courses.Y.one('#block-course-expanded-' + item).get('innerHTML') != '') {
		M.block_my_courses.Y.one('#block-course-expanded-' + item).show();
		M.block_my_courses.Y.one('#block-course-action-' + item).setHTML('<a href="#" onclick="return block_my_courses_unexpand(\'' + item + '\');" >' + M.block_mycourses.hideString + '</a>');
		return false;
	}
	var course = M.block_mycourses.Y.one('#block-course-' + item).get('value');
	M.block_my_courses.send_request(course, item);
	M.block_my_courses.Y.one('#block-course-action-' + item).setHTML('<a href="#" onclick="return block_my_courses_unexpand(\'' + item + '\');" >' + M.block_mycourses.hideString + '</a>');
	return false;
};

function block_my_courses_expand(item) {
	M.block_my_courses.expand(item);
	return false;
};
function block_mycourses_unexpand(item) {
	M.block_mycourses.unexpand(item);
	return false;
};

M.block_my_courses.unexpand = function(item) {
	M.block_my_courses.Y.one('#block-course-expanded-' + item).hide();
	M.block_my_courses.Y.one('#block-course-action-' + item).setHTML('<a href="#" onclick="return block_my_courses_expand(\'' + item + '\');" >' + M.block_mycourses.showString + '</a>');
	return false;
};

M.block_my_courses.send_request = function(course, item) {
	this.api = M.cfg.wwwroot+'/blocks/my_courses/ajax.php?sesskey='+M.cfg.sesskey,
	M.block_my_courses.Y.io(this.api,{
        method : 'POST',
        data :  build_querystring({
            course : course,
        }),
        on : {
            success : function(tid, outcome) {
            	M.block_my_courses.expand_course(outcome.responseText, item);
            }
        },
        context : this
    });
};

M.block_my_courses.expand_course = function(text, item) {
	M.block_my_courses.Y.one('#block-course-expanded-' + item).setHTML(text);
	M.block_my_courses.Y.one('#block-course-expanded-' + item).show();
};
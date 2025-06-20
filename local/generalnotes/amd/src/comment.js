/* eslint-disable */
define(['core/yui','core/config',],function (Y,config) {

    var image_url = window.M.util.image_url;
    var get_string = window.M.util.get_string;

    function CommentHelper(args) {
        var scope = this;
        this.client_id = args.client_id;
        this.itemid = args.itemid;
        this.commentarea = args.commentarea;
        this.component = args.component;
        this.courseid = args.courseid;
        this.contextid = args.contextid;
        this.autostart = (args.autostart);
        // expand comments?
        if (this.autostart) {
            this.view(args.page);
        }
        // load comments
        var handle = Y.one('#comment-link-'+this.client_id);
        // hide toggle link
        if (handle) {
            if (args.notoggle) {
                handle.setStyle('display', 'none');
            }
            handle.on('click', function(e) {
                e.preventDefault();
                this.view(0);
                return false;
            }, this);
            // Also handle space/enter key.
            handle.on('key', function(e) {
                e.preventDefault();
                this.view(0);
                return false;
            }, '13,32', this);
        }
        scope.toggle_textarea(false);
    }

    CommentHelper.prototype.api = config.wwwroot + '/local/generalnotes/ajax.php';

    CommentHelper.prototype.post = function() {
        var ta = Y.one('#dlg-content-'+this.client_id);
        var scope = this;
        var value = ta.get('value');
        if (value && value != get_string('addcomment', 'moodle')) {
            ta.set('disabled', true);
            ta.setStyles({
                'backgroundImage': 'url(' + image_url('i/loading_small', 'core') + ')',
                'backgroundRepeat': 'no-repeat',
                'backgroundPosition': 'center center'
            });
            var params = {'content': value};
            this.request({
                action: 'add',
                scope: scope,
                params: params,
                callback: function(id, obj, args) {
                    var scope = args.scope;
                    var cid = scope.client_id;
                    var ta = Y.one('#dlg-content-'+cid);
                    ta.set('value', '');
                    ta.set('disabled', false);
                    ta.setStyle('backgroundImage', 'none');
                    scope.toggle_textarea(false);
                    var container = Y.one('#comment-list-'+cid);
                    var result = scope.render([obj]);
                    var newcomment = Y.Node.create(result.html);
                    container.insertBefore(newcomment,container.one('li'));
                    var linkText = Y.one('#comment-link-text-' + cid);
                    if (linkText) {
                        linkText.set('innerHTML', get_string('commentscount', 'moodle', obj.count));
                    }
                    scope.register_pagination();
                    scope.register_delete_buttons();
                }
            }, true);
        }
    };
    CommentHelper.prototype.request = function(args, noloading) {
        var params = {};
        var scope = this;
        if (args['scope']) {
            scope = args['scope'];
        }
        //params['page'] = args.page?args.page:'';
        // the form element only accept certain file types
        params['sesskey']   = M.cfg.sesskey;
        params['action']    = args.action?args.action:'';
        params['client_id'] = this.client_id;
        params['contextid'] = this.contextid;
        if (args['params']) {
            for (i in args['params']) {
                params[i] = args['params'][i];
            }
        }
        var cfg = {
            method: 'POST',
            on: {
                complete: function(id,o,p) {
                    if (!o) {
                        alert('IO FATAL');
                        return false;
                    }
                    var data = Y.JSON.parse(o.responseText);
                    if (data.error) {
                        if (data.error == 'require_login') {
                            args.callback(id,data,p);
                            return true;
                        }
                        alert(data.error);
                        return false;
                    } else {
                        args.callback(id,data,p);
                        return true;
                    }
                }
            },
            arguments: {
                scope: scope
            },
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            data: build_querystring(params)
        };
        if (args.form) {
            cfg.form = args.form;
        }
        Y.io(this.api, cfg);
        if (!noloading) {
            this.wait();
        }
    };
    CommentHelper.prototype.render = function(list, newcmt) {
        var ret = {};
        ret.ids = [];
        var template = Y.one('#cmt-tmpl');
        var html = '';
        for(var i in list) {
            var htmlid = 'comment-'+list[i].id+'-'+this.client_id;
            var val = template.get('innerHTML');
            if (list[i].profileurl) {
                val = val.replace('___name___', '<a href="'+list[i].profileurl+'">'+list[i].fullname+'</a>');
            } else {
                val = val.replace('___name___', list[i].fullname);
            }
            if (list[i]['delete']||newcmt) {
                var tokens = {
                    user: list[i].fullname,
                    time: list[i].time
                };
                var deleteStr = Y.Escape.html(get_string('deletecommentbyon', 'moodle', tokens));
                list[i].content = '<div class="comment-delete">' +
                    '<a href="#" role="button" id ="comment-delete-' + this.client_id + '-' + list[i].id + '"' +
                    '   title="' + deleteStr + '">' +
                    '<span></span>' +
                    '</a>' +
                    '</div>' + list[i].content;
            }
            val = val.replace('___time___', list[i].time);
            val = val.replace('___picture___', list[i].avatar);
            val = val.replace('___content___', list[i].content);
            val = '<li id="'+htmlid+'">'+val+'</li>';
            ret.ids.push(htmlid);
            html = html + val;
        }
        ret.html = html;
        return ret;
    };
    CommentHelper.prototype.load = function(page) {
        var scope = this;
        var container = Y.one('#comment-ctrl-'+this.client_id);
        var params = {
            'action': 'get',
            'page': page
        };
        this.request({
            scope: scope,
            params: params,
            callback: function(id, ret, args) {
                var linkText = Y.one('#comment-link-text-' + scope.client_id);
                if (ret.count && linkText) {
                    linkText.set('innerHTML', get_string('commentscount', 'moodle', ret.count));
                }
                var container = Y.one('#comment-list-'+scope.client_id);
                var pagination = Y.one('#comment-pagination-'+scope.client_id);
                if (ret.pagination) {
                    pagination.set('innerHTML', ret.pagination);
                } else {
                    //empty paging bar
                    pagination.set('innerHTML', '');
                }
                if (ret.error == 'require_login') {
                    var result = {};
                    result.html = get_string('commentsrequirelogin', 'moodle');
                } else {
                    var result = scope.render(ret.list);
                }
                container.set('innerHTML', result.html);
                var img = Y.one('#comment-img-'+scope.client_id);
                if (img) {
                    img.set('src', image_url('t/expanded', 'core'));
                }
                args.scope.register_pagination();
                args.scope.register_delete_buttons();
            }
        });
    };

    CommentHelper.prototype.dodelete = function(id) {
        var scope = this,
            cid = scope.client_id,
            params = {'commentid': id};
        function remove_dom(cmt) {
            cmt.remove();
            var linkText = Y.one('#comment-link-text-' + cid),
                comments = Y.all('#comment-list-' + cid + ' li');
            if (linkText && comments) {
                linkText.set('innerHTML', get_string('commentscount', 'moodle', comments.size()));
            }
        }
        this.request({
            action: 'delete',
            scope: scope,
            params: params,
            callback: function(id, resp, args) {
                var htmlid= 'comment-'+resp.commentid+'-'+resp.client_id;
                var cmt = Y.one('#'+htmlid);
                remove_dom(cmt)
            }
        }, true);
    };
    CommentHelper.prototype.register_actions = function() {
        // add new comment
        var action_btn = Y.one('#comment-action-post-'+this.client_id);
        if (action_btn) {
            action_btn.on('click', function(e) {
                e.preventDefault();
                this.post();
                return false;
            }, this);
        }
        // cancel comment box
        var cancel = Y.one('#comment-action-cancel-'+this.client_id);
        if (cancel) {
            cancel.on('click', function(e) {
                e.preventDefault();
                this.view(0);
                return false;
            }, this);
        }
    };
    CommentHelper.prototype.register_delete_buttons = function() {
        var scope = this;
        // page buttons
        Y.all('div.comment-delete a').each(
            function(node, id) {
                var theid = node.get('id');
                var parseid = new RegExp("comment-delete-"+scope.client_id+"-(\\d+)", "i");
                var commentid = theid.match(parseid);
                if (!commentid) {
                    return;
                }
                if (commentid[1]) {
                    Y.Event.purgeElement('#'+theid, false, 'click');
                }
                node.on('click', function(e) {
                    e.preventDefault();
                    if (commentid[1]) {
                        scope.dodelete(commentid[1]);
                    }
                });
                // Also handle space/enter key.
                node.on('key', function(e) {
                    e.preventDefault();
                    if (commentid[1]) {
                        scope.dodelete(commentid[1]);
                    }
                }, '13,32');
                // 13 and 32 are the keycodes for space and enter.

                require(['core/templates', 'core/notification'], function(Templates, Notification) {
                    var title = node.getAttribute('title');
                    Templates.renderPix('t/delete', 'core', title).then(function(html) {
                        node.set('innerHTML', html);
                    }).catch(Notification.exception);
                });
            }
        );
    };
    CommentHelper.prototype.register_pagination = function() {
        var scope = this;
        // page buttons
        Y.all('#comment-pagination-'+this.client_id+' a').each(
            function(node, id) {
                node.on('click', function(e, node) {
                    e.preventDefault();
                    var id = node.get('id');
                    var re = new RegExp("comment-page-"+this.client_id+"-(\\d+)", "i");
                    var result = id.match(re);
                    this.load(result[1]);
                }, scope, node);
            }
        );
    };
    CommentHelper.prototype.view = function(page) {
        var commenttoggler = Y.one('#comment-link-' + this.client_id);
        var container = Y.one('#comment-ctrl-'+this.client_id);
        var ta = Y.one('#dlg-content-'+this.client_id);
        var img = Y.one('#comment-img-'+this.client_id);
        var d = container.getStyle('display');
        if (d=='none'||d=='') {
            // show
            if (!this.autostart) {
                this.load(page);
            } else {
                this.register_delete_buttons();
                this.register_pagination();
            }
            container.setStyle('display', 'block');
            if (img) {
                img.set('src', image_url('t/expanded', 'core'));
            }
            if (commenttoggler) {
                commenttoggler.setAttribute('aria-expanded', 'true');
            }
        } else {
            // hide
            container.setStyle('display', 'none');
            var collapsedimage = 't/collapsed'; // ltr mode
            if ( Y.one(document.body).hasClass('dir-rtl') ) {
                collapsedimage = 't/collapsed_rtl';
            } else {
                collapsedimage = 't/collapsed';
            }
            if (img) {
                img.set('src', image_url(collapsedimage, 'core'));
            }
            if (ta) {
                ta.set('value','');
            }
            if (commenttoggler) {
                commenttoggler.setAttribute('aria-expanded', 'false');
            }
        }
        if (ta) {
            //toggle_textarea.apply(ta, [false]);
            //// reset textarea size
            ta.on('focus', function() {
                this.toggle_textarea(true);
            }, this);
            //ta.onkeypress = function() {
            //if (this.scrollHeight > this.clientHeight && !window.opera)
            //this.rows += 1;
            //}
            ta.on('blur', function() {
                this.toggle_textarea(false);
            }, this);
        }
        this.register_actions();
        return false;
    };
    CommentHelper.prototype.toggle_textarea = function(focus) {
        var t = Y.one('#dlg-content-'+this.client_id);
        if (!t) {
            return false;
        }
        if (focus) {
            if (t.get('value') == get_string('addcomment', 'moodle')) {
                t.set('value', '');
                t.setStyle('color', 'black');
            }
        }else{
            if (t.get('value') == '') {
                t.set('value', get_string('addcomment', 'moodle'));
                t.setStyle('color','grey');
                t.set('rows', 2);
            }
        }
    };
    CommentHelper.prototype.wait = function() {
        var container = Y.one('#comment-list-'+this.client_id);
        container.set('innerHTML', '<div class="mdl-align"><img src="'+image_url('i/loading_small', 'core')+'" /></div>');
    };

    return {
        init: function (options) {
            return new CommentHelper(options);
        }
    }
});

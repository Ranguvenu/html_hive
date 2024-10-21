// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handle selection changes and actions on the competency tree.
 *
 * @module     block_userdasboard/navigations
 * @package    block_userdasboard
 * @copyright  2018 hemalathacarun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'core/url',
        'core/templates',
        'core/notification',
        'core/str',
        'core/modal_factory',
        'core/ajax',
        'local_costcenter/cardPaginate'
    ],
    function($,url, Templates, notification, str, ModalFactory, Ajax, cardPaginate) {
        var userdashboard = function(method, enabled_tab, thisdata){
            var params = {};
            params.filter = thisdata.status;
            params.filter_text = thisdata.filter_text;
            params.filter_offset = 0;
            params.filter_limit = 10;
            if(method == 'local_courses_userdashboard_content'){
                params.coursestype = thisdata.coursestype;
            }
            thisdata['container'].removeClass('justify-content-end');
            thisdata['container'].addClass('justify-content-center d-flex align-items-center');
            thisdata['container'].html('<img class="loading_img" src="'+M.cfg.wwwroot+'/local/ajax-loader.svg">');
            var promise = Ajax.call ([{
                methodname : method,
                args : params
            }]);

            promise[0].done(function(resp){

                Templates.render(thisdata.templatename, resp).then(function(html, js) {
                    // Templates.replaceNodeContents(thisdata['container'], html, js);
                    thisdata['container'].html(html);
                    thisdata['container'].removeClass('justify-content-center d-flex align-items-center');
                    thisdata['container'].addClass('justify-content-end');
                });
                
            });

        };
        return {        
     
        /**
         * Initialise this page (attach event handlers etc).
         *
         * @method init
         * @param {Object} model The tree model provides some useful functions for loading and searching competencies.
         * @param {Number} pagectxid The page context ID.
         * @param {Object} taxonomies Constants indexed by level.
         * @param {Object} rulesMods The modules of the rules.
         */
        init: function() {
            // var methods = {};
            var container = $('.userdashboard_module_content');
            // methods.local_courses = 'local_courses_userdashboard_content';
            // methods.local_classroom = 'local_classroom_userdashboard_classrooms';
            // methods.local_certification = 'local_certification_userdashboard_certification';
            // methods.local_program = 'local_program_userdashboard_program';
            // methods.local_learningplan = 'local_learningplan_userdashboard_learningplans';
            // methods.local_evaluation = 'local_evaluation_userdashboard_evaluations';
            // methods.local_onlinetests = 'local_onlinetests_userdashboard_onlinetests';
            
            $(document).on('click', '.userdashboard_menu_link', function(){
                var active = $(this).parent('.dashboard-stat').hasClass('active_main_tab');
                if(!active){
                    $('.dashboard-stat').removeClass('active_main_tab');
                    $(this).parent('.dashboard-stat').addClass('active_main_tab');
                    var filter_text = $('#userdashboard_filter').val();
                    if(filter_text == undefined){
                        filter_text = '';
                    }
                    var data = $(this).data();
                    data.container = container;
                    data.filter_text = filter_text;
                    var type = $(this).attr('id');
                    if(type == 'mooc_courses'){
                        var coursestype = 'mooc';
                    }else if(type == 'ilt_courses'){
                        var coursestype = 'ilt';
                    }else{
                        var coursestype = null;
                    }
                    data.coursestype = coursestype;
                    var pluginname = data.pluginname;
                    var enabled_tab = data.tabname;
                    var method = pluginname+'_userdashboard_content';
                    return new userdashboard(method, enabled_tab, data);
                }
            });

            $(document).on('click', '.userdashboard_tab_link', function(){
                var filter_text = $('#userdashboard_filter').val();
                
                var type = $(".active_main_tab :first-child").attr('id');
                if(type == 'mooc_courses'){
                    var coursestype = 'mooc';
                }else if(type == 'ilt_courses'){
                    var coursestype = 'ilt';
                }else{
                    var coursestype = null;
                }
                if(filter_text == undefined){
                    filter_text = '';
                }
                var data = $(this).data();
                data.container = container.find('.divslide');
                data.filter_text = filter_text;
                data.coursestype = coursestype;
                var pluginname = data.pluginname;
                var enabled_tab = data.tabname;
                var method = pluginname+'_userdashboard_content';
                return new userdashboard(method, enabled_tab, data);
            });
            $(document).on('keyup', '#userdashboard_filter', function(){
                var filter_text = $(this).val();
                var data = $(this).data();
                var type = $(".active_main_tab :first-child").attr('id');
                if(type == 'mooc_courses'){
                    var coursestype = 'mooc';
                }else if(type == 'ilt_courses'){
                    var coursestype = 'ilt';
                }else{
                    var coursestype = null;
                }
                data.container = container.find('.divslide');
                data.filter_text = filter_text;
                data.coursestype = coursestype;
                var pluginname = data.pluginname;
                var enabled_tab = data.tabname;
                var method = pluginname+'_userdashboard_content';
                return new userdashboard(method, enabled_tab, data);
            });
            $(document).ready(function(){
                var elem = $('.active_main_tab').find('a');
                var data = elem.data();
                data.container = container;
                data.filter_text = '';
                var type = elem.attr('id');
                if(type == 'mooc_courses'){
                    var coursestype = 'mooc';
                }else if(type == 'ilt_courses'){
                    var coursestype = 'ilt';
                }else{
                    var coursestype = null;
                }
                data.coursestype = coursestype;
                var pluginname = data.pluginname;
                var enabled_tab = data.tabname;
                var method = pluginname+'_userdashboard_content';
                return new userdashboard(method, enabled_tab, data);
            });

        },
        makeActive: function(identifier){
            $(document).ready(function(){
                if(!$("#"+identifier).hasClass('active')){
                    $("li.nav-item .nav-link.active").removeClass('active');
                    $("#"+identifier).addClass('active');
                }
            });
        },
        courseexpiry: function(args){
            return str.get_strings([{
                key: 'confirm'
            },
            {
                key: 'course_expiry_user',
                component: 'local_courses',
                param :args
            },
            {
                key: 'confirm'
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.CANCEL,
                    body: s[1]
                }).done(function(modal) {
                    this.modal = modal;
                     modal.show();
                }.bind(this));
            }.bind(this));
        },

        load: function(args){
            $(document).on('click', '.userdashboard_tab_link', function(){
                var data = $(this).data();
                var url = window.location.href;
                var newurl = url.substr(0,url.indexOf("?tab"))+'?tab='+data.tabname+'&type='+args.type;
                history.pushState('', '', newurl);
                var pluginname = data.pluginname;
                var targetid = pluginname.replace("local", 'dashboard');
                var content  = "<div data-region='"+targetid+"-count-container'></div><div data-region='"+targetid+"-list-container' id ='"+targetid+"id'></div><span class='overlay-icon-container hidden' data-region='overlay-icon-container'><span class='loading-icon icon-no-margin'></span></span>";
                var paginatedata = $('.userdashboard_content_detailed').data();
                var options = JSON.parse(JSON.parse(paginatedata.options));
                var dataoptions = JSON.parse(JSON.parse(paginatedata.dataoptions));
                var filterdata = JSON.parse(JSON.parse(paginatedata.filterdata));
                options.filter = data.tabname;
                var newoptions = options;
                $("#global_filter").val('');
                $("#global_filter").data('options', newoptions);
                $('#'+targetid).html(content);
                cardPaginate.reload(newoptions, dataoptions, filterdata);
            });
        }
    }; 
});

/**
 * Add a create new group modal to the page.
 *
 * @module     local_users/newuser
 * @class      NewForum
 * @package    local_users
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',  'theme_epsilon/jquery.toolbar'],
    function($, toolbar) {
        return {
            load: function(){},
            tooltipicons: function() {
                $.each($('.showoptions'), function(){
                    $(this).toolbar({
                        content: $(this).data('toolbar'),
                        style: 'info'
                    });
                });
            
                $('.tool-item').on('click', function () {
                    window.location = $(this).attr('href');
                });
            },
        };
});
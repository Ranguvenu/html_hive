/**
 * Add a create new group modal to the page.
 *
 * @module     local_forum/ratings
 * @package    local_forum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/str', 'core/templates', 'jquery', 'jqueryui'],
function (Ajax, Str, templates, $) {
    
    return {
        init: function (target, options) {
            options = JSON.parse(options);
            return new ratePicker(target, options);
        },
        updatevalues: function (args) {
            
            $.ajax({
                url: M.cfg.wwwroot + "/local/forum/ratings.php?likearea=" + args.likearea + "&forumid=" + args.forumid + "&discussionid=" + args.discussionid +"&parentid=" + args.parentid +"&postid=" + args.postid +"&action=" + args.action,
                beforeSend: function () {
                    $("#loading_image").show();
                },
                success: function (data) {
                    window.location.reload();
                    if (data.dislike) {
                        $(".fa-thumbs-down thumb_dislike_" + args.postid).css('color', '#0769ad');
                        $(".fa-thumbs-up thumb_like_" + args.postid).css('color', '#6d6f71');
                    } else {
                        $(".fa-thumbs-up thumb_like_" + args.postid).css('color', '#0769ad');
                        $(".fa-thumbs-down thumb_dislike_" + args.postid).css('color', '#6d6f71');
                    }
                    $(".count_unlike_" + args.postid).html(data.dislike);
                    $(".count_like_" + args.postid).html(data.like);
                    $("#loading_image").hide();
                }
            });
        },
   
        load: function () {

        }
    }
});
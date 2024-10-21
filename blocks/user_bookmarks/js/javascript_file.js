function addBookmark(bookmarkURL, defaultTitle, ctype, cid) {
        // document.write(bookmarkURL +'<br>' +defaultTitle +'<br>' +ctype +'<br>' +cid  +'<br>' +sesskey);

        var newBookmarkTitle = prompt('Enter bookmark title', defaultTitle);

        if (newBookmarkTitle == "" || newBookmarkTitle == null) {
                newBookmarkTitle = defaultTitle;
        } else {
                var redirectPage = M.cfg.wwwroot + "/blocks/user_bookmarks/create.php?bookmarkurl=" + escape(bookmarkURL) + "&title=" + encodeURIComponent(newBookmarkTitle) +"&ctype=" +ctype +"&cid=" +cid;
                window.location = redirectPage;
        }
}


// for update bookmark......
function updateBookmark(bookmarkURL, defaultTitle, sesskey, wwwroot) {
        var newBookmarkTitle = prompt('Edit bookmark title',defaultTitle);
        if (newBookmarkTitle == "" || newBookmarkTitle == null) {
        newBookmarkTitle = defaultTitle;
        }else {
        var redirectPage = wwwroot + "/blocks/user_bookmarks/update.php?bookmarkurl=" + escape(bookmarkURL) 
                 + "&title=" + encodeURIComponent(newBookmarkTitle) + "&sesskey=" + sesskey;
        window.location = redirectPage;
        }
}


// for delete bookmark.....
function deleteBookmark(bookmarkURL, userid, cid) {
        // document.write(bookmarkURL +"<br>" +userid +"<br>" +cid +"<br>" +sesskey)
        var redirectPage = M.cfg.wwwroot +"/blocks/user_bookmarks/delete.php?bookmarkurl=" +escape(bookmarkURL) +"&userid=" +userid +"&cid=" +cid ;
        window.location = redirectPage;
}
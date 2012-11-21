$(function() {
    
    /* Ask permissions in a modal window. */
    $("#oauth").bind("click", function() {  
        var self = this;

        /* Ask for permissions. */
        FB.ui({ 
            method: "oauth",
            client_id: settings.client_id
            //redirect_uri: settings.tab_url
        }, function(response) {
           /* Response contains user_id first time permissions are        */
           /* given. Later it is always undefined. If permissions are not */
           /* given reponse is also undefined. Old version of JavaScript  */
           /* API also returned false. Check for both just in case.       */

           console.log(response);
           if (false === response || typeof response === "undefined") {
               response = {};
           }

           /* If you need to send some data. Add it via $.extend to */
           /* response object.                                      */
           $.extend(response, {
               foo: "bar"
           });

           $.post("/entries", response, function(data) {
               console.log(data);
               if ("fail" === data.status) {
                   console.log("Submitting entry failed");
               } else {
                   console.log("Submitting entry ok");
                   /* Reload the tab since we show new content.  */
                   /* Ugly kludge. */
                   //top.location.href = settings.tab_url;
                   /* Better is to load something via AJAH call. */
               }
            }, "json");
        });

        return false;
    });
   
    /* Post to wall eg. share. */
    $("#feed").bind("click", function(event) {
        var share = {
            method: "feed",
            name: "Lorem ipsum dolor sit amet!",
            link: "http://www.google.com/",
            //redirect_uri: settings.tab_url,
            picture: "http://placekitten.com/95/95",
            caption: "Claritas est etiam processus dynamicus.",
            description: "Claritas est etiam processus dynamicus, qui sequitur mutationem consuetudium lectorum. Mirum est notare quam littera gothica, quam nunc putamus parum claram."
        };

        FB.ui(share, function(response) {
            if (response && response.post_id) {
                console.log(response);
                /* Log wallpost for later use. */
                var post_data = { post_id: response.post_id };
                $.post("/shares", post_data, function(data) {
                    console.log(data);
                }, "json");
            } else {
                console.log("Share was not made.");
            }
        });
        return false;
    });

    /* Send a Facebook message. */
    $("#send").bind("click", function(event) {
        var message = {
            method: "send",
            name: "Lorem ipsum dolor sit amet!",
            link: "http://www.google.com/",
            picture: "http://placekitten.com/95/95",
            //to: next_uid,
            description: "Claritas est etiam processus dynamicus, qui sequitur mutationem consuetudium lectorum. Mirum est notare quam littera gothica, quam nunc putamus parum claram."
        };

        FB.ui(message, function(response) {
            console.log(response);
            if (response && response.success) {
                /* Log message for later use. */
                var post_data = { };
                $.post("/messages", post_data, function(data) {
                    console.log(data);
                }, "json");
            } else {
                console.log("Message was not send.");
            }
        });
        return false;
    });
    
});
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
    
    $("#login_select").bind("click", function(event) {
        /* Ask for permissions. */
        /* Apparently this is now also inline http://goo.gl/22sfO */
        FB.login(function(response) {
                        
            if (response.authResponse) {
                console.log("Logged in");
                console.log(response.authResponse);
                
                var submit_data = { signed_request: response.authResponse.signedRequest,
                                    oauth_token: response.authResponse.accessToken };
                
                TDFriendSelector.init({debug: false});
                                
                var selector = TDFriendSelector.newInstance({
                    maxSelection    : 1,
                    friendsPerPage  : 5,
                    autoDeselection : true,
                    callbackSubmit: function(selectedFriendIds) {
                        
                        /* Double check just in case. */
                        if (1 == selectedFriendIds.length) {
                            $.extend(submit_data, {uid: selectedFriendIds[0]});

                            console.log(submit_data);

                            $.post("/friends", submit_data, function(data) {
                                console.log(data);
                                if ("ok" == data.status) {
                                    console.log("Friend save succeeded.");
                                } else {
                                    console.log("Friend save failed.");                                
                                }
                            }, "json");
                        }
                        
                    }
                });
                
                selector.showFriendSelector();
            } else {
                console.log("User cancelled login or did not fully authorize.");
            }
        });
        
        return false;
    });
    
    
    
});
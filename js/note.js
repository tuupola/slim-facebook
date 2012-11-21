<div id="fb-root"></div>
<script>
    window.fbAsyncInit = function() {
        FB.init({
            appId      : '486468361373341',
            status     : true,
            cookie     : true,
            xfbml      : true
        });
        FB.Canvas.setAutoGrow();
	FB.Event.subscribe('edge.create',
		function(response) {
			try {
				adf.track(105067,1972299,{});
				_gaq.push(['_trackEvent', 'likes', response]);
			} catch (e) {}
		}
	);
	FB.Event.subscribe('edge.remove',
		function(response) {
			try {
				adf.track(105067,1972433,{});
				_gaq.push(['_trackEvent', 'unlike', response]);
			} catch (e) {}
		}
	);
    };
    (function(d){
        var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement('script'); js.id = id; js.async = true;
        js.src = "//connect.facebook.net/et_EE/all.js";
        ref.parentNode.insertBefore(js, ref);
    }(document));
</script>

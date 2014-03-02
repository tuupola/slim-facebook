<?php

error_reporting(E_ALL);
ini_set("display_errors","On");
ini_set("display_startup_errors","On");
date_default_timezone_set("Europe/Helsinki");

require "vendor/autoload.php";

/* Setup Slim */
$app = new \Slim\Slim(array(
    /*
    "log.writer" => new \Slim\Extras\Log\DateTimeFileWriter(array(
        "path" => "./logs",
        "name_format" => "Y-m-d",
        "date_format" => "Y-m-d H:i:s",
        "message_format" => "%label% [%date%] %message%"
    ))
    */
));

$app->add(new Slim\Middleware\SessionCookie());
/* This middleware is currently broken. */
//$app->add(new Slim\Extras\Middleware\FacebookMethodFix());


/* Normally you should not commit these publicly. */
/* This is just an demo app. */
$app->config(array(
    "client_id"     => "126680937488146",
    "client_secret" => "47011911ec9b48a02d3619611d788dbe",
    "tab_url"       => "https://www.facebook.com/pages/Loophole/306971553786?sk=app_126680937488146",
    "host"          => "slim-ar-facebook.taevas.com",

    "log.enabled"   => true,
    "log.level"     => \Slim\Log::DEBUG,

    "templates.path" => __DIR__ . "/templates/"
));

/* Setup Facebook. */
$facebook = new Facebook(array(
    "appId"  => $app->config("client_id"),
    "secret" => $app->config("client_secret")
));

$connections = array(
    "development" => "mysql://example:example@mysql.example.com/example_slim;charset=utf8",
    "production"  => "mysql://example:example@localhost/example_slim;charset=utf8"
);

ActiveRecord\Config::initialize(function($cfg) use ($connections, $app) {
    $cfg->set_model_directory(__DIR__ . "/models");
    $cfg->set_connections($connections);

    # Default connection is now production
    $cfg->set_default_connection("production");

    //$cfg->set_logging(true);
    //$cfg->set_logger(new \Slim\Extras\Log\ActiveRecordAdapter($app->getLog(), \Slim\Log::DEBUG));
});

$app->hook("slim.before", function() use ($facebook) {

    /* IE has problems with crossdomain cookies. */
    header('P3P: CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

    /* When using FB.ui("oauth", ...) */
    /* Apparently FB.login() is now inline so this is not necessary */
    /* anymore http://goo.gl/22sfO */
    if(isset($_REQUEST["session"])) {

        $session_data = json_decode($_REQUEST["session"], true);

        $url  = "https://graph.facebook.com/oauth/exchange_sessions";
        $curl = curl_init($url);
        $post = array(
          "client_id"     => $facebook->getAppId(),
          "client_secret" => $facebook->getApiSecret(),
          "sessions"      => $session_data["session_key"]
        );

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);

        $exchange_data = json_decode($response, true);
        $access_token  = $exchange_data[0]["access_token"];

        /* Save access toke to session since not all  */
        /* requests come from Facebook iframe. */
        $_SESSION["access_token"] = $access_token;

    }

    /* When using FB.login(...) or already installed. */
    if(isset($_REQUEST["signed_request"])) {
        $signed_request = $facebook->getSignedRequest();
        if (isset($signed_request["oauth_token"])) {

            /* Save access token to session since not all  */
            /* requests come from Facebook iframe. */
            $_SESSION["access_token"] = $signed_request["oauth_token"];
        }
    }

    /* Set the access token if we have any. */
    if (isset($_SESSION["access_token"])) {
        $facebook->setAccessToken($_SESSION["access_token"]);
    }

    /*
    print $uid = $facebook->getUser();
    */

});

$app->get("/", function() use ($app, $facebook) {
    /* If Facebook scraper show content with og tags etc. */
    if (facebook_external_hit()) {
        $app->render("index.html", array(
            "facebook" =>  $facebook,
            "app" => $app
        ));
    /* If user who manually arrived here redirect to tab. */
    } else {
        $app->redirect($app->config("tab_url"));
    }
});

$app->get("/install", function() use ($app, $facebook) {
   $app->render("install.html", array("app_id" =>  $facebook->getAppId()));
});

/* Facebook converts GET request to POST. Provide both for easier */
/* development. */
$app->map("/tab", function() use ($app, $facebook) {
    $signed_request = $facebook->getSignedRequest();

    /* If you need to like gate (yuck) you can do something like
    if ($signed_request["page"]["liked"]) {
        render liked tab
    } else {
        render not liked tab
    }
    */

    $app->render("tab.html", array(
        "facebook" =>  $facebook,
        "app" => $app
    ));

    $app->getLog()->info("Tab rendered");

})->via("GET", "POST");

/* User gave permissions to application. */
$app->post("/entries", function() use ($app, $facebook) {
    /* Creates new user with uid, oauth_token and name if does not exist. */
    $user = current_user();

    /* Save extra data if needed. */
    /*
    $user->foo = $app->request()->post("foo");
    $user->save();
    */

    /* Also log to a file. */
    $message = sprintf("%s (%s) participated in campaign (%s)",
                        $user->name, $user->uid, $_SERVER["REMOTE_ADDR"]);
    $app->getLog()->info($message);

    $data["status"] = "ok";

    $app->contentType("application/json");
    print json_encode($data);

});

/* User posted something to wall. */
$app->post("/shares", function() use ($app, $facebook) {
    /* Creates new user with uid, oauth_token and name if does not exist. */
    /* If not logged in creates dummy user with uid = 0 */
    $user = current_user();

    /* Log share to local database. */
    $share = new Share();
    $share->post_id = $app->request()->post("post_id");
    $share->user = $user;
    $share->save();

    /* Also log to a file. */
    $message = sprintf("%s (%s) made a Facebook share (%s)",
                        $user->name, $user->uid, $_SERVER["REMOTE_ADDR"]);
    $app->getLog()->info($message);


    $data["status"] = "ok";
    $data["id"]     = $share->id;

    $app->contentType("application/json");
    print json_encode($data);
});

/* User sent a Facebook message. */
$app->post("/messages", function() use ($app, $facebook) {
    /* Creates new user with uid, oauth_token and name if does not exist. */
    /* If not logged in creates dummy user with uid = 0 */
    $user = current_user();

    /* Log share to local database. */
    $message = new Message();
    $message->user = $user;
    $message->save();

    $data["status"] = "ok";
    $data["id"]     = $message->id;

    /* Also log to a file. */
    $message = sprintf("%s (%s) sent a Facebook message (%s)",
                        $user->name, $user->uid, $_SERVER["REMOTE_ADDR"]);
    $app->getLog()->info($message);

    $app->contentType("application/json");
    print json_encode($data);
});

/* User chose a friend */
$app->post("/friends", function() use ($app, $facebook) {
    /* Creates new user with uid, oauth_token and name if does not exist. */
    /* If not logged in creates dummy user with uid = 0 */
    $user = current_user();

    /* Log message. */
    $friend = new Friend();
    $friend->uid = $app->request()->post("uid");
    $friend->user = $user;

    $data = user_info($friend->uid);
    $friend->name = $data["name"];

    $friend->save();

    /* Also log to a file. */
    $message = sprintf("%s (%s) and %s (%s) participated in campaign (%s)",
                        $user->name, $user->uid, $friend->name, $friend->uid,
                        $_SERVER["REMOTE_ADDR"]);
    $app->getLog()->info($message);


    $data["status"] = "ok";
    $data["id"]     = $friend->id;

    $app->contentType("application/json");
    print json_encode($data);
});

/* Demonstrate redirect which jumps out of iframe. */
$app->get("/redirect", function() use ($app, $facebook) {
    facebook_redirect($app->config("tab_url"));
});

$app->run();

/* Helpers */

/* Creates new user with uid, oauth_token and name if does not exist. */
/* If not logged in creates dummy user with uid = 0 */
function current_user() {
    global $facebook;

    $uid  = $facebook->getUser();
    $user = User::find_by_uid($uid);

    /* If did not exist before, create one with basic info. */
    if (is_null($user)) {
        $data = user_info($uid);
        $user = new User();
        $user->uid  = $uid;
        $user->name = $data["name"];
        $user->oauth_token = $_SESSION["access_token"];
        $user->save();
    }

    return $user;
};

/* Facebook data for current user. False if fails. */
function user_info($uid) {
    global $facebook;

    $data = false;
    if ($facebook->getUser()) {
        try {
            $data = $facebook->api("/". $uid);
        } catch (FacebookApiException $e) {
            print_r($e);
        }
    }
    return $data;
};

function facebook_redirect($url) {
    print '<script type="text/javascript">top.location.href="' . $url . '"</script>';
};

function facebook_external_hit() {
    global $app;
    $user_agent = $app->request()->getUserAgent();
    return false !== strpos($user_agent, "facebookexternalhit");
};


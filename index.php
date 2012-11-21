<?php
  
error_reporting(E_ALL);
ini_set("display_errors","On");
ini_set("display_startup_errors","On");
date_default_timezone_set("Europe/Helsinki");


set_include_path(get_include_path() . PATH_SEPARATOR . "./vendor");
require "Slim/Slim.php";
\Slim\Slim::registerAutoloader();

/* Setup Slim */
$app = new \Slim\Slim();
$app->add(new Slim\Middleware\SessionCookie());
$app->config(array(
    "client_id"     => "126680937488146",
    "client_secret" => "47011911ec9b48a02d3619611d788dbe",
    "tab_url"       => "https://www.facebook.com/",
    "host"          => "slim-ar-facebook.taevas.com"
));

/* Setup Facebook. Normally you should not commit these publicly. */
/* This is just an demo app. */
require "Facebook/facebook.php";
$facebook = new Facebook(array(
    "appId"  => $app->config("client_id"),
    "secret" => $app->config("client_secret")
));

require "ActiveRecord/ActiveRecord.php";

$connections = array(
    "development" => "mysql://example:example@mysql.example.com/example_slim;charset=utf8",
    "production"  => "mysql://example:example@localhost/example_slim;charset=utf8"
);

ActiveRecord\Config::initialize(function($cfg) use ($connections)
{
    $cfg->set_model_directory("models");
    $cfg->set_connections($connections);
    
    # Default connection is now production
    $cfg->set_default_connection("production");
});

$app->hook("slim.before", function() use ($facebook) {
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

$app->map("/", function() use ($app, $facebook) {
    print_r($facebook);
})->via("GET", "POST");

$app->get("/install", function() use ($app, $facebook) {
   $app->render("install.html", array("app_id" =>  $facebook->getAppId())); 
});

$app->map("/tab", function() use ($app, $facebook) {
    $app->render("tab.html", array(
        "facebook" =>  $facebook,
        "app" => $app
    )); 
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
    
    $data["status"] = "ok";
    
    $app->contentType("application/json");
    print json_encode($data);
    
});

/* User posted something to wall. */
$app->post("/shares", function() use ($app, $facebook) {
    /* Creates new user with uid, oauth_token and name if does not exist. */
    /* If not logged in creates dummy user with uid = 0 */
    $user = current_user();

    /* Log share. */
    $share = new Share();
    $share->post_id = $app->request()->post("post_id");
    //$share->user = $user; // This does not work.
    $share->user_id = $user->id; // REALLY?! WTF ActiveRecord?
    $share->save();
    
    $data["status"] = "ok";
    
    $app->contentType("application/json");
    print json_encode($data);
});

/* User sent a Facebook message. */
$app->post("/messages", function() use ($app, $facebook) {
    /* Creates new user with uid, oauth_token and name if does not exist. */
    /* If not logged in creates dummy user with uid = 0 */
    $user = current_user();

    /* Log message. */
    $message = new Message();
    //$message->user = $user; // This does not work.
    $message->user_id = $user->id; // REALLY?! WTF ActiveRecord?
    $message->save();
    
    $data["status"] = "ok";
    
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
    //$message->user = $user; // This does not work.
    $friend->user_id = $user->id; // REALLY?! WTF ActiveRecord?
    $friend->save();
    
    $data["status"] = "ok";
    
    $app->contentType("application/json");
    print json_encode($data);
});

$app->run();

/* Helpers */
function current_user() {
    global $facebook;
    
    $uid  = $facebook->getUser();
    $user = User::find_by_uid($uid);

    /* If did not exist before, create one with basic info. */
    if (is_null($user)) {
        $data = current_user_info();
        $user = new User();
        $user->uid  = $uid;
        $user->name = $data["name"];
        $user->oauth_token = $_SESSION["access_token"];
        $user->save();
    }
    
    return $user;
};

/* Facebook data for current user. False if fails. */
function current_user_info() {
    global $facebook;
    
    $data = false;
    if ($facebook->getUser()) {
        try {
            $data = $facebook->api("/me");
        } catch (FacebookApiException $e) {
            print_r($e);
        }
    }
    return $data;
};


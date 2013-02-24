<?php 

namespace Slim\Extras\Middleware;

class FacebookMethodFix extends \Slim\Middleware 
{

  public function call()
      {
          $req = $this->app->request();
          $env = $this->app->environment();

          if ($req->isPost()) {              
              /* If signed_request exists this should have been a GET. */
              if (null !== $req->post("signed_request")) {
                  $env["REQUEST_METHOD"] = "GET";
              }
          }

          $this->next->call();
      }

};
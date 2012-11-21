<?php

/*

CREATE TABLE shares (
  id INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  post_id VARCHAR(255),
  created_at DATETIME,
  updated_at DATETIME,
  user_id INTEGER UNSIGNED 
);

*/

class Share extends ActiveRecord\Model 
{
    
    static $belongs_to = array(
        array("user")
    );

}
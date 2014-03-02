<?php

/*

CREATE TABLE messages (
  id INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  created_at DATETIME,
  updated_at DATETIME,
  user_id INTEGER UNSIGNED
);

*/

class Message extends Illuminate\Database\Eloquent\Model {

    public function user() {
        return $this->belongsTo("User");
    }

}
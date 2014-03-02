<?php

/*

CREATE TABLE friends (
  id INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  uid BIGINT UNSIGNED, # Should this be VARCHAR(255) on 32 bit systems?
  name VARCHAR(255),
  created_at DATETIME,
  updated_at DATETIME,
  user_id INTEGER UNSIGNED
);

*/

class Friend extends Illuminate\Database\Eloquent\Model {

    public function user() {
        return $this->belongsTo("User");
    }

}
<?php

/*

CREATE TABLE users (
  id INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  uid BIGINT UNSIGNED, # Should this be VARCHAR(255) on 32 bit systems?
  name VARCHAR(255),
  oauth_token VARCHAR(255),
  created_at DATETIME,
  updated_at DATETIME
);

*/

class User extends Illuminate\Database\Eloquent\Model {

    protected $fillable = array("uid");

    public function shares() {
        return $this->hasMany("Share");
    }

    public function messages() {
        return $this->hasMany("Message");
    }

    public function friends() {
        return $this->hasMany("Friend");
    }

    public function profileUrl() {
        return "http://www.facebook.com/profile.php?id=" . $this->uid;
    }

    public function imageUrl() {
        return "https://graph.facebook.com/" . $this->uid . "/picture?type=square";
    }
}



REST 


METHOD orth STATE



DATA => drive Web State to CHANGE.

req < DATA  > res


$REQUEST  => $RESPONE ( $HEADER)
    ||
    VV
    ParamCheck(must|may,one|many, type);
    default:string, must,one,
a:int; b:int,may;; c:int,may,many;

TRAIL Methord: POST, GET
TRAIL STATE: 


Interface Method {
      onInit();
      onGet();
      onPost();
      onDelete();
      onPut();
}

Object User extend Method.


user_register.php
user_login.php
user_logout.php
token_post.php
token_delete.php



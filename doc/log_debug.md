# description

engine or application should use log and debug info seperate. it's confused but should be consider as diferrent requiment.


# log

log should be use as in production env. log as nesseccerary as configed msg to syslog, for view later.
log can contain any may usaful info. depend on req time.


# debug

debug info should be use in development env only!
debug info should contain info for the current session or request.


# diff

the most diff is that: dev vs product .

if a info is need in a product env, it should be write use log.
if a info is need temperary for fix something, it should be write as debug.

# so

thins should use log:  db sql;

things should use debug:  fatal error;

things goes both: error.


# in another view

msg show to the programer: debug;
msg show to the devop:     log;
msg show to the user:      error;


# 20170824 finally

log:      always goes to  syslog.
error:    always goes to syslog and end user;  normal throw exception.
debug:    goes to syslog and end user; and can be turned off.

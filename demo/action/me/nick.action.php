<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8 nobomb
// 设置/获取昵称。
IN::mayHave('nick', '/\w{2,50}/');

$nick = UserM::UpdateNick($uid, $_nick);

if(empty($nick))$nick = 'you';

OUT::done(array('nick'=>$nick));

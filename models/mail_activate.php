<?php

class MailActivate extends AppModel {

    var $name = 'MailActivate';
    var $validate = array(
        'mail' => array(
            'rule' => array('email', true),
            'message' => '请输入一个合法的邮件地址'
        ),
    );
}

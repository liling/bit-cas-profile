<?php

class MobileActivate extends AppModel {

    var $name = 'MobileActivate';

    var $validate = array(
        'mobile' => array(
            'rule' => '/^1[0-9]{10}$/',
            'message' => '请输入一个合法的手机号码'
        ),
    );
}

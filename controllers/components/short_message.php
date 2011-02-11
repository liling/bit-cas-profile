<?php

class ShortMessageComponent extends Object { 

    function send($mobile, $message) {

        $debug = Configure::read('SMS.debug');
        $username = Configure::read('SMS.username');
        $password = Configure::read('SMS.password');
        $send_url = "http://www.xunsai.net:8000/?user=$username&password=$password&phonenumber=%s&text=%s&charset=utf-8";

        $url = sprintf($send_url, urlencode($mobile), urlencode($message));
        if (!$debug) {
            $output = file_get_contents($url);
        } else {
            $output = '<TITLE>您所发送的信息已经成功提交</TITLE>';
        }
        if ($output) {
            if (!$debug) $output = iconv('GB2312', 'UTF-8', $output);

            $sms = array('phone' => $mobile, 'message' => $message,
                         'url' => $url, 'output' => $output);
            $this->SmsLog->set(array('SmsLog' => $sms));
            $this->SmsLog->save();

            ereg("<TITLE>(.*)</TITLE>", $output, $regs);
            if (!empty($regs)) {
                $result = $regs[1];
            } else {
                $result = '未知错误';
            }
        } else {
            $result = '网络故障，无法连接短信服务器';
        }

        if ($result == '您所发送的信息已经成功提交') {
            $result = true;
        }

        return $result;
    }

}

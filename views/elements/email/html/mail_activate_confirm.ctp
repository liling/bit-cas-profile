<p><?php echo $user['fullname'] ?>：您好，</p>

<p>欢迎使用校园网单点登录服务，您刚刚申请将该服务的密保邮箱地址修改为</p>

<blockquote><p><?php echo $mail; ?></p></blockquote>

<p>要确认此操作，请点击下面的链接</p>

<blockquote><p><a href="<?php echo $confirm_url; ?>"><?php echo $confirm_url; ?></a></p></blockquote>

<p>若您并未试图修改该服务的密保邮箱地址，请忽略此邮件。</p>


<p>此邮件由单点登录系统自动发送，请勿回复。</p>

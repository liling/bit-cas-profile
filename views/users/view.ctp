<img src="<?php echo $this->Html->url('/users/photo'); ?>" alt="照片"/>

<p>人员编码：<?php echo $person['employeenumber']; ?></p>
<p>姓名：<?php echo $person['cn']; ?></p>
<p>身份：<?php echo $person['employeetype']; ?></p>
<p>密保邮件：<a href="mailto:<?php echo $person['mail']; ?>"><?php echo $person['mail']; ?></a></p>

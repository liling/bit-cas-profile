<div id="column3" class="photo">
    <img src="<?php echo $this->Html->url('/users/photo'); ?>" alt="照片"/>
</div>

<div id="column4" class="description">

    <h3>基本信息</h3>
    <p>姓名：<?php echo $person['cn']; ?></p>
    <p>身份：<?php echo $person['employeetype']; ?></p>
    <p>工号/学号：<?php echo $person['employeenumber']; ?></p>
    <p>密保邮件：<?php echo $person['mail']; ?> <?php echo $this->Html->link('修改密保邮箱', '/mail_activates/setmail'); ?></p>
    <p>密保手机：<?php echo empty($person['mobile']) ? '未设定' : $person['mobile']; ?> <?php echo $this->Html->link('修改密保手机', '/mobile_activates/setmobile'); ?></p>

    <h3>帐号信息</h3>
    <p>一卡通：</p>
    <p>校园网：</p>
    <p>校园邮件：</p>
</div>

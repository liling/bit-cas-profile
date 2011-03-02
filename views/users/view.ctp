<div id="column3" class="photo">
    <img src="<?php echo $this->Html->url('/users/photo'); ?>" alt="照片"/>
</div>

<div id="column4" class="description">

    <h3>基本信息</h3>
    <p>姓名：<?php echo $person['cn']; ?></p>
    <p>身份：<?php echo $person['employeetype']; ?></p>
    <p>工号/学号：<?php echo $person['employeenumber']; ?></p>
    <p>密保邮件：<?php echo $person['mail']; ?> <?php echo $this->Html->link('修改密保邮箱', '/mail_activates/setmail'); ?></p>
    <p>密保手机：<?php echo empty($person['mobile']) ? '未设定' : $person['mobile']; ?>
    <?php
        if ($user['User']['mobile_locked']) {
            echo $html->link('解锁', '/users/unlock_mobile');
        } else {
            echo $html->link('修改密保手机', '/mobile_activates/setmobile');
            echo '&nbsp;';
            echo $html->link('加锁', '/users/lock_mobile');
        }
    ?></p>

    <!--<h3>帐号信息</h3>
    <p>一卡通：</p>
    <p>校园网：</p>
    <p>校园邮件：</p>-->

    <h3>访问日志</h3>
    <table>
        <tr>
            <th>时间</th>
            <th>IP地址</th>
            <th>操作</th>
        </tr>
    <?php foreach ($trails as $t): ?>
        <tr>
            <td><?php echo $t['AuditTrail']['AUD_DATE']; ?></td>
            <td><?php echo $t['AuditTrail']['AUD_CLIENT_IP']; ?></td>
        <?php switch ($t['AuditTrail']['AUD_ACTION']) {
              case 'AUTHENTICATION_SUCCESS': echo '<td class="login-success">登录成功</td>'; break;
              case 'AUTHENTICATION_FAILED': echo '<td class="login-failed">登录失败</td>'; break;
        } ?>
        </tr>
    <?php endforeach; ?>
    </table>
</div>

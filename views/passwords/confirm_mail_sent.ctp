<div id="column1" class="description">
<?php echo $this->element("languages/$locale/password_recovery_description"); ?>
</div>

<div id="column2" class="succeeded">
    <?php echo $this->Html->image('tick.jpg', array('alt' => __('成功', true), 'class' => 'tick')); ?>

    <?php
        if (!(empty($via)) && $via == 'mobile') {

            echo '<p>'.__('已经向您的密保手机发送了验证码，请查收。', true).'</p>';
            echo '<br />';
            echo '<p>'.$this->Html->link('输入验证码', '/passwords/recovery_confirm').'</p>';
        } else {
            echo '<p>'.__('已经向您的密保邮箱发送了确认信，请查收。', true).'</p>';
        }
    ?>
</div>

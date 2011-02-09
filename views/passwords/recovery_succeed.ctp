<div id="column1" class="description">
<?php echo $this->element("languages/$locale/password_recovery_description"); ?>
</div>

<div id="column2" class="succeeded">
    <?php echo $this->Html->image('tick.jpg', array('alt' => __('成功', true), 'class' => 'tick')); ?>

    <p><?php __('您的密码已经设置为：') ?></p>
    <p><code><?php echo $password; ?></code></p>
    <p><?php echo $this->Html->link('登录并修改密码', '/passwords/change'); ?></p>
</div>

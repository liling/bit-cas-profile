<div id="column1" class="description">
<?php echo $this->element("languages/$locale/password_recovery_description"); ?>
</div>

<div id="column2" class="succeeded">
    <?php echo $this->Html->image('tick.jpg', array('alt' => __('成功', true), 'class' => 'tick')); ?>

    <p><?php echo __('已经向您的密保邮箱发送了确认信，请查收。', true); ?></p>
</div>

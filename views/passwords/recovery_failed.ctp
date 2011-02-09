<div id="column1" class="description">
<?php echo $this->element("languages/$locale/password_recovery_description"); ?>
</div>

<div id="column2" class="failed">
    <?php echo $this->Html->image('cross.jpg', array('alt' => __('失败', true), 'class' => 'cross')); ?>

    <p><?php echo __('未能重置密码：', true).$reason; ?></p>
</div>

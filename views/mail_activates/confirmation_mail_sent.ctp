<div id="column3" class="succeeded">
    <?php echo $html->image('tick.jpg', array('alt' => __('成功', true), 'class' => 'tick')); ?>

    <p><?php __('系统已向您的邮箱发送邮件，请在三天内查收。'); ?></p>
    <p><?php echo $html->link('返回', '/users/view'); ?></p>
</div>

<div id="column4" class="description">
    <?php echo $this->element("languages/$locale/setmail_description"); ?>
</div>

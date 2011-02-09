<div id="column1" class="description">
<?php echo $this->element("languages/$locale/password_recovery_description"); ?>
</div>

<div id="column2">
    <?php
        $this->Js->get('div.box');
        $this->Js->each('$(this).corner();', true);
    ?>
    <div class="form box">
        <?php echo $form->create('Password'); ?>

        <div class="header">
            <?php echo __('请输入工号/学号和密保邮件地址'); ?>
        </div>

        <div class="item">
            <label><?php echo __('输入工号/学号:', true); ?></label>
            <?php echo $form->text('username', array('size' => 10, 'class' => 'username required')); ?>
            <p class="error"><?php if (!empty($errors['username'])) echo $errors['username']; ?></p>
        </div>

        <div class="item">
            <label><?php echo __('输入密保邮件地址:', true); ?></label>
            <?php echo $form->text('mail', array('size' => 30, 'class' => 'mail required')); ?>
            <p class="error"><?php if (!empty($errors['mail'])) echo $errors['mail']; ?></p>
        </div>

        <div class="footer">
            <?php echo $form->button(__('提交', true), array('type' => 'submit')); ?>
            <?php echo $form->button(__('清空', true), array('type' => 'button', 'id' => 'reset')); ?>
        </div>

        <?php echo $form->end(); ?>
    </div>
</div>

<div id="column1" class="description">
<?php echo $this->element("languages/$locale/password_recovery_description"); ?>
</div>

<div id="column2">
    <?php
        $this->Js->get('div.box');
        $this->Js->each('$(this).corner();', true);
    ?>
    <div class="form box">
        <?php
            echo $form->create('Password', array('action' => 'recovery_confirm', 'type' => 'get'));
            echo $form->button('输入确认码', array('type' => 'submit', 'style' => 'float: right'));
            echo $form->end();
        ?>

        <?php echo $form->create('Password'); ?>
        <div class="header">
            <?php echo $title_for_layout; ?>
        </div>

        <div class="item">
            <label><?php echo __('输入工号/学号:', true); ?></label>
            <?php echo $form->text('username', array('size' => 10, 'class' => 'username required')); ?>
            <p class="error"><?php if (!empty($errors['username'])) echo $errors['username']; ?></p>
        </div>

        <div class="item">
            <label><?php echo __('输入密保邮件地址或密保手机号码:', true); ?></label>
            <?php echo $form->text('mail', array('size' => 30, 'class' => 'mail required')); ?>
            <p class="error"><?php if (!empty($errors['mail'])) echo $errors['mail']; ?></p>
        </div>

        <?php
            $js->get('#captcha');
            $js->event('click', '$(this).attr("src", "'.$html->url('/users/captcha', true).'/" + Math.random());', true);
        ?>
        <div class="item">
            <?php echo $html->image('/users/captcha', array('id' => 'captcha', 'style' => 'float: right; border: 0;', 'title' => '若看不清可单击图片更换')); ?>
            <label><?php echo __('输入图片中的验证码:', true); ?></label>
            <?php echo $form->text('captcha', array('size' => 8, 'class' => 'captcha required', 'value' => '')); ?>
            <p class="error"><?php if (!empty($errors['captcha'])) echo $errors['captcha']; ?></p>
        </div>

        <div class="footer">
            <?php echo $form->button(__('提交', true), array('type' => 'submit')); ?>
        </div>

        <?php echo $form->end(); ?>
    </div>
</div>

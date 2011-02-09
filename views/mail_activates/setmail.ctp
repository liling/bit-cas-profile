<?php
    $this->Js->get('div.box');
    $this->Js->each('$(this).corner();', true);
?>
<div class="box form">
    <?php echo $form->create('MailActivate'); ?>
    <div class="header"><?php echo $title_for_layout; ?></div>

    <div class="item">
        <label for="mail"><?php __('输入邮件地址:') ?></label>
        <?php echo $form->text('mail', array('size' => 30, 'class' => 'mail required')); ?>
        <p class="error"><?php if (!empty($errors['mail'])) echo $errors['mail']; ?></p>
    </div>

    <div class="item">
        <label for="mail"><?php __('确认邮件地址:') ?></label>
        <?php echo $form->text('mail2', array('size' => 30, 'class' => 'mail required')); ?>
        <p class="error"><?php if (!empty($errors['mail2'])) echo $errors['mail2']; ?></p>
    </div>

    <div class="footer">
        <?php echo $form->button(__('提交', true), array('type' => 'submit')); ?>
    </div>

    <?php echo $form->end(); ?>
</div>

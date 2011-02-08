<h1><?php echo $title_for_layout; ?></h1>

<?php echo $form->create('MailActivate'); ?>

输入邮件地址: <?php echo $form->text('mail', array('size' => 30, 'class' => 'mail')); ?>
<p><?php if (!empty($errors['mail'])) echo $errors['mail']; ?></p>
确认邮件地址: <?php echo $form->text('mail2', array('size' => 30, 'class' => 'mail')); ?>
<p><?php if (!empty($errors['mail2'])) echo $errors['mail2']; ?></p>

<?php echo $form->button('Set Mail', array('type' => 'submit')); ?>

<?php echo $form->end(); ?>

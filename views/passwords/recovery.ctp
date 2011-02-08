<h1><?php echo $title_for_layout; ?></h1>

<?php echo $form->create('Password'); ?>

输入工号/学号: <?php echo $form->text('username', array('size' => 10, 'class' => 'username')); ?>
<p><?php if (!empty($errors['username'])) echo $errors['username']; ?></p>
输入邮件地址: <?php echo $form->text('mail', array('size' => 30, 'class' => 'mail')); ?>
<p><?php if (!empty($errors['mail'])) echo $errors['mail']; ?></p>

<?php echo $form->button('Send Recovery Mail', array('type' => 'submit')); ?>

<?php echo $form->end(); ?>

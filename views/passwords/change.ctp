<h1><?php echo $title_for_layout; ?></h1>

<?php echo $form->create('Password'); ?>

旧密码: <?php echo $form->password('old', array('size' => 30, 'class' => 'password')); ?>
<p><?php if (!empty($errors['old'])) echo $errors['old']; ?></p>

新密码: <?php echo $form->password('new', array('size' => 30, 'class' => 'password')); ?>
<p><?php if (!empty($errors['password'])) echo $errors['new']; ?></p>

新密码: <?php echo $form->password('new2', array('size' => 30, 'class' => 'password')); ?>
<p><?php if (!empty($errors['new2'])) echo $errors['new2']; ?></p>

<?php echo $form->button('修改密码', array('type' => 'submit')); ?>

<?php echo $form->end(); ?>


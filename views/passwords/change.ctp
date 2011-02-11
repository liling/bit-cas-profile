<?php
    $this->Js->get('div.box');
    $this->Js->each('$(this).corner();', true);
?>
<div id="column3">
    <div class="box form">
        <?php echo $form->create('Password'); ?>

        <div class="header">修改密码</div>

        <div class="item">
            <label>旧密码:</label>
             <?php echo $form->password('old', array('size' => 30, 'class' => 'password required')); ?>
            <p class="error"><?php if (!empty($errors['old'])) echo $errors['old']; ?></p>
        </div>

        <div class="item">
            <label>新密码（第一遍）:</label>
             <?php echo $form->password('new', array('size' => 30, 'class' => 'password required')); ?>
            <p class="error"><?php if (!empty($errors['password'])) echo $errors['new']; ?></p>
        </div>

        <div class="item">
            <label>新密码（第二遍）:</label>
            <?php echo $form->password('new2', array('size' => 30, 'class' => 'password required')); ?>
            <p class="error"><?php if (!empty($errors['new2'])) echo $errors['new2']; ?></p>
        </div>

        <div class="footer">
            <?php echo $form->button('修改密码', array('type' => 'submit')); ?>
        </div>

        <?php echo $form->end(); ?>
    </div>
</div>

<div id="column4" class="description">
    <?php echo $this->element("languages/$locale/password_change_description"); ?>
</div>

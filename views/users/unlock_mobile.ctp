<?php
    $this->Js->get('div.box');
    $this->Js->each('$(this).corner();', true);
?>
<div class="box form">
    <div class="header"><?php echo $title_for_layout; ?></div>

    <?php echo $form->create('User'); ?>
    <div class="item">

        <p>您预留的问题是：<?php echo $user['User']['unlock_question']; ?></p>

        <label for="unlock_answer"><?php __(' 请输入答案:') ?></label>
        <?php echo $form->text('unlock_answer', array('class' => 'unlock_answer required')); ?>
        <p class="error"><?php if (!empty($errors['unlock_answer'])) echo $errors['unlock_answer']; ?></p>

        <?php echo $form->button(__('提交', true), array('type' => 'submit')); ?>
    </div>
    <?php echo $form->end(); ?>
</div>

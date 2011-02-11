<?php
    $this->Js->get('div.box');
    $this->Js->each('$(this).corner();', true);
?>
<div class="box form">
    <?php if (empty($this->data['MobileActivate']['id'])): ?>
    <div class="header"><?php echo $title_for_layout; ?> - <?php __('输入手机号码'); ?></div>

    <?php echo $form->create('MobileActivate', array('action' => 'setmobile')); ?>
    <div class="item">
        <label for="mobile"><?php __('输入手机号码:') ?></label>
        <?php echo $form->text('mobile', array('maxlength' => 11, 'class' => 'mobile required')); ?>
        <p class="error"><?php if (!empty($errors['mobile'])) echo $errors['mobile']; ?></p>

        <label for="unlock_question"><?php __('解锁问题:') ?></label>
        <?php echo $form->select('unlock_question', $unlock_questions, null, array('class' => 'unlock_question required')); ?>
        <p class="error"><?php if (!empty($errors['unlock_question'])) echo $errors['unlock_question']; ?></p>

        <label for="unlock_answer"><?php __('解锁答案:') ?></label>
        <?php echo $form->text('unlock_answer', array('class' => 'unlock_answer required')); ?>
        <p class="error"><?php if (!empty($errors['unlock_answer'])) echo $errors['unlock_answer']; ?></p>

        <?php echo $form->button(__('发送验证码', true), array('type' => 'submit')); ?>
    </div>
    <?php echo $form->end(); ?>

    <?php else: ?>
    <?php echo $form->create('MobileActivate', array('action' => 'clear')); ?>
        <?php echo $form->button(__('重新设定密保手机号码', true), array('type' => 'submit', 'style' => 'float: right')); ?>
    <?php echo $form->end(); ?>

    <div class="header"><?php echo $title_for_layout; ?> - <?php __('输入验证码'); ?></div>

    <?php echo $form->create('MobileActivate', array('action' => 'resend')); ?>
        <div class="item">
            <label for="mobile"><?php __('手机号码:') ?></label>
            <?php echo $form->text('mobile', array('maxlength' => 11, 'class' => 'mobile required', 'disabled' => true)); ?>
            <p class="error"><?php if (!empty($errors['mobile'])) echo $errors['mobile']; ?></p>
        </div>
        <?php echo $form->button(__('重新发送验证码', true), array('type' => 'submit', 'disabled' => !empty($no_more_send))); ?>
    <?php echo $form->end(); ?>

    <?php echo $form->create('MobileActivate', array('action' => 'confirm')); ?>
    <div class="item">
        <label for="code"><?php __('输入验证码:') ?></label>
        <?php echo $form->text('checkcode', array('maxlength' => 6, 'class' => 'code required', 'disabled' => !empty($no_more_check))); ?>
        <p class="error"><?php if (!empty($errors['checkcode'])) echo $errors['checkcode']; ?></p>
        <?php echo $form->button(__('确认', true), array('type' => 'submit', 'disabled' => !empty($no_more_check))); ?>
    </div>
    <?php echo $form->end(); ?>
    <?php endif; ?>
</div>

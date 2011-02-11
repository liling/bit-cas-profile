<div id="column3">
    <?php
        $this->Js->get('div.box');
        $this->Js->each('$(this).corner();', true);
    ?>
    <div class="box form">
        <div class="header"><?php echo $title_for_layout; ?></div>

        <?php echo $form->create('User'); ?>
        <div class="item">

            <p>您预留的问题是：<?php echo $user['User']['unlock_question']; ?></p>

            <div class="item">
                <label for="unlock_answer"><?php __(' 请输入答案:') ?></label>
                <?php echo $form->text('unlock_answer', array('class' => 'unlock_answer required')); ?>
                <p class="error"><?php if (!empty($errors['unlock_answer'])) echo $errors['unlock_answer']; ?></p>
            </div>

            <div class="item">
                <?php echo $html->image('/users/captcha', array('style' => 'float: right; border: 0;')); ?>
                <label><?php echo __('输入图片中的验证码:', true); ?></label>
                <?php echo $form->text('captcha', array('class' => 'captcha required', 'value' => '')); ?>
                <p class="error"><?php if (!empty($errors['captcha'])) echo $errors['captcha']; ?></p>
            </div>

            <?php echo $form->button(__('提交', true), array('type' => 'submit')); ?>
        </div>
        <?php echo $form->end(); ?>
    </div>
</div>

<div id="column4" class="description">
    <?php echo $this->element("languages/$locale/unlock_mobile_description"); ?>
</div>

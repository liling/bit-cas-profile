<div id="column1" class="description">
<?php echo $this->element("languages/$locale/password_recovery_description"); ?>
</div>

<div id="column2">
    <?php
        $this->Js->get('div.box');
        $this->Js->each('$(this).corner();', true);
    ?>
    <div class="form box">
        <?php echo $form->create('Password', array('type' => 'get', 'action' => 'recovery_confirm')); ?>

        <div class="header">
            <?php echo $title_for_layout; ?>
        </div>

        <div class="item">
            <label for="code"><?php __('请输入确认码'); ?></label>
            <?php echo $form->text('code', array('maxlength' => 16, 'class' => 'code required')); ?>
            <p class="error"><?php if (!empty($errors['code'])) echo $errors['code']; ?></p>
        </div>

        <div class="footer">
            <?php echo $form->button(__('提交', true), array('type' => 'submit')); ?>
        </div>

        <?php echo $form->end(); ?>
    </div>
</div>

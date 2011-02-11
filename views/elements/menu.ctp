<ul class="menu">
    <?php if (empty($user)): ?>
    <li><?php echo $this->Html->link('登录', '/users/login'); ?></li>
    <?php else: ?>
    <li><?php echo $this->Html->link('个人信息', '/users/view'); ?></li>
    <li><?php echo $this->Html->link('修改密码', '/passwords/change'); ?></li>
    <li><?php echo $this->Html->link('退出', '/users/logout'); ?></li>
    <?php endif; ?>
</ul>

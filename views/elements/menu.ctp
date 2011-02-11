<ul class="menu">
    <li><?php if (!$user) echo $this->Html->link('登录', '/users/login'); ?></li>
    <li><?php echo $this->Html->link('个人信息', '/users/view'); ?></li>
    <li><?php echo $this->Html->link('修改密码', '/passwords/change'); ?></li>
    <li><?php if ($user) echo $this->Html->link('退出', '/users/logout'); ?></li>
</ul>

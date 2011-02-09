<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo __('校园网单点登录服务', true).' - '.$title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('default');

        echo $this->Html->script('jquery.min');
        echo $this->Html->script('jquery.corner');

		echo $scripts_for_layout;
	?>
</head>
<body>
	<div id="container">
		<div id="header">
			<div id="header-wrapper">
                <h1><?php echo __('校园网单点登录服务', true).' - '.$title_for_layout; ?></h1>
                <?php echo $this->element('menu'); ?>
            </div>
            <hr />
		</div>
		<div id="content">

			<?php echo $this->Session->flash(); ?>

			<?php echo $content_for_layout; ?>

            <hr />
		</div>
		<div id="footer">
            <p><?php echo $this->Html->link('北京理工大学网络服务中心', 'http://nsc.bit.edu.cn', array('target' => '_blank', 'escape' => false)); ?> 地址：计算中心楼三层 七号教学楼五层</p>

            <p>联系邮箱：service@bit.edu.cn</p>
		</div>
	</div>
    <?php echo $this->Js->writeBuffer(); ?>
	<?php echo $this->element('sql_dump'); ?>
</body>
</html>

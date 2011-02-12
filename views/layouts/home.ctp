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
        echo $this->Html->css('largebutton');

        echo $this->Html->script('jquery.min');
        echo $this->Html->script('jquery.corner');

		echo $scripts_for_layout;
	?>
    <style>
        #header-wrapper {
            background: url(<?php echo $this->Html->url('/img/dove.png', true); ?>) no-repeat scroll 97% -30% #9BC11B;
        }
        /* rotator in-page placement */
        div.rotator {
            position:relative;
            height: 240px;
        }
        /* rotator css */
        div.rotator ul {
            padding: 0;
            margin: 0 auto;
        }
        div.rotator ul li {
            float:left;
            position:absolute;
            list-style: none;
        }
        /* rotator image style */   
        div.rotator ul li img {
            border:1px solid #ccc;
            padding: 4px;
            background: #FFF;
            width: 760px;
        }
        div.rotator ul li.show {
            z-index:500;
        }

        .description ul {
            color: #ff2200;
        }
        .description ul p {
            color: #333;
        }

        .button {
            border: 1px solid #333;
            padding: 4px 30px;
            background-color: #ff2200;
        }
    </style>
    <script language="JavaScript">

function theRotator() {
    //Set the opacity of all images to 0
    $('div.rotator ul li').css({opacity: 0.0});
    
    //Get the first image and display it (gets set to full opacity)
    $('div.rotator ul li:first').css({opacity: 1.0});
        
    //Call the rotator function to run the slideshow, 6000 = change to next image after 6 seconds
    
    setInterval('rotate()',6000);
    
}

function rotate() { 
    //Get the first image
    var current = ($('div.rotator ul li.show')?  $('div.rotator ul li.show') : $('div.rotator ul li:first'));

    if ( current.length == 0 ) current = $('div.rotator ul li:first');

    //Get next image, when it reaches the end, rotate it back to the first image
    var next = ((current.next().length) ? ((current.next().hasClass('show')) ? $('div.rotator ul li:first') :current.next()) : $('div.rotator ul li:first'));
    
    //Un-comment the 3 lines below to get the images in random order
    
    //var sibs = current.siblings();
        //var rndNum = Math.floor(Math.random() * sibs.length );
        //var next = $( sibs[ rndNum ] );
            

    //Set the fade in effect for the next image, the show class has higher z-index
    next.css({opacity: 0.0})
    .addClass('show')
    .animate({opacity: 1.0}, 1000);

    //Hide the current image
    current.animate({opacity: 0.0}, 1000)
    .removeClass('show');
    
};

$(document).ready(function() {      
    //Load the slideshow
    theRotator();
    $('div.rotator').fadeIn(1000);
    $('div.rotator ul li').fadeIn(1000); // tweek for IE
});
    </script>
</head>
<body>
	<div id="container">
		<div id="header">
			<div id="header-wrapper">
                <?php echo $this->Html->image('/img/logo.png'); ?>
                <?php echo $this->element('menu'); ?>
            </div>
            <hr class="clear-both" />
		</div>
		<div id="content">

			<?php echo $this->Session->flash(); ?>

			<?php echo $content_for_layout; ?>

            <hr class="clear-both"/>
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

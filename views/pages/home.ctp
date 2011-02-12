<div class="rotator">
    <ul>
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <li>
            <a href="javascript:void(0)">
                <?php echo $html->image("/img/promo/promo_p0$i.jpg"); ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</div>

<div id="column1" class="description">
    <p><strong>利用单点登录服务，您可以：</strong></p>
    <ul>
        <li><p>使用一套用户名密码，便登录校园网上各种网站；</p></li>
        <li><p>每天打开浏览器，密码只需输入一次；</p></li>
        <li><p>密码完全在加密协议中传输，即便用无线网络也不用担心密码被盗；</p></li>
        <li><p>忘记了密码，也可以通过电子邮件或手机短信重新设置；</p></li>
        <li><p>新生、新教工帐号自动生效。</p></li>
    </ul>
    <p><strong>如果您是网站开发者，可以将您的网站与单点登录服务整合：</strong></p>
    <ul>
        <li><p>所有学生、教工均可直接登录您的网站；</p></li>
        <li><p>无需为新生、新教工创建帐号、发放密码；</p></li>
        <li><p>无须担心用户密码丢失，省去为用户重设密码的麻烦。</p></li>
    </ul>
</div>

<div id="column2">
    <?php if (empty($user)): ?>
    <div style="margin: 10px auto; width:200px;">
        <a href="<?php echo $this->Html->url('/users/login'); ?>" class="largebutton"><span>现在就登录</span></a>
    </div>
    <?php endif; ?>
</div>

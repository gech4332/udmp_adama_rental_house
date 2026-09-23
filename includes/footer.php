<style>
    #site-footer{background:#0b1420;color:rgba(255,255,255,.66);font-family:'Inter',system-ui,sans-serif;position:relative;overflow:hidden}
    #site-footer::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#0d9488,#2dd4bf 50%,#0d9488)}
    #site-footer::after{content:'';position:absolute;bottom:-160px;right:-120px;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(13,148,136,.18),transparent 62%);pointer-events:none}
    .sf-grid{max-width:1180px;margin:0 auto;padding:64px 32px 48px;display:grid;grid-template-columns:1.8fr 1fr 1fr 1.3fr;gap:48px;position:relative;z-index:1}
    .sf-brand .sf-logo{display:inline-flex;align-items:center;gap:11px;text-decoration:none;margin-bottom:20px}
    .sf-logo .sf-logo-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#0d9488,#14b8a6);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:#fff;box-shadow:0 6px 16px rgba(13,148,136,.4)}
    .sf-logo .sf-logo-text{font-size:20px;font-weight:800;color:#f1f5f9;letter-spacing:-.4px}
    .sf-logo .sf-logo-text span{color:#2dd4bf}
    .sf-brand>p{font-size:13.5px;line-height:1.85;color:rgba(255,255,255,.62);max-width:320px;margin-bottom:24px}
    #site-footer .sf-social{display:flex;gap:10px;flex-wrap:wrap}
    #site-footer .sf-social a{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:15px;border:1px solid rgba(255,255,255,.1);transition:all .25s;}
    #site-footer .sf-social a.tg{background:rgba(0,136,204,.16);color:#38bdf8}
    #site-footer .sf-social a.tg:hover{background:#0088cc;color:#fff;border-color:#0088cc;transform:translateY(-3px);box-shadow:0 8px 18px rgba(0,136,204,.35)}
    #site-footer .sf-social a.fb{background:rgba(24,119,242,.16);color:#6fb1ff}
    #site-footer .sf-social a.fb:hover{background:#1877f2;color:#fff;border-color:#1877f2;transform:translateY(-3px);box-shadow:0 8px 18px rgba(24,119,242,.35)}
    #site-footer .sf-social a.ig{background:rgba(225,48,108,.16);color:#ff7cb4}
    #site-footer .sf-social a.ig:hover{background:#e1306c;color:#fff;border-color:#e1306c;transform:translateY(-3px);box-shadow:0 8px 18px rgba(225,48,108,.35)}
    #site-footer .sf-social a.em{background:rgba(234,67,53,.16);color:#ff8a80}
    #site-footer .sf-social a.em:hover{background:#ea4335;color:#fff;border-color:#ea4335;transform:translateY(-3px);box-shadow:0 8px 18px rgba(234,67,53,.35)}
    .sf-col h4{position:relative;color:#f8fafc;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:22px;padding-bottom:12px}
    .sf-col h4::after{content:'';position:absolute;left:0;bottom:0;width:30px;height:2px;border-radius:2px;background:linear-gradient(90deg,#0d9488,#2dd4bf)}
    #site-footer .sf-col a{display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.62);text-decoration:none;font-size:13.5px;padding:5px 0;transition:color .2s,transform .2s}
    #site-footer .sf-col a i{font-size:9px;color:#2dd4bf;opacity:0;transform:translateX(-6px);transition:all .2s}
    #site-footer .sf-col a:hover{color:#fff;transform:translateX(4px)}
    #site-footer .sf-col a:hover i{opacity:1;transform:translateX(0)}
    #site-footer .sf-col .sf-row{display:flex;align-items:flex-start;gap:12px;padding:6px 0;font-size:13.5px;color:rgba(255,255,255,.62)}
    #site-footer .sf-col .sf-row .sf-row-icon{width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:rgba(13,148,136,.14);border:1px solid rgba(45,212,191,.2);color:#2dd4bf;font-size:13px}
    #site-footer .sf-col .sf-row strong{display:block;color:#e2e8f0;font-size:13px;font-weight:600;line-height:1.4}
    #site-footer .sf-col .sf-row span{font-size:12.5px;color:rgba(255,255,255,.55);line-height:1.5}
    .sf-bottom{max-width:1180px;margin:0 auto;padding:22px 32px 30px;border-top:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;font-size:12.5px;color:rgba(255,255,255,.45);position:relative;z-index:1}
    .sf-bottom .sf-copy{display:inline-flex;align-items:center;gap:8px}
    .sf-bottom .sf-copy .dot{width:5px;height:5px;border-radius:50%;background:#2dd4bf;box-shadow:0 0 8px rgba(45,212,191,.8)}
    .sf-bottom .sf-links{display:flex;gap:22px}
    .sf-bottom .sf-links a{color:rgba(255,255,255,.55);text-decoration:none;transition:color .2s}
    .sf-bottom .sf-links a:hover{color:#2dd4bf}
    .sf-bottom .sf-links a+a{position:relative}
    .sf-bottom .sf-links a+a::before{content:'';position:absolute;left:-12px;top:50%;transform:translateY(-50%);width:1px;height:12px;background:rgba(255,255,255,.15)}
    @media(max-width:900px){.sf-grid{grid-template-columns:1fr 1fr;gap:36px}}
    @media(max-width:560px){.sf-grid{grid-template-columns:1fr;gap:32px;padding:48px 22px 36px}.sf-bottom{flex-direction:column;align-items:flex-start;padding:20px 22px 26px}}
</style>
<footer id="site-footer">
    <div class="sf-grid" id="contact">
        <div class="sf-brand">
            <a href="index.php" class="sf-logo">
                <span class="sf-logo-icon">AR</span>
                <span class="sf-logo-text">Adama<span>Rent</span></span>
            </a>
            <p><?php echo t('footer_tagline'); ?></p>
            <div class="sf-social">
                <a href="https://t.me/Get_dope" target="_blank" rel="noopener" class="tg" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                <a href="https://facebook.com/Getdope" target="_blank" rel="noopener" class="fb" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com/get_dope_men" target="_blank" rel="noopener" class="ig" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="mailto:getahun930@gmail.com" class="em" aria-label="Email"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
        <div class="sf-col">
            <h4><?php echo t('footer_quick'); ?></h4>
            <a href="index.php"><i class="fas fa-chevron-right"></i> <?php echo t('footer_browse'); ?></a>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'post_house.php' : 'register.php'; ?>"><i class="fas fa-chevron-right"></i> <?php echo t('btn_list_property'); ?></a>
            <a href="login.php"><i class="fas fa-chevron-right"></i> <?php echo t('footer_signin'); ?></a>
        </div>
        <div class="sf-col">
            <h4><?php echo t('footer_categories'); ?></h4>
            <a href="index.php?cat=Single+Home"><i class="fas fa-chevron-right"></i> <?php echo t('cat_single'); ?></a>
            <a href="index.php?cat=Apartment"><i class="fas fa-chevron-right"></i> <?php echo t('footer_apartments'); ?></a>
            <a href="index.php?cat=Villa"><i class="fas fa-chevron-right"></i> <?php echo t('footer_villas'); ?></a>
            <a href="index.php?cat=Shop"><i class="fas fa-chevron-right"></i> <?php echo t('footer_shops'); ?></a>
        </div>
        <div class="sf-col">
            <h4><?php echo t('footer_contact'); ?></h4>
            <div class="sf-row">
                <span class="sf-row-icon"><i class="fas fa-location-dot"></i></span>
                <div><strong><?php echo t('footer_adama'); ?></strong><span><?php echo t('footer_ethio'); ?></span></div>
            </div>
            <div class="sf-row">
                <span class="sf-row-icon"><i class="fas fa-phone"></i></span>
                <div><strong>+251 910 407 721</strong><span><?php echo t('footer_hours'); ?></span></div>
            </div>
            <div class="sf-row">
                <span class="sf-row-icon"><i class="fas fa-envelope"></i></span>
                <div><strong>getahun930@gmail.com</strong><span><?php echo t('footer_reply'); ?></span></div>
            </div>
        </div>
    </div>
    <div class="sf-bottom">
        <span class="sf-copy"><span class="dot"></span> &copy; <?php echo date('Y'); ?> AdamaRent. <?php echo t('footer_rights'); ?></span>
        <div class="sf-links">
            <a href="terms.php"><?php echo t('footer_terms'); ?></a>
            <a href="privacy.php"><?php echo t('footer_privacy'); ?></a>
        </div>
    </div>
</footer>
<?php
if(!defined('ABSPATH'))exit;
global $wpdb;
$t=$wpdb->prefix.'support_cards';
$cs=$wpdb->get_results("SELECT * FROM $t ORDER BY display_order,id DESC");
$a=json_decode(get_option('sjg_ads','{}'),1);
$ts=json_decode(get_option('sjg_tabs','[]'),1);
$title=get_option('sjg_title','지원금 스킨');
$url=get_option('sjg_url',home_url());
?>
<div class="sjg-wrap">
<header class="sjg-head">
    <div class="sjg-con">
        <?php if(has_custom_logo())the_custom_logo();?>
        <h1 class="sjg-logo"><?=$title?></h1>
    </div>
</header>

<?php if($ts):?>
<div class="sjg-tab-wrap">
    <div class="sjg-con">
        <nav class="sjg-tab-con">
            <ul class="sjg-tabs">
                <?php foreach($ts as $tab):?>
                <li class="sjg-tab-item">
                    <a class="sjg-tab-link <?=$tab['active']?'active':''?>" href="<?=$tab['url']?:$url?>"><?=$tab['name']?></a>
                </li>
                <?php endforeach;?>
            </ul>
        </nav>
    </div>
</div>
<?php endif;?>

<div class="sjg-con sjg-main">
<div class="sjg-exit-pop" id="sjgPop">
    <div class="sjg-pop-box">
        <div class="sjg-pop-tit">🎁 잠깐! 놓치신 혜택이 있어요</div>
        <div class="sjg-pop-desc">지금 확인 안 하면<br><strong>최대 300만원</strong> 못받아요!</div>
        <button class="sjg-pop-btn" onclick="sjgClose()">내 지원금 확인 →</button>
        <button class="sjg-pop-no" onclick="sjgNo()">다음에</button>
    </div>
</div>

<div class="sjg-intro">
    <span class="sjg-badge">신청마감 D-3일</span>
    <p class="sjg-sub">숨은 보험금 1분만에 찾기!</p>
    <h2 class="sjg-tit">숨은 지원금 찾기</h2>
</div>

<?php if($a['display']??''):?><div class="sjg-ad"><?=$a['display']?></div><?php endif;?>

<div class="sjg-info">
    <div class="sjg-info-head">
        <span>🏷️</span>
        <span class="sjg-info-tit">신청 안하면 절대 못받아요</span>
    </div>
    <div class="sjg-info-amt">1인 평균 127만원 환급</div>
    <p>대한민국 92%가 놓치는 정부지원금! 지금 확인하세요.</p>
</div>

<div class="sjg-grid">
<?php if($cs):foreach($cs as $i=>$c):?>
    <?php if(($a['display']??'')&&in_array($i,[0,3,6])):?>
    <div class="sjg-ad-card"><div><?=$a['display']?></div></div>
    <?php endif;?>
    
    <a class="sjg-card <?=$c->is_featured?'feat':''?>" href="<?=$c->link_url?>">
        <div class="sjg-high">
            <?php if($c->is_featured):?><span class="sjg-feat">🔥 인기</span><?php endif;?>
            <div class="sjg-amt"><?=$c->amount?></div>
            <div class="sjg-amt-sub"><?=$c->amount_sub?></div>
        </div>
        <div class="sjg-body">
            <h3 class="sjg-card-tit"><?=$c->keyword?></h3>
            <p class="sjg-desc"><?=$c->description?></p>
            <div class="sjg-det">
                <div class="sjg-row">
                    <span class="sjg-lab">지원대상</span>
                    <span class="sjg-val"><?=$c->target?></span>
                </div>
                <div class="sjg-row">
                    <span class="sjg-lab">신청시기</span>
                    <span class="sjg-val"><?=$c->period?></span>
                </div>
            </div>
            <div class="sjg-btn">지금 바로 신청하기 <span>→</span></div>
        </div>
    </a>
<?php endforeach;endif;?>
</div>

<div class="sjg-hero">
    <div class="sjg-hero-con">
        <span class="sjg-urgent">🔥 신청마감 D-3일</span>
        <p class="sjg-hero-sub">숨은 지원금 1분만에 찾기!</p>
        <h2 class="sjg-hero-tit">나의 <span>숨은 지원금</span> 찾기</h2>
        <p class="sjg-hero-amt">신청자 <strong>1인 평균 127만원</strong> 수령</p>
        <a class="sjg-cta" href="<?=$url?>">30초만에 확인 <span>→</span></a>
        <div class="sjg-trust">
            <span>✓ 무료</span>
            <span>✓ 30초</span>
            <span>✓ 개인정보보호</span>
        </div>
        <div class="sjg-notice">
            <div class="sjg-notice-tit">💡신청 안하면 못받아요</div>
            <p>대한민국 92%가 놓치는 지원금, 지금 확인하세요!</p>
        </div>
    </div>
</div>

<?php if($a['multi']??''):?><div class="sjg-ad"><?=$a['multi']?></div><?php endif;?>
</div>
</div>

<?php if($a['anchor']??''):?><div class="sjg-anchor"><?=$a['anchor']?></div><?php endif;?>
<?php if($a['full']??''):?><div class="sjg-full" style="display:none"><?=$a['full']?></div><?php endif;?>

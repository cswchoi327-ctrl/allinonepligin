<?php
/*
Plugin Name: 지원금 올인원
Description: 키워드만 입력하면 자동 생성되는 지원금 카드 + 광고 관리
Version: 1.0
Author: 아로스
*/

if(!defined('ABSPATH'))exit;

register_activation_hook(__FILE__,'sjg_install');
function sjg_install(){
    global $wpdb;
    $t=$wpdb->prefix.'support_cards';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $t(
        id INT AUTO_INCREMENT PRIMARY KEY,
        keyword VARCHAR(200),
        amount VARCHAR(200),
        amount_sub VARCHAR(200),
        description TEXT,
        target VARCHAR(100),
        period VARCHAR(100),
        link_url VARCHAR(500),
        is_featured TINYINT DEFAULT 0,
        display_order INT DEFAULT 0
    )");
    add_option('sjg_title','지원금 스킨');
    add_option('sjg_url',home_url());
    add_option('sjg_tabs',json_encode([['name'=>'전체','url'=>'','active'=>1]]));
    add_option('sjg_ads',json_encode(['display'=>'','anchor'=>'','full'=>'','multi'=>'']));
}

add_action('admin_menu','sjg_menu');
function sjg_menu(){
    add_menu_page('지원금','지원금','manage_options','sjg-list','sjg_list','dashicons-money-alt');
    add_submenu_page('sjg-list','추가','추가','manage_options','sjg-add','sjg_add');
    add_submenu_page('sjg-list','광고','광고','manage_options','sjg-ads','sjg_ads');
    add_submenu_page('sjg-list','설정','설정','manage_options','sjg-set','sjg_set');
}

function sjg_list(){
    global $wpdb;
    $t=$wpdb->prefix.'support_cards';
    if(isset($_GET['del'])){
        $wpdb->delete($t,['id'=>intval($_GET['del'])]);
        echo'<div class="updated"><p>삭제됨</p></div>';
    }
    $cs=$wpdb->get_results("SELECT * FROM $t ORDER BY display_order,id DESC");
    ?>
    <div class="wrap">
        <h1>지원금 카드 <a href="?page=sjg-add" class="button">추가</a></h1>
        <table class="wp-list-table widefat">
            <tr><th>순서</th><th>키워드</th><th>금액</th><th>대상</th><th>작업</th></tr>
            <?php foreach($cs as $c):?>
            <tr>
                <td><?=$c->display_order?></td>
                <td><b><?=$c->keyword?></b><?=$c->is_featured?' 🔥':''?></td>
                <td><?=$c->amount?></td>
                <td><?=$c->target?></td>
                <td><a href="?page=sjg-add&edit=<?=$c->id?>">수정</a> | <a href="?page=sjg-list&del=<?=$c->id?>" onclick="return confirm('삭제?')">삭제</a></td>
            </tr>
            <?php endforeach;?>
        </table>
    </div>
    <?php
}

function sjg_add(){
    global $wpdb;
    $t=$wpdb->prefix.'support_cards';
    $c=null;
    if(isset($_GET['edit']))$c=$wpdb->get_row("SELECT * FROM $t WHERE id=".intval($_GET['edit']));
    
    if($_POST){
        $k=sanitize_text_field($_POST['keyword']);
        if(empty($_POST['amount'])){
            $g=sjg_gen($k);
            $d=['keyword'=>$k,'amount'=>$g['amount'],'amount_sub'=>$g['amount_sub'],'description'=>$g['description'],'target'=>$g['target'],'period'=>$g['period'],'link_url'=>$_POST['link_url']?:get_option('sjg_url'),'is_featured'=>$_POST['is_featured']?1:0,'display_order'=>intval($_POST['order'])];
        }else{
            $d=['keyword'=>$k,'amount'=>$_POST['amount'],'amount_sub'=>$_POST['amount_sub'],'description'=>$_POST['description'],'target'=>$_POST['target'],'period'=>$_POST['period'],'link_url'=>$_POST['link_url'],'is_featured'=>$_POST['is_featured']?1:0,'display_order'=>intval($_POST['order'])];
        }
        if($c)$wpdb->update($t,$d,['id'=>$c->id]);
        else $wpdb->insert($t,$d);
        echo'<div class="updated"><p>저장됨 <a href="?page=sjg-list">목록</a></p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?=$c?'수정':'추가'?></h1>
        <form method="post">
            <table class="form-table">
                <tr><th>키워드*</th><td><input type="text" name="keyword" class="regular-text" required value="<?=$c?$c->keyword:''?>"><p class="description">키워드만 입력하면 자동생성!</p></td></tr>
                <tr><th>금액</th><td><input type="text" name="amount" class="regular-text" value="<?=$c?$c->amount:''?>" placeholder="비우면 자동"></td></tr>
                <tr><th>부가설명</th><td><input type="text" name="amount_sub" class="regular-text" value="<?=$c?$c->amount_sub:''?>"></td></tr>
                <tr><th>설명</th><td><textarea name="description" class="large-text"><?=$c?$c->description:''?></textarea></td></tr>
                <tr><th>대상</th><td><input type="text" name="target" class="regular-text" value="<?=$c?$c->target:''?>" maxlength="20"><p class="description">20자 이내</p></td></tr>
                <tr><th>시기</th><td><input type="text" name="period" class="regular-text" value="<?=$c?$c->period:''?>"></td></tr>
                <tr><th>URL</th><td><input type="url" name="link_url" class="regular-text" value="<?=$c?$c->link_url:get_option('sjg_url')?>"></td></tr>
                <tr><th>순서</th><td><input type="number" name="order" class="small-text" value="<?=$c?$c->display_order:0?>"></td></tr>
                <tr><th>인기</th><td><input type="checkbox" name="is_featured" value="1" <?=$c&&$c->is_featured?'checked':''?>> 인기카드(🔥)</td></tr>
            </table>
            <p><input type="submit" class="button-primary" value="저장"> <a href="?page=sjg-list" class="button">취소</a></p>
        </form>
    </div>
    <?php
}

function sjg_gen($k){
    $db=['청년내일채움공제'=>['amount'=>'최대 1200만원','amount_sub'=>'2년 근속시','description'=>'중소기업 청년 장기근속 지원 공제','target'=>'만 15~34세 청년','period'=>'상시'],
    '청년도약계좌'=>['amount'=>'최대 5000만원','amount_sub'=>'5년 만기시','description'=>'청년 자산형성 지원 금융상품','target'=>'만 19~34세 청년','period'=>'상시'],
    '청년월세지원'=>['amount'=>'월 20만원','amount_sub'=>'최대 12개월','description'=>'무주택 청년 주거비 지원','target'=>'만 19~34세 무주택청년','period'=>'상시'],
    '신혼부부전세자금'=>['amount'=>'최대 3억원','amount_sub'=>'연 1%대 금리','description'=>'신혼부부 저금리 전세자금 대출','target'=>'혼인 7년 이내','period'=>'상시'],
    '첫만남이용권'=>['amount'=>'200만원','amount_sub'=>'1회 지급','description'=>'출생아동 양육비 바우처','target'=>'2022년 이후 출생아','period'=>'출생후 60일'],
    '국민취업지원'=>['amount'=>'월 50만원','amount_sub'=>'최대 6개월','description'=>'저소득 구직자 생계비 지원','target'=>'15~69세 구직자','period'=>'상시'],
    '청년창업지원'=>['amount'=>'최대 1억원','amount_sub'=>'무이자/저금리','description'=>'청년 창업 초기자금 지원','target'=>'만 39세 이하','period'=>'분기별'],
    '근로장려금'=>['amount'=>'최대 330만원','amount_sub'=>'연 1회','description'=>'저소득 근로자 소득지원','target'=>'총소득 4000만원 미만','period'=>'매년 5월'],
    '자녀장려금'=>['amount'=>'최대 100만원','amount_sub'=>'자녀 1인당','description'=>'저소득 가구 양육비 지원','target'=>'부양자녀 있는 가구','period'=>'매년 5월'],
    '기초생활수급'=>['amount'=>'월 62만원','amount_sub'=>'생계급여 기준','description'=>'최저생활 보장 급여','target'=>'중위소득 30~50%','period'=>'상시'],
    '노인일자리'=>['amount'=>'월 27만원','amount_sub'=>'월 30시간','description'=>'어르신 일자리 제공','target'=>'만 65세 이상','period'=>'매년 1월']];
    
    if(isset($db[$k]))return $db[$k];
    foreach($db as $key=>$v)if(strpos($key,$k)!==false||strpos($k,$key)!==false)return $v;
    
    $r=['amount'=>'최대 300만원','amount_sub'=>'조건충족시','description'=>$k.' 지원혜택','target'=>'조건충족자','period'=>'상시'];
    if(strpos($k,'청년')!==false)$r['target']='만 19~34세 청년';
    if(strpos($k,'신혼')!==false)$r['target']='혼인 7년 이내';
    if(strpos($k,'노인')!==false)$r['target']='만 65세 이상';
    if(strpos($k,'창업')!==false){$r['amount']='최대 5000만원';$r['amount_sub']='무이자';}
    return $r;
}

function sjg_ads(){
    if($_POST){
        update_option('sjg_ads',json_encode(['display'=>$_POST['ad_d'],'anchor'=>$_POST['ad_a'],'full'=>$_POST['ad_f'],'multi'=>$_POST['ad_m']]));
        echo'<div class="updated"><p>저장됨</p></div>';
    }
    $a=json_decode(get_option('sjg_ads','{}'),1);
    ?>
    <div class="wrap">
        <h1>광고 설정</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>디스플레이</th><td><textarea name="ad_d" rows="5" class="large-text code"><?=$a['display']??''?></textarea><p>카드 1,4,7번 전</p></td></tr>
                <tr><th>앵커</th><td><textarea name="ad_a" rows="5" class="large-text code"><?=$a['anchor']??''?></textarea><p>하단고정</p></td></tr>
                <tr><th>전면</th><td><textarea name="ad_f" rows="5" class="large-text code"><?=$a['full']??''?></textarea><p>3초후 표시</p></td></tr>
                <tr><th>멀티플렉스</th><td><textarea name="ad_m" rows="5" class="large-text code"><?=$a['multi']??''?></textarea><p>하단 추천</p></td></tr>
            </table>
            <p><input type="submit" class="button-primary" value="저장"></p>
        </form>
    </div>
    <?php
}

function sjg_set(){
    if($_POST){
        update_option('sjg_title',$_POST['title']);
        update_option('sjg_url',$_POST['url']);
        $ts=[];
        for($i=0;$i<3;$i++)if($_POST['tn'][$i])$ts[]=['name'=>$_POST['tn'][$i],'url'=>$_POST['tu'][$i],'active'=>$_POST['ta']==$i];
        update_option('sjg_tabs',json_encode($ts));
        echo'<div class="updated"><p>저장됨</p></div>';
    }
    $ts=json_decode(get_option('sjg_tabs','[]'),1);
    ?>
    <div class="wrap">
        <h1>기본 설정</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>헤더제목</th><td><input type="text" name="title" value="<?=get_option('sjg_title')?>" class="regular-text"></td></tr>
                <tr><th>기본URL</th><td><input type="url" name="url" value="<?=get_option('sjg_url')?>" class="regular-text"></td></tr>
                <tr><th>탭메뉴</th><td>
                    <?php for($i=0;$i<3;$i++):?>
                    <div style="margin-bottom:10px">
                        <input type="text" name="tn[]" placeholder="탭<?=$i+1?>" value="<?=$ts[$i]['name']??''?>" class="regular-text">
                        <input type="url" name="tu[]" placeholder="URL" value="<?=$ts[$i]['url']??''?>" class="regular-text">
                        <input type="radio" name="ta" value="<?=$i?>" <?=isset($ts[$i])&&$ts[$i]['active']?'checked':''?>> 활성
                    </div>
                    <?php endfor;?>
                </td></tr>
            </table>
            <p><input type="submit" class="button-primary" value="저장"></p>
        </form>
    </div>
    <?php
}

add_shortcode('support_cards','sjg_render');
function sjg_render(){
    ob_start();
    include plugin_dir_path(__FILE__).'template.php';
    return ob_get_clean();
}

add_action('wp_enqueue_scripts','sjg_assets');
function sjg_assets(){
    wp_enqueue_style('sjg-css',plugin_dir_url(__FILE__).'style.css','','1.0');
    wp_enqueue_script('sjg-js',plugin_dir_url(__FILE__).'script.js',['jquery'],'1.0',1);
}
?>

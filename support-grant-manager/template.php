<?php
/**
 * 지원금 카드 프론트엔드 템플릿
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'support_cards';
$cards = $wpdb->get_results("SELECT * FROM $table ORDER BY display_order ASC, id DESC");
$ad_codes = json_decode(get_option('sjg_ad_codes', '{}'), true);
$tabs = json_decode(get_option('sjg_tabs', '[]'), true);
$header_title = get_option('sjg_header_title', '지원금 스킨');
$connect_url = get_option('sjg_connect_url', home_url());

// 광고 코드 파싱
$display_ad = $ad_codes['display'] ?? '';
$anchor_ad = $ad_codes['anchor'] ?? '';
$interstitial_ad = $ad_codes['interstitial'] ?? '';
$multiplex_ad = $ad_codes['multiplex'] ?? '';
?>

<div class="sjg-wrapper">
    <!-- 헤더 -->
    <header class="sjg-header">
        <div class="sjg-container">
            <div class="sjg-logo">
                <?php if (has_custom_logo()): ?>
                    <?php the_custom_logo(); ?>
                <?php endif; ?>
            </div>
            <h1 class="sjg-logo-text"><?php echo esc_html($header_title); ?></h1>
        </div>
    </header>

    <!-- 탭 메뉴 -->
    <?php if (!empty($tabs)): ?>
    <div class="sjg-tab-wrapper">
        <div class="sjg-container">
            <nav class="sjg-tab-container">
                <ul class="sjg-tabs">
                    <?php foreach ($tabs as $tab): ?>
                        <li class="sjg-tab-item">
                            <a class="sjg-tab-link <?php echo $tab['active'] ? 'active' : ''; ?>" 
                               href="<?php echo esc_url($tab['url'] ?: $connect_url); ?>">
                                <?php echo esc_html($tab['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>

    <div class="sjg-container sjg-main-content">
        <!-- 이탈 방지 팝업 -->
        <div class="sjg-exit-popup-overlay" id="sjgExitPopup">
            <div class="sjg-exit-popup">
                <div class="sjg-exit-popup-title">🎁 잠깐! 놓치신 혜택이 있어요</div>
                <div class="sjg-exit-popup-desc">
                    지금 확인 안 하면<br>
                    <strong>최대 300만원</strong> 지원금을 못 받을 수 있어요!
                </div>
                <button class="sjg-exit-popup-btn" onclick="sjgClosePopupAndScroll()">
                    내 지원금 확인하기 →
                </button>
                <button class="sjg-exit-popup-close" onclick="sjgClosePopupNotNow()">
                    다음에 할게요
                </button>
            </div>
        </div>

        <!-- 상단 인트로 -->
        <div class="sjg-intro-section">
            <span class="sjg-intro-badge">신청마감 D-3일</span>
            <p class="sjg-intro-sub">숨은 보험금 1분만에 찾기!</p>
            <h2 class="sjg-intro-title">숨은 지원금 찾기</h2>
        </div>

        <!-- 디스플레이 광고 (상단) -->
        <?php if ($display_ad): ?>
        <div class="sjg-ad-container">
            <?php echo $display_ad; ?>
        </div>
        <?php endif; ?>

        <!-- 정보 박스 -->
        <div class="sjg-info-box">
            <div class="sjg-info-box-header">
                <span class="sjg-info-box-icon">🏷️</span>
                <span class="sjg-info-box-title">신청 안하면 절대 못 받아요</span>
            </div>
            <div class="sjg-info-box-amount">1인 평균 127만원 환급</div>
            <p class="sjg-info-box-desc">대한민국 92%가 놓치고 있는 정부 지원금! 지금 확인하고 혜택 놓치지 마세요.</p>
        </div>

        <!-- 카드 그리드 -->
        <div class="sjg-info-card-grid">
            <?php if (!empty($cards)): ?>
                <?php foreach ($cards as $index => $card): ?>
                    
                    <!-- 광고 삽입 (1번째, 4번째, 7번째 카드 전) -->
                    <?php if ($display_ad && in_array($index, [0, 3, 6])): ?>
                    <div class="sjg-ad-card">
                        <div style="display:flex; justify-content:center; width:100%;">
                            <?php echo $display_ad; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 지원금 카드 -->
                    <a class="sjg-info-card <?php echo $card->is_featured ? 'featured' : ''; ?>" 
                       href="<?php echo esc_url($card->link_url); ?>">
                        <div class="sjg-info-card-highlight">
                            <?php if ($card->is_featured): ?>
                            <span class="sjg-info-card-badge">🔥 인기</span>
                            <?php endif; ?>
                            <div class="sjg-info-card-amount"><?php echo esc_html($card->amount); ?></div>
                            <div class="sjg-info-card-amount-sub"><?php echo esc_html($card->amount_sub); ?></div>
                        </div>
                        <div class="sjg-info-card-content">
                            <h3 class="sjg-info-card-title"><?php echo esc_html($card->keyword); ?></h3>
                            <p class="sjg-info-card-desc"><?php echo esc_html($card->description); ?></p>
                            <div class="sjg-info-card-details">
                                <div class="sjg-info-card-row">
                                    <span class="sjg-info-card-label">지원대상</span>
                                    <span class="sjg-info-card-value"><?php echo esc_html($card->target); ?></span>
                                </div>
                                <div class="sjg-info-card-row">
                                    <span class="sjg-info-card-label">신청시기</span>
                                    <span class="sjg-info-card-value"><?php echo esc_html($card->period); ?></span>
                                </div>
                            </div>
                            <div class="sjg-info-card-btn">
                                지금 바로 신청하기 <span class="sjg-btn-arrow">→</span>
                            </div>
                        </div>
                    </a>
                    
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; padding: 40px;">등록된 지원금이 없습니다.</p>
            <?php endif; ?>
        </div>

        <!-- 히어로 섹션 (CTA) -->
        <div class="sjg-hero-section">
            <div class="sjg-hero-content">
                <span class="sjg-hero-urgent">🔥 신청마감 D-3일</span>
                <p class="sjg-hero-sub">숨은 지원금 1분만에 찾기!</p>
                <h2 class="sjg-hero-title">
                    나의 <span class="sjg-hero-highlight">숨은 지원금</span> 찾기
                </h2>
                <p class="sjg-hero-amount">신청자 <strong>1인 평균 127만원</strong> 수령</p>
                <a class="sjg-hero-cta" href="<?php echo esc_url($connect_url); ?>">
                    30초만에 내 지원금 확인 <span class="sjg-cta-arrow">→</span>
                </a>
                <div class="sjg-hero-trust">
                    <span class="sjg-trust-item">✓ 무료 조회</span>
                    <span class="sjg-trust-item">✓ 30초 완료</span>
                    <span class="sjg-trust-item">✓ 개인정보 보호</span>
                </div>
                <div class="sjg-hero-notice">
                    <div class="sjg-notice-title">💡신청 안하면 못 받아요</div>
                    <p class="sjg-notice-desc">대한민국 92%가 놓치고 있는 정부 지원금, 지금 확인하고 혜택 놓치지 마세요!</p>
                </div>
            </div>
        </div>

        <!-- 멀티플렉스 광고 -->
        <?php if ($multiplex_ad): ?>
        <div class="sjg-ad-container">
            <?php echo $multiplex_ad; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 앵커 광고 -->
<?php if ($anchor_ad): ?>
<div class="sjg-anchor-ad">
    <?php echo $anchor_ad; ?>
</div>
<?php endif; ?>

<!-- 전면 광고 -->
<?php if ($interstitial_ad): ?>
<div class="sjg-interstitial-ad" style="display:none;">
    <?php echo $interstitial_ad; ?>
</div>
<?php endif; ?>

<?php
/**
 * Plugin Name: 지원금 올인원 관리 플러그인
 * Description: 지원금 카드 자동 생성 및 광고 관리 올인원 플러그인
 * Version: 1.0.0
 * Author: 아로스
 */

if (!defined('ABSPATH')) exit;

// 플러그인 활성화 시 실행
register_activation_hook(__FILE__, 'sjg_activate');
function sjg_activate() {
    global $wpdb;
    $table = $wpdb->prefix . 'support_cards';
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        keyword varchar(200) NOT NULL,
        amount varchar(200) NOT NULL,
        amount_sub varchar(200) NOT NULL,
        description text NOT NULL,
        target varchar(100) NOT NULL,
        period varchar(100) NOT NULL,
        link_url varchar(500) NOT NULL,
        is_featured tinyint(1) DEFAULT 0,
        display_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // 기본 옵션 설정
    add_option('sjg_header_title', '지원금 스킨');
    add_option('sjg_connect_url', home_url());
    add_option('sjg_tabs', json_encode([
        ['name' => '전체', 'url' => '', 'active' => true],
        ['name' => '청년', 'url' => '', 'active' => false],
        ['name' => '신혼', 'url' => '', 'active' => false]
    ]));
    add_option('sjg_ad_codes', json_encode([
        'display' => '',
        'anchor' => '',
        'interstitial' => '',
        'multiplex' => ''
    ]));
}

// 관리자 메뉴 추가
add_action('admin_menu', 'sjg_admin_menu');
function sjg_admin_menu() {
    add_menu_page(
        '지원금 관리',
        '지원금 관리',
        'manage_options',
        'support-cards',
        'sjg_cards_page',
        'dashicons-money-alt',
        20
    );
    
    add_submenu_page(
        'support-cards',
        '카드 추가',
        '카드 추가',
        'manage_options',
        'support-cards-add',
        'sjg_add_card_page'
    );
    
    add_submenu_page(
        'support-cards',
        '광고 설정',
        '광고 설정',
        'manage_options',
        'support-ads',
        'sjg_ads_page'
    );
    
    add_submenu_page(
        'support-cards',
        '기본 설정',
        '기본 설정',
        'manage_options',
        'support-settings',
        'sjg_settings_page'
    );
}

// 카드 목록 페이지
function sjg_cards_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'support_cards';
    
    // 삭제 처리
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $wpdb->delete($table, ['id' => intval($_GET['id'])]);
        echo '<div class="notice notice-success"><p>카드가 삭제되었습니다.</p></div>';
    }
    
    $cards = $wpdb->get_results("SELECT * FROM $table ORDER BY display_order ASC, id DESC");
    ?>
    <div class="wrap">
        <h1>지원금 카드 관리</h1>
        <a href="?page=support-cards-add" class="page-title-action">새 카드 추가</a>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="50">순서</th>
                    <th>키워드</th>
                    <th>금액</th>
                    <th>대상</th>
                    <th>시기</th>
                    <th>인기</th>
                    <th width="150">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cards)): ?>
                    <tr><td colspan="7">등록된 카드가 없습니다.</td></tr>
                <?php else: ?>
                    <?php foreach ($cards as $card): ?>
                    <tr>
                        <td><?php echo esc_html($card->display_order); ?></td>
                        <td><strong><?php echo esc_html($card->keyword); ?></strong></td>
                        <td><?php echo esc_html($card->amount); ?></td>
                        <td><?php echo esc_html($card->target); ?></td>
                        <td><?php echo esc_html($card->period); ?></td>
                        <td><?php echo $card->is_featured ? '🔥' : '-'; ?></td>
                        <td>
                            <a href="?page=support-cards-add&action=edit&id=<?php echo $card->id; ?>">수정</a> | 
                            <a href="?page=support-cards&action=delete&id=<?php echo $card->id; ?>" onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// 카드 추가/수정 페이지
function sjg_add_card_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'support_cards';
    $is_edit = isset($_GET['action']) && $_GET['action'] === 'edit';
    $card = null;
    
    if ($is_edit && isset($_GET['id'])) {
        $card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['id'])));
    }
    
    // 폼 제출 처리
    if (isset($_POST['sjg_submit'])) {
        $keyword = sanitize_text_field($_POST['keyword']);
        
        // AI 자동 생성 (키워드만 입력된 경우)
        if (!empty($keyword) && empty($_POST['amount'])) {
            $generated = sjg_generate_card_data($keyword);
            $data = [
                'keyword' => $keyword,
                'amount' => $generated['amount'],
                'amount_sub' => $generated['amount_sub'],
                'description' => $generated['description'],
                'target' => $generated['target'],
                'period' => $generated['period'],
                'link_url' => sanitize_url($_POST['link_url'] ?: get_option('sjg_connect_url')),
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'display_order' => intval($_POST['display_order'])
            ];
        } else {
            $data = [
                'keyword' => $keyword,
                'amount' => sanitize_text_field($_POST['amount']),
                'amount_sub' => sanitize_text_field($_POST['amount_sub']),
                'description' => sanitize_textarea_field($_POST['description']),
                'target' => sanitize_text_field($_POST['target']),
                'period' => sanitize_text_field($_POST['period']),
                'link_url' => sanitize_url($_POST['link_url']),
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'display_order' => intval($_POST['display_order'])
            ];
        }
        
        if ($is_edit) {
            $wpdb->update($table, $data, ['id' => intval($_GET['id'])]);
            echo '<div class="notice notice-success"><p>카드가 수정되었습니다.</p></div>';
        } else {
            $wpdb->insert($table, $data);
            echo '<div class="notice notice-success"><p>카드가 추가되었습니다.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo $is_edit ? '카드 수정' : '새 카드 추가'; ?></h1>
        
        <form method="post" style="max-width: 800px;">
            <table class="form-table">
                <tr>
                    <th><label for="keyword">키워드 (필수) *</label></th>
                    <td>
                        <input type="text" name="keyword" id="keyword" class="regular-text" required 
                               value="<?php echo $card ? esc_attr($card->keyword) : ''; ?>"
                               placeholder="예: 청년내일채움공제">
                        <p class="description">키워드만 입력하면 나머지 내용이 자동 생성됩니다!</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount">금액/혜택</label></th>
                    <td>
                        <input type="text" name="amount" id="amount" class="regular-text"
                               value="<?php echo $card ? esc_attr($card->amount) : ''; ?>"
                               placeholder="예: 최대 1200만원">
                        <p class="description">비워두면 자동 생성됩니다</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount_sub">부가 설명</label></th>
                    <td>
                        <input type="text" name="amount_sub" id="amount_sub" class="regular-text"
                               value="<?php echo $card ? esc_attr($card->amount_sub) : ''; ?>"
                               placeholder="예: 2년 근속 시">
                    </td>
                </tr>
                <tr>
                    <th><label for="description">상세 설명</label></th>
                    <td>
                        <textarea name="description" id="description" rows="3" class="large-text"
                                  placeholder="예: 중소기업 취업 청년의 장기근속을 지원하는 공제제도"><?php echo $card ? esc_textarea($card->description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="target">지원대상</label></th>
                    <td>
                        <input type="text" name="target" id="target" class="regular-text"
                               value="<?php echo $card ? esc_attr($card->target) : ''; ?>"
                               placeholder="예: 만 15~34세 청년"
                               maxlength="20">
                        <p class="description">20글자 이내</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="period">신청시기</label></th>
                    <td>
                        <input type="text" name="period" id="period" class="regular-text"
                               value="<?php echo $card ? esc_attr($card->period) : ''; ?>"
                               placeholder="예: 상시">
                    </td>
                </tr>
                <tr>
                    <th><label for="link_url">연결 URL</label></th>
                    <td>
                        <input type="url" name="link_url" id="link_url" class="regular-text"
                               value="<?php echo $card ? esc_url($card->link_url) : get_option('sjg_connect_url'); ?>"
                               placeholder="https://example.com">
                    </td>
                </tr>
                <tr>
                    <th><label for="display_order">표시 순서</label></th>
                    <td>
                        <input type="number" name="display_order" id="display_order" class="small-text"
                               value="<?php echo $card ? esc_attr($card->display_order) : '0'; ?>">
                        <p class="description">낮을수록 먼저 표시됩니다</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="is_featured">인기 카드</label></th>
                    <td>
                        <input type="checkbox" name="is_featured" id="is_featured" value="1"
                               <?php echo ($card && $card->is_featured) ? 'checked' : ''; ?>>
                        <label for="is_featured">이 카드를 인기 카드로 표시</label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="sjg_submit" class="button button-primary" value="<?php echo $is_edit ? '수정하기' : '추가하기'; ?>">
                <a href="?page=support-cards" class="button">목록으로</a>
            </p>
        </form>
    </div>
    <?php
}

// AI 자동 생성 함수 (API 없이 로컬 규칙 기반)
function sjg_generate_card_data($keyword) {
    $keyword = trim($keyword);
    
    // 지원금 데이터베이스 (실제 정부 지원금 정보)
    $support_db = [
        '청년내일채움공제' => [
            'amount' => '최대 1200만원',
            'amount_sub' => '2년 근속 시',
            'description' => '중소기업 취업 청년의 장기근속을 지원하는 공제제도',
            'target' => '만 15~34세 청년',
            'period' => '상시'
        ],
        '청년도약계좌' => [
            'amount' => '최대 5000만원',
            'amount_sub' => '5년 만기 시',
            'description' => '청년의 자산형성을 지원하는 정책형 금융상품',
            'target' => '만 19~34세 청년',
            'period' => '상시'
        ],
        '청년월세지원' => [
            'amount' => '월 20만원',
            'amount_sub' => '최대 12개월',
            'description' => '무주택 청년의 주거비 부담 완화를 위한 월세 지원',
            'target' => '만 19~34세 무주택 청년',
            'period' => '상시'
        ],
        '신혼부부 전세자금대출' => [
            'amount' => '최대 3억원',
            'amount_sub' => '연 1%대 금리',
            'description' => '신혼부부의 주거 안정을 위한 저금리 전세자금 대출',
            'target' => '혼인 7년 이내 부부',
            'period' => '상시'
        ],
        '첫만남이용권' => [
            'amount' => '200만원',
            'amount_sub' => '1회 지급',
            'description' => '출생아동 양육비 부담 경감을 위한 바우처 지원',
            'target' => '2022년 이후 출생아',
            'period' => '출생 후 60일 이내'
        ],
        '국민취업지원제도' => [
            'amount' => '월 50만원',
            'amount_sub' => '최대 6개월',
            'description' => '구직활동 중인 저소득층에게 취업지원서비스와 생계비 지원',
            'target' => '15~69세 구직자',
            'period' => '상시'
        ],
        '청년창업지원금' => [
            'amount' => '최대 1억원',
            'amount_sub' => '무이자 또는 저금리',
            'description' => '청년 창업가의 사업 초기 자금 지원',
            'target' => '만 39세 이하 예비창업자',
            'period' => '분기별 모집'
        ],
        '근로장려금' => [
            'amount' => '최대 330만원',
            'amount_sub' => '연 1회 지급',
            'description' => '저소득 근로자 가구의 근로를 장려하고 소득을 지원',
            'target' => '총소득 4000만원 미만',
            'period' => '매년 5월'
        ],
        '자녀장려금' => [
            'amount' => '최대 100만원',
            'amount_sub' => '자녀 1인당',
            'description' => '저소득 가구의 자녀 양육비 지원',
            'target' => '부양자녀가 있는 가구',
            'period' => '매년 5월'
        ],
        '청년 구직활동지원금' => [
            'amount' => '월 50만원',
            'amount_sub' => '최대 6개월',
            'description' => '미취업 청년의 구직활동 및 역량개발 지원',
            'target' => '만 18~34세 미취업자',
            'period' => '상시'
        ],
        '기초생활수급' => [
            'amount' => '월 62만원',
            'amount_sub' => '생계급여 기준',
            'description' => '최저생활보장을 위한 생계·의료·주거·교육급여 지원',
            'target' => '중위소득 30~50% 이하',
            'period' => '상시'
        ],
        '노인일자리' => [
            'amount' => '월 27만원',
            'amount_sub' => '월 30시간 활동',
            'description' => '어르신 사회참여 및 소득 보충을 위한 일자리 제공',
            'target' => '만 65세 이상',
            'period' => '매년 1월'
        ]
    ];
    
    // 정확히 일치하는 키워드가 있으면 반환
    if (isset($support_db[$keyword])) {
        return $support_db[$keyword];
    }
    
    // 부분 일치 검색
    foreach ($support_db as $key => $data) {
        if (strpos($key, $keyword) !== false || strpos($keyword, $key) !== false) {
            return $data;
        }
    }
    
    // 키워드 기반 패턴 매칭
    $patterns = [
        '청년' => ['target' => '만 19~34세 청년', 'period' => '상시'],
        '신혼' => ['target' => '혼인 7년 이내 부부', 'period' => '상시'],
        '노인' => ['target' => '만 65세 이상', 'period' => '상시'],
        '창업' => ['amount' => '최대 5000만원', 'amount_sub' => '무이자 지원'],
        '대출' => ['amount' => '최대 2억원', 'amount_sub' => '연 1%대 금리'],
        '월세' => ['amount' => '월 20만원', 'amount_sub' => '최대 12개월'],
        '전세' => ['amount' => '최대 3억원', 'amount_sub' => '저금리 대출'],
    ];
    
    $result = [
        'amount' => '최대 300만원',
        'amount_sub' => '조건 충족 시',
        'description' => $keyword . ' 지원 혜택',
        'target' => '조건 충족자',
        'period' => '상시'
    ];
    
    foreach ($patterns as $pattern => $data) {
        if (strpos($keyword, $pattern) !== false) {
            $result = array_merge($result, $data);
        }
    }
    
    return $result;
}

// 광고 설정 페이지
function sjg_ads_page() {
    if (isset($_POST['sjg_ads_submit'])) {
        $ad_codes = [
            'display' => wp_kses_post($_POST['ad_display']),
            'anchor' => wp_kses_post($_POST['ad_anchor']),
            'interstitial' => wp_kses_post($_POST['ad_interstitial']),
            'multiplex' => wp_kses_post($_POST['ad_multiplex'])
        ];
        update_option('sjg_ad_codes', json_encode($ad_codes));
        echo '<div class="notice notice-success"><p>광고 설정이 저장되었습니다.</p></div>';
    }
    
    $ad_codes = json_decode(get_option('sjg_ad_codes', '{}'), true);
    ?>
    <div class="wrap">
        <h1>광고 설정</h1>
        <p>타뷸라, 데이블, 애드센스 등 모든 광고 코드 지원</p>
        
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="ad_display">디스플레이 광고</label></th>
                    <td>
                        <textarea name="ad_display" id="ad_display" rows="5" class="large-text code"><?php echo esc_textarea($ad_codes['display'] ?? ''); ?></textarea>
                        <p class="description">카드 사이에 표시될 광고 (1번, 4번, 7번 카드 전)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ad_anchor">앵커 광고</label></th>
                    <td>
                        <textarea name="ad_anchor" id="ad_anchor" rows="5" class="large-text code"><?php echo esc_textarea($ad_codes['anchor'] ?? ''); ?></textarea>
                        <p class="description">화면 하단 고정 광고</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ad_interstitial">전면 광고</label></th>
                    <td>
                        <textarea name="ad_interstitial" id="ad_interstitial" rows="5" class="large-text code"><?php echo esc_textarea($ad_codes['interstitial'] ?? ''); ?></textarea>
                        <p class="description">페이지 로드 시 전면 광고</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ad_multiplex">멀티플렉스 광고</label></th>
                    <td>
                        <textarea name="ad_multiplex" id="ad_multiplex" rows="5" class="large-text code"><?php echo esc_textarea($ad_codes['multiplex'] ?? ''); ?></textarea>
                        <p class="description">컨텐츠 하단 추천 광고</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="sjg_ads_submit" class="button button-primary" value="저장">
            </p>
        </form>
    </div>
    <?php
}

// 기본 설정 페이지
function sjg_settings_page() {
    if (isset($_POST['sjg_settings_submit'])) {
        update_option('sjg_header_title', sanitize_text_field($_POST['header_title']));
        update_option('sjg_connect_url', sanitize_url($_POST['connect_url']));
        
        $tabs = [];
        for ($i = 0; $i < 3; $i++) {
            if (!empty($_POST['tab_name'][$i])) {
                $tabs[] = [
                    'name' => sanitize_text_field($_POST['tab_name'][$i]),
                    'url' => sanitize_url($_POST['tab_url'][$i]),
                    'active' => isset($_POST['tab_active']) && $_POST['tab_active'] == $i
                ];
            }
        }
        update_option('sjg_tabs', json_encode($tabs));
        
        echo '<div class="notice notice-success"><p>설정이 저장되었습니다.</p></div>';
    }
    
    $tabs = json_decode(get_option('sjg_tabs', '[]'), true);
    ?>
    <div class="wrap">
        <h1>기본 설정</h1>
        
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="header_title">헤더 제목</label></th>
                    <td>
                        <input type="text" name="header_title" id="header_title" class="regular-text"
                               value="<?php echo esc_attr(get_option('sjg_header_title', '지원금 스킨')); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="connect_url">기본 연결 URL</label></th>
                    <td>
                        <input type="url" name="connect_url" id="connect_url" class="regular-text"
                               value="<?php echo esc_url(get_option('sjg_connect_url', home_url())); ?>">
                    </td>
                </tr>
                <tr>
                    <th>탭 메뉴</th>
                    <td>
                        <?php for ($i = 0; $i < 3; $i++): ?>
                        <div style="margin-bottom: 10px;">
                            <input type="text" name="tab_name[]" placeholder="탭 이름" class="regular-text"
                                   value="<?php echo isset($tabs[$i]) ? esc_attr($tabs[$i]['name']) : ''; ?>">
                            <input type="url" name="tab_url[]" placeholder="URL" class="regular-text"
                                   value="<?php echo isset($tabs[$i]) ? esc_url($tabs[$i]['url']) : ''; ?>">
                            <label>
                                <input type="radio" name="tab_active" value="<?php echo $i; ?>"
                                       <?php echo (isset($tabs[$i]) && $tabs[$i]['active']) ? 'checked' : ''; ?>>
                                활성
                            </label>
                        </div>
                        <?php endfor; ?>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="sjg_settings_submit" class="button button-primary" value="저장">
            </p>
        </form>
    </div>
    <?php
}

// 숏코드 등록
add_shortcode('support_cards', 'sjg_render_cards');
function sjg_render_cards() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'template.php';
    return ob_get_clean();
}

// 프론트엔드 스타일/스크립트
add_action('wp_enqueue_scripts', 'sjg_enqueue_scripts');
function sjg_enqueue_scripts() {
    wp_enqueue_style('sjg-style', plugin_dir_url(__FILE__) . 'style.css', [], '1.0.0');
    wp_enqueue_script('sjg-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0.0', true);
}
?>

/**
 * 지원금 카드 플러그인 JavaScript
 * CTR 극대화 및 사용자 경험 최적화
 */

(function($) {
    'use strict';
    
    // 이탈 방지 팝업 관리
    var popupShown = sessionStorage.getItem('sjgExitPopupShown');
    var closeCount = parseInt(sessionStorage.getItem('sjgExitPopupCloseCount')) || 0;
    var scrollTriggered = false;
    
    // 페이지 로드 완료 후 실행
    $(document).ready(function() {
        
        // 1. PC: 마우스 이탈 감지
        $(document).on('mouseout', function(e) {
            if (e.clientY < 0 && !popupShown && closeCount < 2) {
                showExitPopup();
            }
        });
        
        // 2. 모바일 + PC: 뒤로가기 감지
        history.pushState(null, '', location.href);
        $(window).on('popstate', function() {
            if (closeCount < 2) {
                showExitPopup();
            }
            history.pushState(null, '', location.href);
        });
        
        // 3. 모바일: 스크롤 60% 도달 시 팝업
        $(window).on('scroll', function() {
            var scrollHeight = $(document).height() - $(window).height();
            var scrollPercent = ($(window).scrollTop() / scrollHeight) * 100;
            
            if (scrollPercent > 60 && !popupShown && !scrollTriggered && closeCount < 2) {
                showExitPopup();
                scrollTriggered = true;
            }
        });
        
        // 탭 활성화 처리
        if (window.location.hash) {
            $('.sjg-tab-link').removeClass('active');
            $('.sjg-tab-link[href="' + window.location.hash + '"]').addClass('active');
        }
        
        // 카드 클릭 추적 (CTR 최적화)
        $('.sjg-info-card').on('click', function(e) {
            // 구글 애널리틱스 이벤트 (있는 경우)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'card_click', {
                    'card_keyword': $(this).find('.sjg-info-card-title').text(),
                    'card_position': $(this).index()
                });
            }
        });
        
        // 히어로 CTA 버튼 클릭 추적
        $('.sjg-hero-cta').on('click', function(e) {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'cta_click', {
                    'button_text': $(this).text().trim()
                });
            }
        });
        
        // 전면 광고 표시 (3초 후)
        setTimeout(function() {
            if ($('.sjg-interstitial-ad').length) {
                $('.sjg-interstitial-ad').fadeIn();
                setTimeout(function() {
                    $('.sjg-interstitial-ad').fadeOut();
                }, 5000);
            }
        }, 3000);
        
        // 카드 호버 효과 강화 (모바일 터치 지원)
        $('.sjg-info-card').on('touchstart', function() {
            $(this).addClass('touch-active');
        }).on('touchend', function() {
            var self = this;
            setTimeout(function() {
                $(self).removeClass('touch-active');
            }, 300);
        });
        
    });
    
    // 이탈 방지 팝업 표시
    function showExitPopup() {
        $('#sjgExitPopup').css('display', 'flex');
        popupShown = true;
    }
    
    // 팝업 닫기 및 스크롤
    window.sjgClosePopupAndScroll = function() {
        $('#sjgExitPopup').fadeOut();
        $('html, body').animate({
            scrollTop: $('.sjg-hero-section').offset().top - 100
        }, 800);
    };
    
    // 팝업 닫기 (나중에)
    window.sjgClosePopupNotNow = function() {
        $('#sjgExitPopup').fadeOut();
        popupShown = true;
        closeCount++;
        sessionStorage.setItem('sjgExitPopupShown', 'true');
        sessionStorage.setItem('sjgExitPopupCloseCount', closeCount);
    };
    
})(jQuery);

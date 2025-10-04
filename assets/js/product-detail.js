(function($) {
        $(document).ready(function() {
            let currentSlide = 0;
            const totalSlides = $('.slide').length;
            
            // Hiển thị slide đầu tiên
            showSlide(0);
            
            // Xử lý click thumbnail
            $('.thumbnail').on('click', function() {
                const index = $(this).data('index');
                showSlide(index);
            });
            
            // Xử lý nút prev
            $('.prev-slide').on('click', function() {
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                showSlide(currentSlide);
            });
            
            // Xử lý nút next
            $('.next-slide').on('click', function() {
                currentSlide = (currentSlide + 1) % totalSlides;
                showSlide(currentSlide);
            });
            
            // Hàm hiển thị slide
            function showSlide(index) {
                currentSlide = index;
                
                // Ẩn tất cả slides
                $('.slide').removeClass('active');
                
                // Hiển thị slide được chọn
                $('.slide').eq(index).addClass('active');
                
                // Cập nhật thumbnail active
                $('.thumbnail').removeClass('active');
                $('.thumbnail').eq(index).addClass('active');
            }
            
            // Xử lý keyboard navigation
            $(document).on('keydown', function(e) {
                if (e.which === 37) { // Left arrow
                    $('.prev-slide').click();
                } else if (e.which === 39) { // Right arrow
                    $('.next-slide').click();
                }
            });
            
            // Touch/swipe support cho mobile
            let startX = 0;
            let endX = 0;
            
            $('.product-main-image').on('touchstart', function(e) {
                startX = e.originalEvent.touches[0].clientX;
            });
            
            $('.product-main-image').on('touchend', function(e) {
                endX = e.originalEvent.changedTouches[0].clientX;
                handleSwipe();
            });
            
            function handleSwipe() {
                const diffX = startX - endX;
                const threshold = 50; // Minimum swipe distance
                
                if (Math.abs(diffX) > threshold) {
                    if (diffX > 0) {
                        // Swipe left - next slide
                        $('.next-slide').click();
                    } else {
                        // Swipe right - prev slide
                        $('.prev-slide').click();
                    }
                }
            }
            
            
            // Tabs
            $('.tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                
                // Xóa class active từ tất cả các tab và tab content
                $('.tab-btn').removeClass('active');
                $('.tab-content').removeClass('active');
                
                // Thêm class active cho tab và tab content được chọn
                $(this).addClass('active');
                $('#' + tabId).addClass('active');
            });
            
            // Product Variants
            $('.variant-option').on('click', function() {
                $('.variant-option').removeClass('selected');
                $(this).addClass('selected');
            });
        });
    })(jQuery);

jQuery(document).ready(function($) {
    $('.size-option').on('click', function() {
        var $tieredPricing = $(this).find('.tiered-pricing');
        if ($tieredPricing.length) {
            $tieredPricing.toggle();
            // Ẩn các tiered pricing khác
            $('.tiered-pricing').not($tieredPricing).hide();
        }
    });
    
    // Ẩn khi click bên ngoài
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.size-option').length) {
            $('.tiered-pricing').hide();
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const description = document.querySelector('.product-description-content');
    
    if (!description) return;
    
    // ✅ LẤY HTML CONTENT thay vì textContent
    const fullHTML = description.innerHTML;
    
    // Tạo temporary element để đếm độ dài text thực (không có HTML tags)
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = fullHTML;
    const textLength = tempDiv.textContent.trim().length;
    
    const limit = 200; // Giới hạn số ký tự
    
    if (textLength > limit) {
        // Hàm truncate HTML thông minh
        const truncatedHTML = truncateHTML(fullHTML, limit);
        
        // Tạo wrapper cho short và full content
        const wrapper = document.createElement('div');
        wrapper.className = 'description-wrapper';
        
        wrapper.innerHTML = `
            <div class="short-text">${truncatedHTML}...</div>
            <div class="full-text" style="display:none;">${fullHTML}</div>
            <button class="read-more-btn">Đọc thêm</button>
        `;
        
        // Thay thế nội dung
        description.innerHTML = '';
        description.appendChild(wrapper);
        
        // Xử lý sự kiện click
        const btn = description.querySelector('.read-more-btn');
        const shortDiv = description.querySelector('.short-text');
        const fullDiv = description.querySelector('.full-text');
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (fullDiv.style.display === 'none') {
                // Hiển thị full content
                shortDiv.style.display = 'none';
                fullDiv.style.display = 'block';
                btn.textContent = 'Thu gọn';
                
                // Scroll mượt đến vị trí
                description.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'nearest' 
                });
            } else {
                // Hiển thị short content
                shortDiv.style.display = 'block';
                fullDiv.style.display = 'none';
                btn.textContent = 'Đọc thêm';
            }
        });
    }
});

/**
 * Hàm truncate HTML thông minh
 * Giữ nguyên cấu trúc HTML, chỉ cắt text content
 */
function truncateHTML(html, maxLength) {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    
    let charCount = 0;
    let truncated = false;
    
    function traverseNodes(node) {
        if (truncated) return;
        
        // Nếu là text node
        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent;
            const remainingChars = maxLength - charCount;
            
            if (charCount + text.length > maxLength) {
                // Cắt text tại vị trí phù hợp (tránh cắt giữa từ)
                let cutText = text.substring(0, remainingChars);
                const lastSpace = cutText.lastIndexOf(' ');
                
                if (lastSpace > 0) {
                    cutText = cutText.substring(0, lastSpace);
                }
                
                node.textContent = cutText;
                truncated = true;
                
                // Xóa các siblings sau node này
                let nextSibling = node.nextSibling;
                while (nextSibling) {
                    const toRemove = nextSibling;
                    nextSibling = nextSibling.nextSibling;
                    toRemove.parentNode.removeChild(toRemove);
                }
            } else {
                charCount += text.length;
            }
        } 
        // Nếu là element node
        else if (node.nodeType === Node.ELEMENT_NODE) {
            // Các thẻ tự đóng như img, br không tính
            if (['IMG', 'BR', 'HR'].includes(node.tagName)) {
                return;
            }
            
            // Duyệt qua các child nodes
            const childNodes = Array.from(node.childNodes);
            for (let child of childNodes) {
                if (truncated) {
                    // Xóa các child còn lại
                    node.removeChild(child);
                } else {
                    traverseNodes(child);
                }
            }
        }
    }
    
    traverseNodes(tempDiv);
    
    // Dọn dẹp các thẻ rỗng
    cleanEmptyTags(tempDiv);
    
    return tempDiv.innerHTML;
}

/**
 * Xóa các thẻ HTML rỗng sau khi truncate
 */
function cleanEmptyTags(element) {
    const children = Array.from(element.children);
    
    children.forEach(child => {
        // Đệ quy dọn dẹp children trước
        cleanEmptyTags(child);
        
        // Nếu thẻ không có nội dung và không phải thẻ tự đóng
        if (!child.textContent.trim() && 
            !['IMG', 'BR', 'HR', 'INPUT'].includes(child.tagName)) {
            child.remove();
        }
    });
}
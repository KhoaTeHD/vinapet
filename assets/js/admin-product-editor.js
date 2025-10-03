/**
 * File: assets/js/admin-product-editor.js
 * JavaScript cho Product Meta Editor
 */

jQuery(document).ready(function($) {
    
    // Tab switching
    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Character counters
    function updateCounter(inputId, counterId, max) {
        const val = $('#' + inputId).val();
        const count = val.length;
        $('#' + counterId).text(count);
        
        if (count > max) {
            $('#' + counterId).css('color', 'red');
        } else if (count > max * 0.9) {
            $('#' + counterId).css('color', 'orange');
        } else {
            $('#' + counterId).css('color', 'green');
        }
    }
    
    // Update counters on load
    updateCounter('custom_short_desc', 'short-desc-count', 200);
    updateCounter('seo_title', 'seo-title-count', 60);
    updateCounter('seo_description', 'seo-desc-count', 160);
    
    // Update on input
    $('#custom_short_desc').on('input', function() {
        updateCounter('custom_short_desc', 'short-desc-count', 200);
    });
    
    $('#seo_title').on('input', function() {
        updateCounter('seo_title', 'seo-title-count', 60);
        updateSEOPreview();
    });
    
    $('#seo_description').on('input', function() {
        updateCounter('seo_description', 'seo-desc-count', 160);
        updateSEOPreview();
    });
    
    // Update SEO Preview
    function updateSEOPreview() {
        const title = $('#seo_title').val() || 'Tiêu đề sản phẩm';
        const desc = $('#seo_description').val() || 'Mô tả sản phẩm sẽ hiển thị ở đây...';
        
        $('#preview-title').text(title);
        $('#preview-desc').text(desc);
    }
    
    // OG Image Upload
    let ogImageFrame;
    
    $('#upload-og-image').on('click', function(e) {
        e.preventDefault();
        
        if (ogImageFrame) {
            ogImageFrame.open();
            return;
        }
        
        ogImageFrame = wp.media({
            title: 'Chọn ảnh OG Image',
            button: {
                text: 'Sử dụng ảnh này'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        ogImageFrame.on('select', function() {
            const attachment = ogImageFrame.state().get('selection').first().toJSON();
            $('#seo_og_image').val(attachment.url);
            $('#og-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px;">');
            $('#remove-og-image').show();
        });
        
        ogImageFrame.open();
    });
    
    // Remove OG Image
    $('#remove-og-image').on('click', function(e) {
        e.preventDefault();
        $('#seo_og_image').val('');
        $('#og-image-preview').html('<p style="color: #999;">Chưa có ảnh (sẽ dùng ảnh sản phẩm chính)</p>');
        $(this).hide();
    });
    
    // Save Meta
    $('#save-meta').on('click', function() {
        const btn = $(this);
        const spinner = $('#save-spinner');
        const productCode = $('#product-code').val();
        
        // Get editor content
        const customDesc = tinymce.get('custom_description') ? 
                          tinymce.get('custom_description').getContent() : 
                          $('#custom_description').val();
        
        btn.prop('disabled', true);
        spinner.addClass('is-active');
        
        $.ajax({
            url: vinapet_editor_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'vinapet_save_product_meta',
                nonce: vinapet_editor_ajax.nonce,
                product_code: productCode,
                custom_description: customDesc,
                custom_short_desc: $('#custom_short_desc').val(),
                seo_title: $('#seo_title').val(),
                seo_description: $('#seo_description').val(),
                seo_og_image: $('#seo_og_image').val(),
                is_featured: $('#is_featured').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data);
                } else {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', 'Lỗi kết nối!');
            },
            complete: function() {
                btn.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });
    
    // Delete Meta
    $('#delete-meta').on('click', function() {
        if (!confirm('Bạn chắc chắn muốn xóa tất cả tùy chỉnh? Sẽ quay về dùng dữ liệu từ ERP.')) {
            return;
        }
        
        const btn = $(this);
        const productCode = $('#product-code').val();
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: vinapet_editor_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'vinapet_delete_product_meta',
                nonce: vinapet_editor_ajax.nonce,
                product_code: productCode
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data);
                    
                    // Redirect về danh sách sau 1s
                    setTimeout(function() {
                        window.location.href = '<?php echo admin_url("admin.php?page=vinapet-products-erp"); ?>';
                    }, 1000);
                } else {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', 'Lỗi kết nối!');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
    
    // Preview Product
    $('#preview-product').on('click', function() {
        const productCode = $('#product-code').val();
        const previewUrl = '<?php echo home_url("/san-pham/"); ?>' + productCode;
        window.open(previewUrl, '_blank');
    });
    
    // Show Notice
    function showNotice(type, message) {
        const className = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div class="notice ' + className + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.editor-header').after(notice);
        
        // Auto dismiss after 5s
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 5000);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    // Initialize preview on load
    updateSEOPreview();
});
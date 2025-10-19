<?php
/**
 * File: includes/api/rest-lead-endpoint.php
 * REST API Endpoint để nhận webhook từ Elementor Form
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API route
 */
function vinapet_register_lead_rest_route()
{
    register_rest_route('vinapet/v1', '/lead', array(
        'methods'             => 'POST',
        'callback'            => 'vinapet_handle_lead_submission',
        'permission_callback' => '__return_true', // Public access
    ));
}
add_action('rest_api_init', 'vinapet_register_lead_rest_route');

/**
 * Main callback function - Xử lý lead submission
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function vinapet_handle_lead_submission($request)
{
    // 1. Lấy data từ request body
    $params = $request->get_json_params();

    // 2. Validate required fields
    $validation_error = vinapet_validate_lead_fields($params);
    if ($validation_error) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => $validation_error
        ), 400);
    }

    // 3. Sanitize input data
    $sanitized_data = vinapet_sanitize_lead_data($params);

    // 4. Transform data sang format ERPNext
    $erp_data = vinapet_transform_to_erp_format($sanitized_data);

    // 5. Gọi ERP API để tạo lead
    try {
        $result = vinapet_create_lead_in_erp($erp_data);

        if ($result['status'] === 'success') {
            // Success response
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Gửi thông tin thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.',
                'data'    => array(
                    'lead_name' => $result['lead_name'] ?? null
                )
            ), 200);
        } else {
            // ERP API trả về lỗi
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Hệ thống đang bận, vui lòng thử lại sau ít phút.'
            ), 500);
        }
    } catch (Exception $e) {
        // Catch mọi exception
        error_log('VinaPet Lead Endpoint Error: ' . $e->getMessage());
        
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Có lỗi xảy ra, vui lòng thử lại sau.'
        ), 500);
    }
}

/**
 * Validate required fields từ Elementor
 * 
 * @param array $data
 * @return string|null Error message hoặc null nếu hợp lệ
 */
function vinapet_validate_lead_fields($data)
{
    // Required fields từ Elementor
    $required_fields = array(
        'name'        => 'Họ tên',
        'email'       => 'Email',
        'phonenumber' => 'Số điện thoại',
        'message'     => 'Nội dung'
    );

    // Check từng field bắt buộc
    foreach ($required_fields as $field => $label) {
        if (empty($data[$field]) || trim($data[$field]) === '') {
            return "Vui lòng điền {$label}.";
        }
    }

    // Validate email format
    if (!is_email($data['email'])) {
        return 'Email không hợp lệ.';
    }

    // Validate message length tối thiểu
    if (strlen(trim($data['message'])) < 10) {
        return 'Nội dung phải có ít nhất 10 ký tự.';
    }

    return null; // Hợp lệ
}

/**
 * Sanitize input data
 * 
 * @param array $data
 * @return array
 */
function vinapet_sanitize_lead_data($data)
{
    return array(
        'name'        => sanitize_text_field($data['name']),
        'email'       => sanitize_email($data['email']),
        'phonenumber' => sanitize_text_field($data['phonenumber']),
        'message'     => sanitize_textarea_field($data['message'])
    );
}

/**
 * Transform Elementor data sang format ERPNext
 * 
 * @param array $data
 * @return array
 */
function vinapet_transform_to_erp_format($data)
{
    return array(
        'company_name' => 'Cá nhân',
        'contact_name' => $data['name'],
        'email'        => $data['email'],
        'phone'        => $data['phonenumber'],
        'address'      => '', // Elementor không có field này
        'needs'        => $data['message']
    );
}

/**
 * Gọi ERP API Client để tạo lead
 * 
 * @param array $erp_data
 * @return array
 * @throws Exception
 */
function vinapet_create_lead_in_erp($erp_data)
{
    // Load ERP API Client class
    if (!class_exists('ERP_API_Client')) {
        require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
    }

    // Khởi tạo ERP client
    $erp_client = new ERP_API_Client();

    // Check config
    if (!$erp_client->is_configured()) {
        throw new Exception('ERPNext chưa được cấu hình.');
    }

    // Call API
    $result = $erp_client->create_lead($erp_data);

    if ($result === false) {
        throw new Exception('ERP API call failed.');
    }

    return $result;
}
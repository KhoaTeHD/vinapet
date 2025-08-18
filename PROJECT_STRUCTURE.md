1. Thư Mục Gốc (/)
Chứa các file cốt lõi của WordPress, bao gồm:

404.php - Template cho trang lỗi 404
archive.php - Template cho trang lưu trữ
footer.php - Footer chính của theme
functions.php - File chức năng chính
header.php - Header chính của theme
index.php - Template mặc định
page.php - Template cho trang tĩnh
single.php - Template cho bài viết đơn
style.css - CSS chính và thông tin theme
screenshot.png - Ảnh đại diện của theme

2. Thư Mục assets/
Chứa tất cả tài nguyên tĩnh, được tổ chức thành các thư mục con:

css/ - Stylesheet:

theme.css - CSS chính
responsive.css - CSS cho thiết kế responsive
Các file CSS chuyên biệt khác


js/ - JavaScript:

main.js - JavaScript chính
Các file JS cho các tính năng cụ thể


images/ - Hình ảnh, chia thành các thư mục con theo mục đích sử dụng
libs/ - Thư viện bên thứ ba:

swiper/
select2/
rateit/



3. Thư Mục includes/
Chứa mã PHP chính của theme, tổ chức theo chức năng:

api/ - Xử lý giao tiếp API:

class-erp-api-client.php - Giao tiếp với ERPNext
class-erp-webhook-handler.php - Xử lý webhooks


models/ - Các lớp đại diện cho dữ liệu:

class-product.php - Mô hình sản phẩm
class-customer.php - Mô hình khách hàng
class-order.php - Mô hình đơn hàng


helpers/ - Các lớp tiện ích:

class-cache-manager.php - Quản lý bộ nhớ đệm
class-url-generator.php - Tạo URL
class-breadcrumb.php - Điều hướng breadcrumb


admin/ - Chức năng cho admin:

class-admin-menu.php - Quản lý menu admin
class-orders-list-table.php - Bảng danh sách đơn hàng


ajax/ - Xử lý AJAX:

ajax-orders.php - AJAX cho đơn hàng
ajax-products.php - AJAX cho sản phẩm


post-types/ - Đăng ký custom post types:

post-type-brands.php - CPT cho thương hiệu
post-type-store-system.php - CPT cho hệ thống cửa hàng


setup.php - Thiết lập cơ bản cho theme

4. Thư Mục template-parts/
Chứa các phần template có thể tái sử dụng, tổ chức theo chức năng:

product/ - Template liên quan đến sản phẩm:

item.php - Hiển thị một sản phẩm trong danh sách
listing.php - Hiển thị danh sách sản phẩm
detail.php - Hiển thị chi tiết sản phẩm


account/ - Template liên quan đến tài khoản:

dashboard.php - Bảng điều khiển tài khoản
orders.php - Danh sách đơn hàng
address.php - Quản lý địa chỉ


checkout/ - Template liên quan đến thanh toán:

cart-item.php - Một mục trong giỏ hàng
payment-methods.php - Phương thức thanh toán


common/ - Các phần dùng chung:

breadcrumbs.php - Điều hướng breadcrumb
pagination.php - Phân trang
sidebar.php - Thanh bên


header/ và footer/ - Các phần của header và footer

5. Thư Mục page-templates/
Chứa các template trang đầy đủ:

template-shop.php - Template trang cửa hàng
template-account.php - Template trang tài khoản
template-checkout.php - Template trang thanh toán

6. Thư Mục backend/
Chứa các file cho giao diện quản trị:

css/ - CSS cho admin
js/ - JavaScript cho admin
partials/ - Các phần giao diện quản trị

7. Thư Mục data/
Chứa dữ liệu tĩnh dạng JSON:

locations.json - Dữ liệu vị trí
wards.json - Dữ liệu phường/xã
categories.json - Dữ liệu danh mục

8. Thư Mục languages/
Chứa các file dịch:

vi.po, vi.mo - File dịch tiếng Việt
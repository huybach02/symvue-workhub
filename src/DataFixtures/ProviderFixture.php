<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Provider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ProviderFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['provider'];
    }

    public function load(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();
        $connection->executeStatement('DELETE FROM provider');

        $providers = [
            [
                'code' => 'VINAMILK',
                'name' => 'Công ty Cổ phần Sữa Việt Nam (Vinamilk)',
                'phone' => '02854155555',
                'email' => 'vinamilk@vinamilk.com.vn',
                'address' => '10 Tân Trào, Phường Tân Phú, Quận 7, TP. Hồ Chí Minh',
                'taxNumber' => '0300588569',
                'bankName' => 'Vietcombank',
                'bankNumber' => '0071001234567',
                'note' => 'Nhà cung cấp các sản phẩm sữa và chế phẩm từ sữa.',
                'status' => 1,
            ],
            [
                'code' => 'PEPSICO',
                'name' => 'Công ty TNHH Nước giải khát Suntory PepsiCo Việt Nam',
                'phone' => '02838219437',
                'email' => 'contact@suntorypepsico.vn',
                'address' => 'Cao ốc Sheraton, 88 Đồng Khởi, Quận 1, TP. Hồ Chí Minh',
                'taxNumber' => '0312053090',
                'bankName' => 'BIDV',
                'bankNumber' => '1201000789012',
                'note' => 'Cung cấp nước ngọt Pepsi, 7Up, trà ô long Tea+ Plus.',
                'status' => 1,
            ],
            [
                'code' => 'MASAN',
                'name' => 'Công ty Cổ phần Hàng tiêu dùng Masan',
                'phone' => '02862563862',
                'email' => 'info@masangroup.com',
                'address' => 'Tòa nhà MPlaza Saigon, 39 Lê Duẩn, Quận 1, TP. Hồ Chí Minh',
                'taxNumber' => '0302017440',
                'bankName' => 'Techcombank',
                'bankNumber' => '19033333333333',
                'note' => 'Cung cấp gia vị (nước mắm Nam Ngư, nước tương Chinsu), mì ăn liền.',
                'status' => 1,
            ],
            [
                'code' => 'SABECO',
                'name' => 'Tổng công ty Cổ phần Bia - Rượu - Nước giải khát Sài Gòn (Sabeco)',
                'phone' => '02838294081',
                'email' => 'sabeco@sabeco.com.vn',
                'address' => '187 Nguyễn Chí Thanh, Phường 12, Quận 5, TP. Hồ Chí Minh',
                'taxNumber' => '0300553865',
                'bankName' => 'Vietinbank',
                'bankNumber' => '110000123456',
                'note' => 'Nhà cung cấp bia Sài Gòn, bia 333.',
                'status' => 1,
            ],
            [
                'code' => 'MEGAMARKET',
                'name' => 'Công ty TNHH MM Mega Market (Việt Nam)',
                'phone' => '02835190390',
                'email' => 'contact-us@mmvietnam.com',
                'address' => 'Khu dân cư mới An Khánh, Phường An Phú, TP. Thủ Đức, TP. Hồ Chí Minh',
                'taxNumber' => '0302249539',
                'bankName' => 'ACB',
                'bankNumber' => '20260626888',
                'note' => 'Nhà cung cấp bán sỉ thực phẩm, nguyên liệu và văn phòng phẩm.',
                'status' => 1,
            ],
            [
                'code' => 'VIETGAPDL',
                'name' => 'Hợp tác xã rau an toàn VietGAP Đà Lạt',
                'phone' => '02633822456',
                'email' => 'lienhe@vietgapdalat.vn',
                'address' => '45 Phan Đình Phùng, Phường 2, TP. Đà Lạt, Tỉnh Lâm Đồng',
                'taxNumber' => '5801345678',
                'bankName' => 'Agribank',
                'bankNumber' => '5200205123456',
                'note' => 'Cung cấp các loại rau củ quả tươi Đà Lạt đạt chuẩn VietGAP.',
                'status' => 1,
            ],
            [
                'code' => 'TRUNGUYEN',
                'name' => 'Tập đoàn Trung Nguyên Legend',
                'phone' => '02839251852',
                'email' => 'office@trungnguyenlegend.com',
                'address' => '82-84 Bùi Thị Xuân, Phường Bến Thành, Quận 1, TP. Hồ Chí Minh',
                'taxNumber' => '0302482431',
                'bankName' => 'Vietcombank',
                'bankNumber' => '0071007890123',
                'note' => 'Cung cấp cà phê hòa tan G7, cà phê hạt Trung Nguyên.',
                'status' => 1,
            ],
            [
                'code' => 'ACECOOK',
                'name' => 'Công ty Cổ phần Acecook Việt Nam',
                'phone' => '02838154064',
                'email' => 'info@acecookvietnam.com',
                'address' => 'Lô II-3, Đường số 11, KCN Tân Bình, Quận Tân Phú, TP. Hồ Chí Minh',
                'taxNumber' => '0300808687',
                'bankName' => 'Vietinbank',
                'bankNumber' => '112000456789',
                'note' => 'Nhà sản xuất mì ăn liền Hảo Hảo, phở Đệ Nhất.',
                'status' => 1,
            ],
            [
                'code' => 'VISSAN',
                'name' => 'Công ty Cổ phần Việt Nam Kỹ nghệ Súc sản (Vissan)',
                'phone' => '02838433907',
                'email' => 'vissan@vissan.com.vn',
                'address' => '420 Nơ Trang Long, Phường 13, Quận Bình Thạnh, TP. Hồ Chí Minh',
                'taxNumber' => '0300101356',
                'bankName' => 'BIDV',
                'bankNumber' => '1351000987654',
                'note' => 'Cung cấp thịt heo tươi sống, xúc xích, đồ hộp Vissan.',
                'status' => 1,
            ],
            [
                'code' => 'CPVIETNAM',
                'name' => 'Công ty Cổ phần Chăn nuôi C.P. Việt Nam',
                'phone' => '02513836251',
                'email' => 'cpvietnam@cp.com.vn',
                'address' => 'Số 2, Đường 2A, KCN Biên Hòa II, Phường Long Bình Tân, TP. Biên Hòa, Đồng Nai',
                'taxNumber' => '3600234567',
                'bankName' => 'Sacombank',
                'bankNumber' => '060123456789',
                'note' => 'Cung cấp thịt gà, trứng sạch, xúc xích C.P.',
                'status' => 1,
            ],
            [
                'code' => 'THTRUE_MILK',
                'name' => 'Công ty Cổ phần Chuỗi Thực phẩm TH (TH True Milk)',
                'phone' => '1800545440',
                'email' => 'chamsockhachhang@thmilk.vn',
                'address' => '166 Nguyễn Thái Học, Phường Quang Trung, TP. Vinh, Nghệ An',
                'taxNumber' => '2901198765',
                'bankName' => 'MBBank',
                'bankNumber' => '0520260626888',
                'note' => 'Nhà cung cấp sữa tươi tiệt trùng TH True Milk.',
                'status' => 1,
            ],
            [
                'code' => 'AJINOMOTO',
                'name' => 'Công ty Ajinomoto Việt Nam',
                'phone' => '02838221916',
                'email' => 'tvkh@ajinomoto.com.vn',
                'address' => 'KCN Biên Hòa I, Đường 1, Phường An Bình, TP. Biên Hòa, Đồng Nai',
                'taxNumber' => '3600216462',
                'bankName' => 'Techcombank',
                'bankNumber' => '19022222222222',
                'note' => 'Cung cấp bột ngọt Ajinomoto, hạt nêm Aji-ngon, sốt mayonnaise.',
                'status' => 1,
            ],
            [
                'code' => 'PHUCHONG',
                'name' => 'Hộ kinh doanh Thực phẩm sạch Phúc Hưng',
                'phone' => '0903123456',
                'email' => 'phuchungfood@gmail.com',
                'address' => 'Chợ đầu mối Bình Điền, Phường 7, Quận 8, TP. Hồ Chí Minh',
                'taxNumber' => '8012345678',
                'bankName' => 'Agribank',
                'bankNumber' => '6460205001234',
                'note' => 'Cung cấp hải sản đông lạnh và thực phẩm khô bán sỉ.',
                'status' => 1,
            ],
            [
                'code' => 'KINDO',
                'name' => 'Công ty Cổ phần Mondelez Kinh Đô Việt Nam',
                'phone' => '02838270838',
                'email' => 'customercare.vietnam@mdlz.com',
                'address' => '138-142 Hai Bà Trưng, Phường Đa Kao, Quận 1, TP. Hồ Chí Minh',
                'taxNumber' => '0313333333',
                'bankName' => 'StandardChartered',
                'bankNumber' => '88812345678',
                'note' => 'Cung cấp bánh trung thu Kinh Đô, bánh quy Oreo, Cosy.',
                'status' => 1,
            ],
            [
                'code' => 'ANCO',
                'name' => 'Công ty Cổ phần Nông nghiệp Quốc tế Anco',
                'phone' => '02839151617',
                'email' => 'info@anco.com.vn',
                'address' => 'Lầu 10, Tòa nhà MPlaza, 39 Lê Duẩn, Quận 1, TP. Hồ Chí Minh',
                'taxNumber' => '0304567890',
                'bankName' => 'HDBank',
                'bankNumber' => '009704370012345',
                'note' => 'Cung cấp thức ăn chăn nuôi gia súc, gia cầm.',
                'status' => 1,
            ],
            [
                'code' => 'HUNGPHAT',
                'name' => 'Công ty TNHH Hưng Phát Đạt',
                'phone' => '02437654321',
                'email' => 'hungphatdat.co@gmail.com',
                'address' => 'KCN Quang Minh, Thị trấn Quang Minh, Huyện Mê Linh, Hà Nội',
                'taxNumber' => '0102345678',
                'bankName' => 'TPBank',
                'bankNumber' => '03001234567',
                'note' => 'Cung cấp bao bì giấy, thùng carton đóng gói thực phẩm.',
                'status' => 1,
            ],
            [
                'code' => 'MINHPHU',
                'name' => 'Tập đoàn Thủy sản Minh Phú',
                'phone' => '02903838262',
                'email' => 'minhphu@minhphu.com.vn',
                'address' => '26 Lê Hồng Phong, Phường 8, TP. Cà Mau, Tỉnh Cà Mau',
                'taxNumber' => '2000323399',
                'bankName' => 'Vietcombank',
                'bankNumber' => '0111000123456',
                'note' => 'Cung cấp tôm xuất khẩu và các mặt hàng thủy hải sản cao cấp.',
                'status' => 1,
            ],
            [
                'code' => 'VINATABA',
                'name' => 'Tổng công ty Thuốc lá Việt Nam (Vinataba)',
                'phone' => '02438511671',
                'email' => 'vinataba@vinataba.com.vn',
                'address' => '83 Nguyễn Khang, Phường Yên Hòa, Quận Cầu Giấy, Hà Nội',
                'taxNumber' => '0100100414',
                'bankName' => 'BIDV',
                'bankNumber' => '12210001234567',
                'note' => 'Cung cấp nguyên liệu lá thuốc lá và sản phẩm tiêu dùng liên quan.',
                'status' => 1,
            ],
            [
                'code' => 'GIALAI_COFFEE',
                'name' => 'Công ty TNHH Một thành viên Cà phê Gia Lai',
                'phone' => '02693824156',
                'email' => 'caphegialai@gialai.gov.vn',
                'address' => '97 Phạm Văn Đồng, Phường Thống Nhất, TP. Pleiku, Gia Lai',
                'taxNumber' => '5900189765',
                'bankName' => 'Agribank',
                'bankNumber' => '5100201001234',
                'note' => 'Cung cấp hạt cà phê nhân xanh Robusta và Arabica xuất khẩu.',
                'status' => 1,
            ],
            [
                'code' => 'CHOLIMEX',
                'name' => 'Công ty Cổ phần Thực phẩm Cholimex',
                'phone' => '02837653389',
                'email' => 'cholimexfood@cholimexfood.com.vn',
                'address' => 'Lô C40-43/I, Đường số 7, KCN Vĩnh Lộc, Huyện Bình Chánh, TP. Hồ Chí Minh',
                'taxNumber' => '0304475742',
                'bankName' => 'Sacombank',
                'bankNumber' => '060012345678',
                'note' => 'Cung cấp tương ớt, tương cà, gia vị kho cá và nước tương Cholimex.',
                'status' => 1,
            ],
        ];

        foreach ($providers as $data) {
            $provider = new Provider();
            $provider->setCode($data['code']);
            $provider->setName($data['name']);
            $provider->setPhone($data['phone']);
            $provider->setEmail($data['email']);
            $provider->setAddress($data['address']);
            $provider->setTaxNumber($data['taxNumber']);
            $provider->setBankName($data['bankName']);
            $provider->setBankNumber($data['bankNumber']);
            $provider->setNote($data['note']);
            $provider->setStatus($data['status']);

            $manager->persist($provider);
        }

        $manager->flush();
    }
}

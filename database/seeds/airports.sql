-- database/seeds/airports.sql
-- Dữ liệu sân bay Việt Nam + quốc tế phổ biến

INSERT INTO airports (iata_code, name_vi, name_en, city_vi, city_en, country_code, country_vi, entity_id) VALUES
-- Việt Nam
('SGN', 'Sân bay Tân Sơn Nhất',    'Tan Son Nhat International',  'TP Hồ Chí Minh', 'Ho Chi Minh City', 'VN', 'Việt Nam', '95673379'),
('HAN', 'Sân bay Nội Bài',         'Noi Bai International',       'Hà Nội',          'Hanoi',            'VN', 'Việt Nam', '128668079'),
('DAD', 'Sân bay Đà Nẵng',         'Da Nang International',       'Đà Nẵng',         'Da Nang',          'VN', 'Việt Nam', '95674529'),
('CXR', 'Sân bay Cam Ranh',        'Cam Ranh International',      'Nha Trang',       'Nha Trang',        'VN', 'Việt Nam', '95674536'),
('PQC', 'Sân bay Phú Quốc',        'Phu Quoc International',      'Phú Quốc',        'Phu Quoc',         'VN', 'Việt Nam', '95674565'),
('VCA', 'Sân bay Cần Thơ',         'Can Tho International',       'Cần Thơ',         'Can Tho',          'VN', 'Việt Nam', '95674560'),
('HUI', 'Sân bay Phú Bài (Huế)',   'Phu Bai International',       'Huế',             'Hue',              'VN', 'Việt Nam', '95674527'),
('DLI', 'Sân bay Liên Khương',     'Lien Khuong Airport',         'Đà Lạt',          'Da Lat',           'VN', 'Việt Nam', '95674526'),
('VII', 'Sân bay Vinh',            'Vinh Airport',                'Vinh',            'Vinh',             'VN', 'Việt Nam', '95674561'),
('HPH', 'Sân bay Cát Bi',         'Cat Bi International',        'Hải Phòng',       'Hai Phong',        'VN', 'Việt Nam', '95674530'),
('VDO', 'Sân bay Vân Đồn',        'Van Don International',       'Quảng Ninh',      'Quang Ninh',       'VN', 'Việt Nam', '128668397'),
('BMV', 'Sân bay Buôn Ma Thuột',   'Buon Ma Thuot Airport',       'Buôn Ma Thuột',   'Buon Ma Thuot',    'VN', 'Việt Nam', '95674521'),
-- Đông Nam Á
('BKK', 'Sân bay Suvarnabhumi',    'Suvarnabhumi Airport',        'Bangkok',         'Bangkok',          'TH', 'Thái Lan', '95565050'),
('DMK', 'Sân bay Don Mueang',      'Don Mueang Airport',          'Bangkok',         'Bangkok',          'TH', 'Thái Lan', '128668416'),
('SIN', 'Sân bay Changi',          'Changi Airport',              'Singapore',       'Singapore',        'SG', 'Singapore', '128668174'),
('KUL', 'Sân bay KLIA',            'Kuala Lumpur International',  'Kuala Lumpur',    'Kuala Lumpur',     'MY', 'Malaysia', '95565044'),
('MNL', 'Sân bay Ninoy Aquino',    'Ninoy Aquino International',  'Manila',          'Manila',           'PH', 'Philippines', '95673592'),
-- Đông Á
('ICN', 'Sân bay Incheon',         'Incheon International',       'Seoul',           'Seoul',            'KR', 'Hàn Quốc', '95673492'),
('NRT', 'Sân bay Narita',          'Narita International',        'Tokyo',           'Tokyo',            'JP', 'Nhật Bản', '95673581'),
('HND', 'Sân bay Haneda',          'Haneda Airport',              'Tokyo',           'Tokyo',            'JP', 'Nhật Bản', '95673576'),
('HKG', 'Sân bay Hồng Kông',      'Hong Kong International',     'Hồng Kông',       'Hong Kong',        'HK', 'Hồng Kông', '95673496'),
('PEK', 'Sân bay Bắc Kinh',       'Beijing Capital International','Bắc Kinh',       'Beijing',          'CN', 'Trung Quốc', '95673529'),
-- Châu Âu & Mỹ
('CDG', 'Sân bay Charles de Gaulle','Charles de Gaulle Airport', 'Paris',           'Paris',            'FR', 'Pháp', '95565039'),
('LHR', 'Sân bay Heathrow',        'London Heathrow',             'London',          'London',           'GB', 'Anh', '95565057'),
('FRA', 'Sân bay Frankfurt',       'Frankfurt Airport',           'Frankfurt',       'Frankfurt',        'DE', 'Đức', '95565043'),
('JFK', 'Sân bay JFK',             'John F. Kennedy International','New York',       'New York',         'US', 'Mỹ', '95565060'),
('LAX', 'Sân bay Los Angeles',     'Los Angeles International',   'Los Angeles',     'Los Angeles',      'US', 'Mỹ', '95565069'),
('SYD', 'Sân bay Sydney',          'Sydney Kingsford Smith',      'Sydney',          'Sydney',           'AU', 'Úc', '95565074')
ON CONFLICT (iata_code) DO UPDATE
    SET entity_id = EXCLUDED.entity_id,
        is_active = true;

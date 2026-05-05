// =========================================
// DATA EXPORTS
// =========================================
export const destinationsData = [
    { title: "Khám phá New York", date: "05/02/2026", img: "./assets/logos/dest-newyork.png" },
    { title: "Du lịch Paris", date: "05/02/2026", img: "./assets/logos/dest-paris.png" },
    { title: "Tokyo sầm uất", date: "05/02/2026", img: "./assets/logos/dest-tokyo.png" },
    { title: "Nghỉ dưỡng Bali", date: "05/02/2026", img: "./assets/logos/dest-bali.png" }
];

export const newsData = [
    { title: "Mở đường bay mới", date: "05/02/2026", img: "./assets/logos/news-1.png" },
    { title: "Khuyến mãi mùa hè", date: "05/02/2026", img: "./assets/logos/news-2.png" },
    { title: "Hướng dẫn thủ tục", date: "05/02/2026", img: "./assets/logos/news-3.png" },
    { title: "Chính sách hành lý", date: "05/02/2026", img: "./assets/logos/news-4.png" }
];

export const partnersData = [
    { name: "Vietnam Airlines", img: "./assets/logos/VN.png", url: "https://www.vietnamairlines.com" },
    { name: "VietJet Air", img: "./assets/logos/VJ.png", url: "https://www.vietjetair.com" },
    { name: "American Airlines", img: "./assets/logos/AA.png", url: "https://www.aa.com" },
    { name: "Air Canada", img: "./assets/logos/AC.png", url: "https://www.aircanada.com" },
    { name: "EVA Air", img: "./assets/logos/BR.png", url: "https://www.evaair.com" },
    { name: "Cathay Pacific", img: "./assets/logos/CX.png", url: "https://www.cathaypacific.com" },
    { name: "Japan Airlines", img: "./assets/logos/JL.png", url: "https://www.jal.co.jp" },
    { name: "STARLUX Airlines", img: "./assets/logos/JX.png", url: "https://www.starlux-airlines.com" },
    { name: "Korean Air", img: "./assets/logos/KE.png", url: "https://www.koreanair.com" },
    { name: "All Nippon Airways (ANA)", img: "./assets/logos/NH.png", url: "https://www.ana.co.jp" },
    { name: "Asiana Airlines", img: "./assets/logos/OZ.png", url: "https://flyasiana.com" },
    { name: "Singapore Airlines", img: "./assets/logos/SQ.png", url: "https://www.singaporeair.com" },
    { name: "Thai Airways", img: "./assets/logos/TG.png", url: "https://www.thaiairways.com" },
    { name: "United Airlines", img: "./assets/logos/UA.png", url: "https://www.united.com" },
    { name: "Bamboo Airways", img: "./assets/logos/9G.webp", url: "https://www.bambooairways.com" }
];

export const popupContentData = {
    "NỘI ĐỊA": `
        <h4 class="location-popup__country"><u>Việt Nam</u></h4>
        <div class="location-popup__grid">
            <div class="location-popup__col">
                <div class="location-popup__item">TP HCM (SGN)</div>
                <div class="location-popup__item">HCM-Long Thành (LTH)</div>
                <div class="location-popup__item">Nha Trang (CXR)</div>
                <div class="location-popup__item">Phú Quốc (PQC)</div>
                <div class="location-popup__item">Cà Mau (CAH)</div>
                <div class="location-popup__item">Đà Lạt (DLI)</div>
                <div class="location-popup__item">Huế (HUI)</div>
                <div class="location-popup__item">Tuy Hòa (TBB)</div>
                <div class="location-popup__item">Quy Nhơn (UIH)</div>
                <div class="location-popup__item">Côn Đảo (VCS)</div>
                <div class="location-popup__item">Quảng Ninh (VDO)</div>
                <div class="location-popup__item">Rạch Giá (VKG)</div>
            </div>
            <div class="location-popup__col">
                <div class="location-popup__item">Hà Nội (HAN)</div>
                <div class="location-popup__item">Đà Nẵng (DAD)</div>
                <div class="location-popup__item">Cần Thơ (VCA)</div>
                <div class="location-popup__item">Buôn Ma Thuột (BMV)</div>
                <div class="location-popup__item">Điện Biên (DIN)</div>
                <div class="location-popup__item">Hải Phòng (HPH)</div>
                <div class="location-popup__item">Pleiku (PXU)</div>
                <div class="location-popup__item">Thanh Hoá (THD)</div>
                <div class="location-popup__item">Chu Lai (VCL)</div>
                <div class="location-popup__item">Đồng Hới (VDH)</div>
                <div class="location-popup__item">Vinh (VII)</div>
            </div>
        </div>
    `,
    "MỸ - CANADA": `
        <h4 class="location-popup__country"><u>Hoa Kỳ</u></h4>
        <div class="location-popup__grid" style="margin-bottom: 15px;">
            <div class="location-popup__col">
                <div class="location-popup__item">Atlanta (ATL)</div>
                <div class="location-popup__item">Chicago (ORD)</div>
                <div class="location-popup__item">Houston (IAH)</div>
                <div class="location-popup__item">Miami (MIA)</div>
                <div class="location-popup__item">New York (JFK)</div>
                <div class="location-popup__item">San Francisco (SFO)</div>
                <div class="location-popup__item">Washington (IAD)</div>
            </div>
            <div class="location-popup__col">
                <div class="location-popup__item">Boston (BOS)</div>
                <div class="location-popup__item">Dallas (DFW)</div>
                <div class="location-popup__item">Los Angeles (LAX)</div>
                <div class="location-popup__item">Minneapolis (MSP)</div>
                <div class="location-popup__item">San Diego (SAN)</div>
                <div class="location-popup__item">Seattle (SEA)</div>
            </div>
        </div>
        <h4 class="location-popup__country"><u>Canada</u></h4>
        <div class="location-popup__grid">
            <div class="location-popup__col">
                <div class="location-popup__item">Toronto (YYZ)</div>
            </div>
            <div class="location-popup__col">
                <div class="location-popup__item">Vancouver (YVR)</div>
            </div>
        </div>
    `,
    "CHÂU Á": `
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
            <div class="location-popup__col">
                <h4 class="location-popup__country"><u>Hồng Kông</u></h4>
                <div class="location-popup__item">Hồng Kông (HKG)</div>
                
                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Hàn Quốc</u></h4>
                <div class="location-popup__item">Seoul (ICN)</div>
                <div class="location-popup__item">Busan (PUS)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Trung Quốc</u></h4>
                <div class="location-popup__item">Quảng Châu (CAN)</div>
                <div class="location-popup__item">Thượng Hải (SHA)</div>
                <div class="location-popup__item">Vũ Hán (WUH)</div>
                <div class="location-popup__item">Beijing (PEK)</div>
                <div class="location-popup__item">Thâm Quyến (SZX)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Campuchia</u></h4>
                <div class="location-popup__item">Phnom Penh (KTI)</div>
                <div class="location-popup__item">Siem Reap (REP)</div>
            </div>
            <div class="location-popup__col">
                <h4 class="location-popup__country"><u>Singapore</u></h4>
                <div class="location-popup__item">Singapore (SIN)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Đài Loan</u></h4>
                <div class="location-popup__item">Đài Bắc (TPE)</div>
                <div class="location-popup__item">Cao Hùng (KHH)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Nhật Bản</u></h4>
                <div class="location-popup__item">Osaka (KIX)</div>
                <div class="location-popup__item">Tokyo (HND)</div>
                <div class="location-popup__item">Tokyo (NRT)</div>
                <div class="location-popup__item">Fukuoka (FUK)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Myanmar</u></h4>
                <div class="location-popup__item">Yangon (RGN)</div>
            </div>
            <div class="location-popup__col">
                <h4 class="location-popup__country"><u>Ấn Độ</u></h4>
                <div class="location-popup__item">New Delhi (DEL)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Indonesia</u></h4>
                <div class="location-popup__item">Surabaya (SUB)</div>
                <div class="location-popup__item">Jakarta (CGK)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Thái Lan</u></h4>
                <div class="location-popup__item">Bangkok (BKK)</div>
                <div class="location-popup__item">Bangkok (DMK)</div>
                <div class="location-popup__item">Chiang Mai (CNX)</div>
                <div class="location-popup__item">Phuket (HKT)</div>
                <div class="location-popup__item">Pattaya (UTP)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Philippines</u></h4>
                <div class="location-popup__item">Manila (MNL)</div>
            </div>
        </div>
    `,
    "CHÂU ÂU": `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="location-popup__col">
                <h4 class="location-popup__country"><u>Vương quốc Anh</u></h4>
                <div class="location-popup__item">Luân Đôn (LHR)</div>
                <div class="location-popup__item">Manchester (MAN)</div>
                <div class="location-popup__item">Glasgow (GLA)</div>
                
                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Pháp</u></h4>
                <div class="location-popup__item">Paris (CDG)</div>
                <div class="location-popup__item">Lyon (LYS)</div>
                <div class="location-popup__item">Nice (NCE)</div>
            </div>
            <div class="location-popup__col">
                <h4 class="location-popup__country"><u>Đức</u></h4>
                <div class="location-popup__item">Frankfurt (FRA)</div>
                <div class="location-popup__item">Berlin (BER)</div>
                <div class="location-popup__item">Munich (MUC)</div>

                <h4 class="location-popup__country" style="margin-top: 15px;"><u>Thụy Sĩ</u></h4>
                <div class="location-popup__item">Zurich (ZRH)</div>
                <div class="location-popup__item">Geneva (GVA)</div>
                <div class="location-popup__item">Basel (BSL)</div>
            </div>
        </div>
    `,
    "CHÂU ÚC": `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="location-popup__col">
                <h4 class="location-popup__country"><u>Úc (Australia)</u></h4>
                <div class="location-popup__item">Sydney (SYD)</div>
                <div class="location-popup__item">Melbourne (MEL)</div>
                <div class="location-popup__item">Brisbane (BNE)</div>
                <div class="location-popup__item">Perth (PER)</div>
                <div class="location-popup__item">Adelaide (ADL)</div>
            </div>
            <div class="location-popup__col">
                <h4 class="location-popup__country"><u>New Zealand</u></h4>
                <div class="location-popup__item">Auckland (AKL)</div>
                <div class="location-popup__item">Wellington (WLG)</div>
                <div class="location-popup__item">Christchurch (CHC)</div>
            </div>
        </div>
    `
};

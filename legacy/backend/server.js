const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');
const https = require('https');

dotenv.config();

const app = express();
const PORT = process.env.PORT || 5000;

app.use(cors());
app.use(express.json());

// Memory Cache for Exchange Rate
let cachedExchangeRate = {
    rate: 25450, // Default fallback
    lastUpdate: null
};

// Function to sync exchange rate from External API using native HTTPS
const syncExchangeRate = () => {
    console.log('🔄 Đang đồng bộ tỷ giá USD/VND từ API...');
    const url = 'https://api.exchangerate-api.com/v4/latest/USD';
    
    https.get(url, (res) => {
        let data = '';
        res.on('data', (chunk) => { data += chunk; });
        res.on('end', () => {
            try {
                const json = JSON.parse(data);
                if (json && json.rates && json.rates.VND) {
                    cachedExchangeRate.rate = Math.round(json.rates.VND);
                    cachedExchangeRate.lastUpdate = new Date();
                    console.log(`✅ Đồng bộ thành công: 1 USD = ${cachedExchangeRate.rate} VND`);
                }
            } catch (e) {
                console.error('❌ Lỗi parse JSON tỷ giá:', e.message);
            }
        });
    }).on('error', (err) => {
        console.error('❌ Lỗi kết nối API tỷ giá:', err.message);
    });
};

// Sync initially and then every 30 minutes
syncExchangeRate();
setInterval(syncExchangeRate, 30 * 60 * 1000);

// Endpoint lấy tỷ giá
app.get('/api/currency/rate', (req, res) => {
    res.json({
        success: true,
        data: cachedExchangeRate
    });
});

// API key đọc từ biến môi trường, không hardcode trong source.
// Thiết lập: export RAPIDAPI_KEY=... hoặc thêm vào file .env ở repo root.
const RAPIDAPI_KEY = process.env.RAPIDAPI_KEY || process.env.SKYSCANNER_RAPIDAPI_KEY || '';
const RAPIDAPI_HOST = 'skyscanner-flights-travel-api.p.rapidapi.com';
const SKYSCANNER_SEARCH_URL = 'https://skyscanner-flights-travel-api.p.rapidapi.com/flights/searchFlights';

if (!RAPIDAPI_KEY) {
    console.warn('[Bayou] RAPIDAPI_KEY chưa được cấu hình — Skyscanner endpoint sẽ trả lỗi 503.');
}

const airportEntityMap = {
    SGN: '95673379',
    HAN: '128668079',
};

const normalizeDate = (dateValue) => {
    const fallbackDate = new Date();
    fallbackDate.setDate(fallbackDate.getDate() + 10);

    if (!dateValue) return fallbackDate.toISOString().slice(0, 10);

    if (/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
        return dateValue;
    }

    const parts = String(dateValue).split('/');
    if (parts.length === 3) {
        const [day, month, year] = parts;
        return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    const parsedDate = new Date(dateValue);
    if (!Number.isNaN(parsedDate.getTime())) {
        return parsedDate.toISOString().slice(0, 10);
    }

    return fallbackDate.toISOString().slice(0, 10);
};

const requestJson = (url, headers = {}) => new Promise((resolve, reject) => {
    https.get(url, { headers }, (apiRes) => {
        let body = '';
        apiRes.on('data', (chunk) => { body += chunk; });
        apiRes.on('end', () => {
            try {
                const json = JSON.parse(body);
                if (apiRes.statusCode < 200 || apiRes.statusCode >= 300) {
                    reject(new Error(json.message || `RapidAPI HTTP ${apiRes.statusCode}`));
                    return;
                }
                resolve(json);
            } catch (error) {
                reject(new Error(`Không đọc được JSON từ RapidAPI: ${body}`));
            }
        });
    }).on('error', reject);
});

const resolveAirline = (itineraryText) => {
    if (itineraryText.includes('-31703') || itineraryText.includes('/viet/')) {
        return {
            name: 'Vietnam Airlines',
            code: 'VN',
            logo: 'https://logos.skyscnr.com/images/airlines/favicon/VN.png',
        };
    }

    if (itineraryText.includes('-31705') || itineraryText.includes('/jtuk/')) {
        return {
            name: 'VietJet Air',
            code: 'VJ',
            logo: 'https://logos.skyscnr.com/images/airlines/favicon/4V.png',
        };
    }

    return {
        name: 'Unknown Airline',
        code: 'NA',
        logo: '',
    };
};

const mapSkyscannerFlights = (json, origin, destination) => {
    const itineraries = json.data?.itineraries || json.itineraries || [];

    return itineraries.map((itinerary, index) => {
        const leg = itinerary.legs?.[0] || {};
        const text = JSON.stringify(itinerary);
        const airline = resolveAirline(text);
        const price = itinerary.price || {};

        return {
            id: itinerary.id || `SKY-${index}`,
            airline: airline.name,
            airline_code: airline.code,
            airline_logo: airline.logo,
            flight_number: `${airline.code}${String(index + 1).padStart(3, '0')}`,
            departure_time: leg.departure,
            arrival_time: leg.arrival,
            price: price.amount || price.raw || 0,
            price_formatted: price.formatted || `${price.currency || ''} ${price.amount || price.raw || ''}`.trim(),
            currency: price.currency || '',
            origin,
            destination,
            booking_url: itinerary.bookingUrl || '',
        };
    }).filter((flight) => flight.departure_time && flight.arrival_time && flight.price);
};

const fetchSkyscannerFlights = async (origin, destination, date) => {
    if (!RAPIDAPI_KEY) {
        const err = new Error('RAPIDAPI_KEY chưa được cấu hình trong môi trường server.');
        err.statusCode = 503;
        throw err;
    }

    const originCode = String(origin || 'SGN').toUpperCase();
    const destinationCode = String(destination || 'HAN').toUpperCase();
    const isoDate = normalizeDate(date);

    const params = new URLSearchParams({
        originSkyId: originCode,
        originEntityId: airportEntityMap[originCode] || '',
        destinationSkyId: destinationCode,
        destinationEntityId: airportEntityMap[destinationCode] || '',
        date: isoDate,
        adults: '1',
        currency: 'VND',
        market: 'VN',
    });

    const json = await requestJson(`${SKYSCANNER_SEARCH_URL}?${params.toString()}`, {
        'X-RapidAPI-Key': RAPIDAPI_KEY,
        'X-RapidAPI-Host': RAPIDAPI_HOST,
    });

    return mapSkyscannerFlights(json, originCode, destinationCode);
};

// Real Flight Search API via RapidAPI Skyscanner
app.post('/api/flights/search', async (req, res, next) => {
    const { from, to, departDate, returnDate, tripType } = req.body;
    
    try {
        const outbound = await fetchSkyscannerFlights(from, to, departDate);
        const inbound = tripType === 'roundtrip' && returnDate
            ? await fetchSkyscannerFlights(to, from, returnDate)
            : [];

        res.json({
            success: true,
            source: 'RapidAPI Skyscanner',
            data: { outbound, inbound }
        });
    } catch (error) {
        next(error);
    }
});

// Legacy PHP-compatible endpoint used by older frontend builds.
app.get('/api-bayou/get_ticket.php', async (req, res, next) => {
    const origin = req.query.DiemDi || req.query.from;
    const destination = req.query.DiemDen || req.query.to;
    const date = req.query.NgayDi || req.query.departDate;

    try {
        const flights = await fetchSkyscannerFlights(origin, destination, date);
        const data = flights.map((flight) => ({
            AirlineName: flight.airline,
            AirlineCode: flight.airline_code,
            FlightNumber: flight.flight_number,
            SeatClass: 'Phổ thông',
            DepartTime: flight.departure_time,
            ArriveTime: flight.arrival_time,
            TotalPrice: flight.price,
            PriceDisplay: flight.price_formatted,
            Logo: flight.airline_logo,
        }));

        res.json({
            status: 'success',
            source: 'RapidAPI Skyscanner',
            request: { origin, destination, date },
            data
        });
    } catch (error) {
        next(error);
    }
});

app.post('/api-bayou/book_ticket.php', (req, res) => {
    res.json({
        status: 'success',
        pnr: 'BY' + Math.floor(Math.random() * 900000 + 100000)
    });
});

app.use((err, req, res, next) => {
    console.error('API error:', err);
    res.status(500).json({
        success: false,
        message: 'Có lỗi xảy ra khi xử lý yêu cầu. Vui lòng thử lại.'
    });
});

app.listen(PORT, () => {
    console.log(`🚀 Bayou Backend đang chạy tại http://localhost:${PORT}`);
});

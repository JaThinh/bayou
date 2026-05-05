import { bindLocationInput, bindCalendarInput } from './popups.js';

export function initSearchWidget() {
    // Trip Tabs logic
    const tripTabs = document.querySelectorAll('.search-widget__tab');
    const returnDateGroup = document.getElementById('return-date-group');
    const inputReturn = document.getElementById('input-return');

    if (tripTabs.length > 0) {
        tripTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tripTabs.forEach(t => t.classList.remove('search-widget__tab--active'));
                tab.classList.add('search-widget__tab--active');

                const mainForm = document.getElementById('flight-search-form');
                const multiForm = document.getElementById('multicity-form');

                if (tab.dataset.trip === 'multicity') {
                    if (mainForm) mainForm.style.display = 'none';
                    if (multiForm) multiForm.style.display = 'block';
                } else {
                    if (mainForm) mainForm.style.display = 'flex';
                    if (multiForm) multiForm.style.display = 'none';

                    if (tab.dataset.trip === 'oneway') {
                        if (returnDateGroup) {
                            returnDateGroup.style.display = 'none';
                        }
                        if (inputReturn) {
                            inputReturn.disabled = true;
                            inputReturn.value = '';
                        }
                    } else {
                        if (returnDateGroup) {
                            returnDateGroup.style.display = 'flex';
                        }
                        if (inputReturn) inputReturn.disabled = false;
                    }
                }
            });
        });
    }

    // Swap Inputs logic (for both Main form and Multi-city)
    const attachSwapLogic = (btnSwap, inputFrom, inputTo) => {
        if (btnSwap && inputFrom && inputTo) {
            btnSwap.addEventListener('click', () => {
                const temp = inputFrom.value;
                inputFrom.value = inputTo.value;
                inputTo.value = temp;

                btnSwap.style.transform = 'rotate(180deg)';
                setTimeout(() => {
                    btnSwap.style.transition = 'none';
                    btnSwap.style.transform = 'rotate(0deg)';
                    setTimeout(() => btnSwap.style.transition = 'transform 0.3s', 50);
                }, 300);
            });
        }
    };

    // Attach to main form
    attachSwapLogic(
        document.getElementById('btn-swap'), 
        document.getElementById('input-from'), 
        document.getElementById('input-to')
    );

    // Default Dates for main form
    const inputDepart = document.getElementById('input-depart');
    if (inputDepart && inputReturn) {
        const todayObj = new Date();
        const tomorrowObj = new Date(todayObj);
        tomorrowObj.setDate(tomorrowObj.getDate() + 1);

        const todayStr = `${String(todayObj.getDate()).padStart(2, '0')}/${String(todayObj.getMonth() + 1).padStart(2, '0')}/${todayObj.getFullYear()}`;
        const tomorrowStr = `${String(tomorrowObj.getDate()).padStart(2, '0')}/${String(tomorrowObj.getMonth() + 1).padStart(2, '0')}/${tomorrowObj.getFullYear()}`;

        if (!inputDepart.value) inputDepart.value = todayStr;
        if (!inputReturn.value) inputReturn.value = tomorrowStr;
    }

    // Auto capitalize airport codes
    const formatAirportInput = (e) => {
        const val = e.target.value;
        if (val.length <= 3) {
            e.target.value = val.toUpperCase();
        }
    };
    
    // Bind capitalization to main inputs
    const inputFrom = document.getElementById('input-from');
    const inputTo = document.getElementById('input-to');
    if (inputFrom) inputFrom.addEventListener('input', formatAirportInput);
    if (inputTo) inputTo.addEventListener('input', formatAirportInput);

    // Dropdowns Logic (Seat & Passenger)
    const btnSeat = document.getElementById('btn-seat');
    const menuSeat = document.getElementById('menu-seat');
    const btnPassenger = document.getElementById('btn-passenger');
    const menuPassenger = document.getElementById('menu-passenger');
    
    let currentSeatClass = 'Phổ thông';
    let passengers = { adult: 1, child: 0, infant: 0 };

    if (btnSeat && menuSeat) {
        btnSeat.addEventListener('click', (e) => {
            e.stopPropagation();
            if (menuPassenger) menuPassenger.classList.remove('custom-dropdown__menu--show');
            menuSeat.classList.toggle('custom-dropdown__menu--show');
        });

        menuSeat.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = e.target.closest('.custom-dropdown__item');
            if (item) {
                menuSeat.querySelectorAll('.custom-dropdown__item').forEach(el => {
                    el.classList.remove('custom-dropdown__item--active');
                    const dot = el.querySelector('.dot');
                    if (dot) dot.remove();
                });
                
                item.classList.add('custom-dropdown__item--active');
                if (item.innerText !== 'Tất cả hạng ghế') {
                    item.innerHTML += ' <span class="dot"></span>';
                }
                
                currentSeatClass = item.innerText;
                btnSeat.innerHTML = `💺 ${currentSeatClass} <span class="arrow">▼</span>`;
                menuSeat.classList.remove('custom-dropdown__menu--show');
            }
        });
    }

    if (btnPassenger && menuPassenger) {
        btnPassenger.addEventListener('click', (e) => {
            e.stopPropagation();
            if (menuSeat) menuSeat.classList.remove('custom-dropdown__menu--show');
            menuPassenger.classList.toggle('custom-dropdown__menu--show');
        });

        menuPassenger.addEventListener('click', (e) => {
            e.stopPropagation();
            const btn = e.target.closest('.passenger-btn');
            if (btn) {
                const type = btn.getAttribute('data-type');
                const isPlus = btn.classList.contains('plus');
                
                if (isPlus) {
                    passengers[type]++;
                } else {
                    if (passengers[type] > 0) {
                        if (type === 'adult' && passengers[type] <= 1) return;
                        passengers[type]--;
                    }
                }
                document.getElementById(`count-${type}`).innerText = passengers[type];
            }

            if (e.target.closest('.btn-confirm-passenger')) {
                const total = passengers.adult + passengers.child + passengers.infant;
                // Get translation text if available
                const passengerText = document.getElementById('btn-passenger').getAttribute('data-i18n-text') || 'Hành khách';
                btnPassenger.innerHTML = `👥 ${total} ${passengerText} <span class="arrow">▼</span>`;
                menuPassenger.classList.remove('custom-dropdown__menu--show');
            }
        });
    }

    // Handle Language change
    document.addEventListener('languageChanged', (e) => {
        const t = e.detail.dict;
        if (btnPassenger) {
            btnPassenger.setAttribute('data-i18n-text', t.passenger);
            const total = passengers.adult + passengers.child + passengers.infant;
            btnPassenger.innerHTML = `👥 ${total} ${t.passenger} <span class="arrow">▼</span>`;
        }
        if (btnSeat) {
            // Very simple update for seat class
            if (currentSeatClass === 'Phổ thông' || currentSeatClass === 'Economy') {
                currentSeatClass = t.seat_economy;
            } else if (currentSeatClass === 'Thương gia' || currentSeatClass === 'Business') {
                currentSeatClass = t.seat_business;
            }
            btnSeat.innerHTML = `💺 ${currentSeatClass} <span class="arrow">▼</span>`;
            
            // Update items
            const items = menuSeat.querySelectorAll('.custom-dropdown__item');
            if(items.length >= 2) {
                const hasDotEco = items[0].querySelector('.dot');
                const hasDotBus = items[1].querySelector('.dot');
                items[0].innerHTML = `${t.seat_economy} ${hasDotEco ? '<span class="dot"></span>' : ''}`;
                items[1].innerHTML = `${t.seat_business} ${hasDotBus ? '<span class="dot"></span>' : ''}`;
            }
        }
        
        // Update flight headers
        const headers = multicityForm ? multicityForm.querySelectorAll('.multicity-flight-header') : [];
        headers.forEach((header, i) => {
            header.innerText = (e.detail.lang === 'EN' ? 'FLIGHT ' : 'CHUYẾN BAY ') + (i + 1);
        });
    });

    // Hide dropdowns on click outside
    document.addEventListener('click', (e) => {
        const path = e.composedPath();
        if (menuSeat && !path.includes(btnSeat) && !path.includes(menuSeat)) {
            menuSeat.classList.remove('custom-dropdown__menu--show');
        }
        if (menuPassenger && !path.includes(btnPassenger) && !path.includes(menuPassenger)) {
            menuPassenger.classList.remove('custom-dropdown__menu--show');
        }
    });

    // ==========================================
    // MULTI-CITY LOGIC
    // ==========================================
    const multicityForm = document.getElementById('multicity-form');
    let flightCount = 2; // Khởi tạo với 2 chuyến bay

    const getMulticityRows = () => Array.from(multicityForm.querySelectorAll('.multicity-row'));

    const syncNextFlightOrigin = (toInput) => {
        const currentRow = toInput.closest('.multicity-row');
        const rows = getMulticityRows();
        const currentIndex = rows.indexOf(currentRow);

        if (currentIndex === -1 || currentIndex >= rows.length - 1) {
            return;
        }

        const nextFrom = rows[currentIndex + 1].querySelector('.m-from');
        if (nextFrom) {
            nextFrom.value = toInput.value;
            nextFrom.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const syncNewFlightOrigin = (newFlight) => {
        const rows = getMulticityRows();
        const newRow = newFlight.querySelector('.multicity-row');
        const newIndex = rows.indexOf(newRow);

        if (newIndex <= 0) {
            return;
        }

        const previousTo = rows[newIndex - 1].querySelector('.m-to');
        const newFrom = newRow.querySelector('.m-from');
        if (previousTo && newFrom) {
            newFrom.value = previousTo.value;
        }
    };

    const addFlightBtn = document.querySelector('.btn-add-flight');
    if (addFlightBtn && multicityForm) {
        addFlightBtn.addEventListener('click', () => {
            flightCount++;
            
            // Create new flight section
            const newFlight = document.createElement('div');
            newFlight.className = 'multicity-flight';
            newFlight.style.marginTop = '15px';
            
            newFlight.innerHTML = `
                <div class="multicity-flight-header">CHUYẾN BAY ${flightCount}</div>
                <div class="multicity-row multicity-row--removable">
                    <div class="search-widget__input-group" style="position: relative;">
                        <label class="search-widget__label">Điểm đi</label>
                        <input type="text" class="search-widget__input m-from" placeholder="Thành phố hoặc sân bay" autocomplete="off">
                    </div>
                    <button type="button" class="search-widget__swap-btn m-swap">⇄</button>
                    <div class="search-widget__input-group" style="position: relative;">
                        <label class="search-widget__label">Điểm đến</label>
                        <input type="text" class="search-widget__input m-to" placeholder="Thành phố hoặc sân bay" autocomplete="off">
                    </div>
                    <div class="search-widget__input-group multicity-date-group" style="position: relative;">
                        <label class="search-widget__label">Ngày đi</label>
                        <input type="text" class="search-widget__input m-date" placeholder="dd/mm/yyyy" readonly autocomplete="off" style="cursor: pointer;">
                    </div>
                    <button type="button" class="btn-remove-flight" style="color: red; font-size: 20px; cursor: pointer; padding: 0 15px;">×</button>
                </div>
            `;
            
            // Insert before the actions (Add btn / Submit btn)
            const actionsDiv = document.querySelector('.multicity-actions');
            multicityForm.insertBefore(newFlight, actionsDiv);
            syncNewFlightOrigin(newFlight);
            
            // Bind components to new inputs
            const fromInput = newFlight.querySelector('.m-from');
            const toInput = newFlight.querySelector('.m-to');
            const dateInput = newFlight.querySelector('.m-date');
            const swapBtn = newFlight.querySelector('.m-swap');
            const removeBtn = newFlight.querySelector('.btn-remove-flight');
            
            fromInput.addEventListener('input', formatAirportInput);
            toInput.addEventListener('input', formatAirportInput);
            
            bindLocationInput(fromInput);
            bindLocationInput(toInput);
            bindCalendarInput(dateInput);
            attachSwapLogic(swapBtn, fromInput, toInput);
            
            // Remove flight logic
            removeBtn.addEventListener('click', () => {
                newFlight.remove();
                // Update flight numbers
                flightCount = multicityForm.querySelectorAll('.multicity-row').length;
                const headers = multicityForm.querySelectorAll('.multicity-flight-header');
                headers.forEach((header, index) => {
                    header.innerText = `CHUYẾN BAY ${index + 1}`;
                });
            });
        });
        multicityForm.addEventListener('input', (e) => {
            if (e.target.classList.contains('m-to')) {
                syncNextFlightOrigin(e.target);
            }
        });

        multicityForm.addEventListener('change', (e) => {
            if (e.target.classList.contains('m-to')) {
                syncNextFlightOrigin(e.target);
            }
        });

        // Initialize existing rows
        const existingRows = multicityForm.querySelectorAll('.multicity-row');
        existingRows.forEach(row => {
            const swapBtn = row.querySelector('.m-swap');
            const fromInput = row.querySelector('.m-from');
            const toInput = row.querySelector('.m-to');
            if (swapBtn && fromInput && toInput) {
                attachSwapLogic(swapBtn, fromInput, toInput);
                fromInput.addEventListener('input', formatAirportInput);
                toInput.addEventListener('input', formatAirportInput);
            }
        });
    }

    // ==========================================
    // API FETCH LOGIC
    // ==========================================
    const searchFlights = async (flightsArray) => {
        try {
            // Hiển thị loading
            const loader = document.getElementById('global-loader');
            if (loader) {
                loader.style.display = 'flex';
                loader.classList.remove('loader-overlay--hidden');
            }

            // Lấy mã sân bay từ chuỗi (ví dụ "TP HCM (SGN)" -> "SGN")
            const extractCode = (str) => {
                const match = str.match(/\(([^)]+)\)/);
                return match ? match[1] : str;
            };

            const getAirlineLogo = (code) => {
                const normalizedCode = String(code || '').toLowerCase();
                const logos = {
                    vn: './assets/logos/airline-vn.png',
                    vj: './assets/logos/airline-vj.png',
                    qh: './assets/logos/airline-qh.png',
                    vu: './assets/logos/airline-vu.png'
                };

                return logos[normalizedCode] || '';
            };

            const allResults = [];

            for (let i = 0; i < flightsArray.length; i++) {
                const f = flightsArray[i];
                const originCode = extractCode(f.from);
                const destCode = extractCode(f.to);

                const url = `http://localhost:5000/api/flights/search`;
                console.log(`Đang gọi API Node.js Chuyến ${i + 1}: ${url}`);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        from: originCode,
                        to: destCode,
                        departDate: f.date,
                        tripType: 'oneway'
                    }),
                    mode: 'cors'
                });

                if (!response.ok) {
                    throw new Error(`Không thể tìm chuyến bay. Mã lỗi: ${response.status}`);
                }

                const resData = await response.json();
                const formattedData = {
                    status: resData.success ? 'success' : 'error',
                    source: 'Bayou OTA System',
                    request: {
                        origin: originCode,
                        destination: destCode,
                        date: f.date
                    },
                    data: (resData.data?.outbound || []).map(flight => ({
                        AirlineName: flight.airline,
                        AirlineCode: flight.airline_code,
                        FlightNumber: flight.flight_number,
                        SeatClass: currentSeatClass,
                        DepartTime: new Date(flight.departure_time).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                        ArriveTime: new Date(flight.arrival_time).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                        TotalPrice: flight.price,
                        PriceDisplay: flight.price_formatted || `${Number(flight.price || 0).toLocaleString('vi-VN')} ${flight.currency || ''}`.trim(),
                        Logo: flight.airline_logo || getAirlineLogo(flight.airline_code)
                    }))
                };

                allResults.push(formattedData);
            }
            
            // Xóa loading overlay
            if (loader) {
                loader.classList.add('loader-overlay--hidden');
                setTimeout(() => loader.style.display = 'none', 500);
            }
            
            // Hiển thị ra màn hình
            renderFlightResults(allResults);
        } catch (error) {
            console.error("Lỗi khi lấy dữ liệu vé:", error);
            alert("Không thể tìm chuyến bay lúc này. Vui lòng thử lại sau.");
            const loader = document.getElementById('global-loader');
            if (loader) {
                loader.classList.add('loader-overlay--hidden');
                setTimeout(() => loader.style.display = 'none', 500);
            }
        }
    };

    // Hàm render giao diện vé máy bay
    const renderFlightResults = (resultsArray) => {
        let container = document.getElementById('flight-results-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'flight-results-container';
            container.className = 'container';
            container.style.marginTop = '40px';
            container.style.marginBottom = '40px';
            container.style.padding = '20px';
            container.style.backgroundColor = '#fff';
            container.style.borderRadius = '8px';
            container.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            
            // Chèn ngay dưới khu vực search widget (Hero container)
            const hero = document.getElementById('hero-container');
            if(hero && hero.parentNode) {
                hero.parentNode.insertBefore(container, hero.nextSibling);
            }
        }
        
        let html = '';
        
        resultsArray.forEach((resData, index) => {
            const titlePrefix = resultsArray.length > 1 ? `Chuyến ${index + 1}: ` : '';
            const marginTop = index > 0 ? '40px' : '0';

            if (resData.status === 'success' && resData.data && resData.data.length > 0) {
                const title = `<h2 style="margin-bottom: 20px; color: #005bc5; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: ${marginTop};">
                    ${titlePrefix}Kết quả tìm kiếm: ${resData.request.origin} ✈ ${resData.request.destination} 
                    <span style="font-size: 14px; font-weight: normal; color: #888; margin-left: 10px;">(Ngày đi: ${resData.request.date} - Nguồn: ${resData.source})</span>
                </h2>`;
                
                html += title + '<div style="display:flex; flex-direction:column; gap:15px;">';
                resData.data.forEach(f => {
                    const logoHtml = f.Logo
                        ? `<img src="${f.Logo}" alt="${f.AirlineName}" style="width: 96px; height: 48px; object-fit: contain; display: block;">`
                        : `<div style="width: 96px; height: 48px; border-radius: 10px; background: #eef4ff; color: #005bc5; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px;">${f.AirlineCode || 'AIR'}</div>`;

                    html += `
                    <div class="flight-card" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background: #fff; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 15px rgba(0,91,197,0.15)'; this.style.borderColor='#005bc5';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.05)'; this.style.borderColor='#ddd';">
                        <div style="display: flex; align-items: center; gap: 15px; width: 30%;">
                            <div style="width: 112px; min-width: 112px; display: flex; align-items: center; justify-content: center;">${logoHtml}</div>
                            <div>
                                <strong style="display: block; font-size: 16px; color: #333;">${f.AirlineName} (${f.AirlineCode})</strong>
                                <span style="color: #666; font-size: 13px;">Chuyến bay: <b>${f.FlightNumber}</b> • Hạng: ${f.SeatClass}</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; justify-content: center; width: 40%; gap: 20px;">
                            <div style="text-align: center;">
                                <strong style="font-size: 22px; color: #333;">${f.DepartTime}</strong>
                                <div style="color: #999; font-size: 12px; font-weight: bold;">${resData.request.origin}</div>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; align-items: center; width: 100px;">
                                <span style="color: #888; font-size: 11px; margin-bottom: 2px;">Bay thẳng</span>
                                <div style="width: 100%; height: 1px; background: #ccc; position: relative;">
                                    <span style="position: absolute; right: -5px; top: -5px; color: #ccc;">✈</span>
                                </div>
                            </div>
    
                            <div style="text-align: center;">
                                <strong style="font-size: 22px; color: #333;">${f.ArriveTime}</strong>
                                <div style="color: #999; font-size: 12px; font-weight: bold;">${resData.request.destination}</div>
                            </div>
                        </div>
    
                        <div style="text-align: right; width: 30%;">
                            <div style="font-size: 24px; font-weight: bold; color: #ff6b00;">${f.PriceDisplay || `${f.TotalPrice.toLocaleString('vi-VN')} VND`}</div>
                            <div style="color: #888; font-size: 11px; margin-bottom: 8px;">Đã bao gồm thuế phí</div>
                            <button class="btn-select-flight" data-flight='${JSON.stringify(f)}' style="background: #ff6b00; color: #fff; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; text-transform: uppercase; transition: background 0.2s;" onmouseover="this.style.background='#e65c00'" onmouseout="this.style.background='#ff6b00'">Chọn Vé</button>
                        </div>
                    </div>`;
                });
                html += '</div>';
                
            } else {
                html += `<div style="text-align:center; padding: 40px; margin-top: ${marginTop}; background: #fdfdfd; border-radius: 8px; border: 1px solid #eee;">
                    <h2 style="color: #ff3333; margin-bottom: 10px;">${titlePrefix}Không tìm thấy chuyến bay phù hợp!</h2>
                    <p style="color: #666;">Không có chuyến bay từ ${resData.request?.origin || 'điểm đi'} đến ${resData.request?.destination || 'điểm đến'}.</p>
                </div>`;
            }
        });
        
        container.innerHTML = html;

        // Cuộn xuống để xem kết quả
        setTimeout(() => {
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
        
        // Đăng ký sự kiện cho các nút "Chọn Vé"
        document.querySelectorAll('.btn-select-flight').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const flightData = JSON.parse(e.target.dataset.flight);
                openBookingModal(flightData);
            });
        });
    };

    // Hàm mở Modal đặt vé
    const openBookingModal = (flight) => {
        const modal = document.createElement('div');
        modal.id = 'booking-modal';
        modal.style = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 9999;';
        
        modal.innerHTML = `
            <div style="background: #fff; padding: 30px; border-radius: 12px; width: 500px; max-width: 90%; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                <span id="close-modal" style="position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #888;">&times;</span>
                <h3 style="margin-bottom: 20px; color: #005bc5;">Xác nhận đặt vé: ${flight.AirlineName}</h3>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p><b>Chuyến bay:</b> ${flight.FlightNumber}</p>
                    <p><b>Hành trình:</b> ${flight.DepartTime} ➔ ${flight.ArriveTime}</p>
                    <p><b>Giá tổng:</b> <span style="color: #ff6b00; font-weight: bold; font-size: 18px;">${flight.PriceDisplay || `${flight.TotalPrice.toLocaleString('vi-VN')} VND`}</span></p>
                </div>
                
                <form id="form-booking-submit">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Họ và tên hành khách:</label>
                    <input type="text" id="passenger-name" required style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Ví dụ: NGUYEN VAN A">
                    
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Số điện thoại liên hệ:</label>
                    <input type="tel" id="contact-phone" required style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px;" placeholder="09xxxxxxxx">
                    
                    <button type="submit" style="width: 100%; background: #005bc5; color: #fff; border: none; padding: 12px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer;">XÁC NHẬN ĐẶT CHỖ</button>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Đóng modal
        document.getElementById('close-modal').onclick = () => modal.remove();
        modal.onclick = (e) => { if(e.target === modal) modal.remove(); };
        
        // Xử lý submit đặt vé
        document.getElementById('form-booking-submit').onsubmit = async (e) => {
            e.preventDefault();
            const passengerName = document.getElementById('passenger-name').value;
            const phone = document.getElementById('contact-phone').value;
            
            const btn = e.target.querySelector('button');
            btn.disabled = true;
            btn.innerText = 'Đang xử lý...';
            
            try {
                const response = await fetch('http://localhost:5000/api-bayou/book_ticket.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        flight_id: flight.FlightNumber,
                        total_price: flight.TotalPrice,
                        passengers: [{ name: passengerName }],
                        contact: { phone: phone }
                    })
                });
                
                const result = await response.json();
                if (result.status === 'success') {
                    modal.innerHTML = `
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 50px; color: #28a745; margin-bottom: 15px;">✓</div>
                            <h3 style="color: #28a745;">ĐẶT VÉ THÀNH CÔNG!</h3>
                            <p style="margin: 15px 0;">Mã đặt chỗ (PNR) của bạn là: <br> <b style="font-size: 32px; color: #005bc5; letter-spacing: 2px;">${result.pnr}</b></p>
                            <p style="color: #666; font-size: 14px;">Vui lòng lưu lại mã này để tra cứu trạng thái chuyến bay.</p>
                            <button onclick="location.reload()" style="margin-top: 20px; background: #005bc5; color: #fff; border: none; padding: 10px 25px; border-radius: 4px; cursor: pointer;">ĐÓNG</button>
                        </div>
                    `;
                }
            } catch (err) {
                alert('Có lỗi xảy ra khi đặt vé, vui lòng thử lại!');
                btn.disabled = false;
                btn.innerText = 'XÁC NHẬN ĐẶT CHỖ';
            }
        };
    };

    // ==========================================
    // FORM VALIDATION & SUBMIT
    // ==========================================
    const submitHandler = async (e, formType) => {
        e.preventDefault();
        const tripType = document.querySelector('.search-widget__tab--active').dataset.trip;
        const totalPassenger = passengers.adult + passengers.child + passengers.infant;

        const searchData = {
            tripType: tripType,
            seatClassName: currentSeatClass,
            passengerCount: totalPassenger,
            flights: []
        };

        if (formType === 'main') {
            const from = inputFrom ? inputFrom.value.trim() : '';
            const to = inputTo ? inputTo.value.trim() : '';
            const depart = inputDepart ? inputDepart.value : '';
            const isOneway = tripType === 'oneway';
            const returnDate = inputReturn && !isOneway ? inputReturn.value : '';

            if (!from || !to) { alert('Vui lòng nhập đầy đủ Điểm đi và Điểm đến!'); return; }
            if (from.toLowerCase() === to.toLowerCase()) { alert('Điểm đi và Điểm đến không được trùng nhau!'); return; }
            if (!depart) { alert('Vui lòng chọn Ngày đi!'); return; }
            if (!isOneway && !returnDate) { alert('Vui lòng chọn Ngày về!'); return; }

            searchData.flights.push({ from, to, date: depart });
            if (!isOneway) {
                searchData.flights.push({ from: to, to: from, date: returnDate }); // return flight
            }
        } else if (formType === 'multicity') {
            const rows = multicityForm.querySelectorAll('.multicity-row');
            let hasError = false;
            
            const parseDate = (dateStr) => {
                const parts = dateStr.split('/');
                if (parts.length === 3) return new Date(parts[2], parts[1] - 1, parts[0]);
                return null;
            };

            let previousDate = null;

            rows.forEach((row, index) => {
                const inputs = row.querySelectorAll('input');
                const from = inputs[0].value.trim();
                const to = inputs[1].value.trim();
                const date = inputs[2].value.trim();
                
                if (!from || !to || !date) {
                    alert(`Vui lòng nhập đầy đủ thông tin cho chuyến bay ${index + 1}!`);
                    hasError = true;
                    return;
                }
                if (from.toLowerCase() === to.toLowerCase()) {
                    alert(`Điểm đi và Điểm đến không được trùng nhau ở chuyến bay ${index + 1}!`);
                    hasError = true;
                    return;
                }
                
                const currentDate = parseDate(date);
                if (previousDate && currentDate && currentDate < previousDate) {
                    alert(`Ngày đi của chuyến bay ${index + 1} không được sớm hơn ngày đi của chuyến bay ${index}!`);
                    hasError = true;
                    return;
                }
                previousDate = currentDate;

                searchData.flights.push({ from, to, date });
            });
            
            if (hasError) return;
        }

        console.log("✈️ Flight Search Data (Giao diện):", searchData);
        
        // Gọi API Backend cho các chuyến bay (Khứ hồi hoặc Đa chặng sẽ gọi nhiều lần)
        if (searchData.flights.length > 0) {
            await searchFlights(searchData.flights);
        }
    };

    const mainForm = document.getElementById('flight-search-form');
    if (mainForm) mainForm.addEventListener('submit', (e) => submitHandler(e, 'main'));
    if (multicityForm) multicityForm.addEventListener('submit', (e) => submitHandler(e, 'multicity'));
}

import { popupContentData } from './data.js';

let locationPopup = null;
let calendarPopup = null;
let activeLocationInput = null;
let activeInputForCalendar = null;
let currentDate = new Date();

export function bindLocationInput(input) {
    if (!locationPopup) locationPopup = document.getElementById('popup-from');
    if (!locationPopup) return;
    
    // Prevent multiple bindings
    if (input.dataset.boundLocation) return;
    input.dataset.boundLocation = "true";

    input.addEventListener('focus', (e) => {
        e.stopPropagation();
        activeLocationInput = input;
        input.parentNode.appendChild(locationPopup);
        locationPopup.classList.add('location-popup--show');
    });
    input.addEventListener('click', (e) => {
        e.stopPropagation();
        activeLocationInput = input;
        input.parentNode.appendChild(locationPopup);
        locationPopup.classList.add('location-popup--show');
    });
}

export function bindCalendarInput(input) {
    if (!calendarPopup) calendarPopup = document.getElementById('calendar-popup');
    if (!calendarPopup) return;

    if (input.dataset.boundCalendar) return;
    input.dataset.boundCalendar = "true";

    input.type = "text";
    input.placeholder = "dd/mm/yyyy";
    input.readOnly = true;
    input.style.cursor = "pointer";
    input.addEventListener('click', (e) => {
        e.stopPropagation();
        activeInputForCalendar = input;
        input.parentNode.appendChild(calendarPopup); 
        calendarPopup.classList.add('custom-calendar-popup--show');
        renderCalendar();
    });
}

const renderLocationPopupContent = (activeTabName) => {
    const htmlContent = popupContentData[activeTabName];
    let tabsHtml = Object.keys(popupContentData).map(tab => 
        `<button type="button" class="location-popup__tab ${tab === activeTabName ? 'location-popup__tab--active' : ''}" data-tab="${tab}">${tab}</button>`
    ).join('');

    if (locationPopup) {
        locationPopup.innerHTML = `
            <div class="location-popup__tabs">${tabsHtml}</div>
            <div class="location-popup__body">
                ${htmlContent}
            </div>
        `;
    }
};

const renderCalendar = () => {
    if (!calendarPopup || !document.getElementById('calendar-left')) return;
    const leftMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const rightMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
    
    document.getElementById('calendar-left').innerHTML = generateMonthHTML(leftMonth, true);
    document.getElementById('calendar-right').innerHTML = generateMonthHTML(rightMonth, false);
};

const generateMonthHTML = (date, isLeft) => {
    const monthNames = ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"];
    const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    const firstDayIndex = new Date(date.getFullYear(), date.getMonth(), 1).getDay(); 
    
    let html = `
        <div class="calendar-header">
            ${isLeft ? `<button type="button" id="cal-prev">&lt;</button>` : `<div></div>`}
            <div class="calendar-title">${monthNames[date.getMonth()]} ${date.getFullYear()}</div>
            ${!isLeft ? `<button type="button" id="cal-next">&gt;</button>` : `<div></div>`}
        </div>
        <div class="calendar-grid">
            <div class="calendar-day-name">CN</div><div class="calendar-day-name">T2</div><div class="calendar-day-name">T3</div>
            <div class="calendar-day-name">T4</div><div class="calendar-day-name">T5</div><div class="calendar-day-name">T6</div><div class="calendar-day-name">T7</div>
    `;
    
    for(let i = 0; i < firstDayIndex; i++) {
        html += `<div class="calendar-day calendar-day--empty"></div>`;
    }
    
    const today = new Date();
    today.setHours(0,0,0,0);
    
    let selectedDateStr = null;
    if (activeInputForCalendar && activeInputForCalendar.value) {
        const parts = activeInputForCalendar.value.split('/');
        if (parts.length === 3) {
            selectedDateStr = `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
    }
    
    for(let i = 1; i <= daysInMonth; i++) {
        const currentDay = new Date(date.getFullYear(), date.getMonth(), i);
        let classes = "calendar-day";
        
        if (currentDay < today) classes += " calendar-day--disabled";
        
        const m = String(currentDay.getMonth() + 1).padStart(2, '0');
        const d = String(currentDay.getDate()).padStart(2, '0');
        const dateStr = `${currentDay.getFullYear()}-${m}-${d}`;
        
        if (selectedDateStr === dateStr) classes += " calendar-day--selected";
        
        let priceHtml = '';
        if (currentDay >= today) {
            // Generate a deterministic pseudo-random price based on date
            const seed = currentDay.getDate() * (currentDay.getMonth() + 1) * 789;
            const price = Math.floor((seed % 20) * 100) + 600; // 600k to 2500k
            priceHtml = `<div style="font-size: 9px; color: #ff6b00; margin-top: 2px; font-weight: bold;">${price}k</div>`;
        }
        
        html += `<div class="${classes}" data-date="${dateStr}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;">
            <div style="line-height: 1;">${i}</div>
            ${priceHtml}
        </div>`;
    }
    html += `</div>`;
    return html;
};

export function initPopups() {
    locationPopup = document.getElementById('popup-from'); 
    calendarPopup = document.getElementById('calendar-popup');
    if (document.getElementById('popup-to')) document.getElementById('popup-to').remove(); 

    if (locationPopup) {
        renderLocationPopupContent('NỘI ĐỊA');

        locationPopup.addEventListener('click', (e) => {
            e.stopPropagation();
            const tabBtn = e.target.closest('.location-popup__tab');
            if (tabBtn) {
                renderLocationPopupContent(tabBtn.getAttribute('data-tab'));
                return;
            }
            const itemBtn = e.target.closest('.location-popup__item');
            if (itemBtn && activeLocationInput) {
                activeLocationInput.value = itemBtn.innerText;
                locationPopup.classList.remove('location-popup--show');
                activeLocationInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

    if (calendarPopup) {
        calendarPopup.addEventListener('click', (e) => {
            e.stopPropagation();
            if (e.target.id === 'cal-prev') {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            } else if (e.target.id === 'cal-next') {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            } else if (e.target.classList.contains('calendar-day') && !e.target.classList.contains('calendar-day--disabled') && !e.target.classList.contains('calendar-day--empty')) {
                const clickedDate = e.target.getAttribute('data-date');
                if (activeInputForCalendar) {
                    activeInputForCalendar.value = clickedDate.split('-').reverse().join('/');
                    renderCalendar(); 
                }
            }
        });
        
        const btnCalendarConfirm = document.getElementById('btn-calendar-confirm');
        if (btnCalendarConfirm) {
            btnCalendarConfirm.addEventListener('click', (e) => {
                e.stopPropagation();
                calendarPopup.classList.remove('custom-calendar-popup--show');
            });
        }
    }

    // Bind existing inputs initially
    const locationInputs = document.querySelectorAll('#input-from, #input-to, .multicity-row input[placeholder="Thành phố hoặc sân bay"]');
    locationInputs.forEach(bindLocationInput);

    const dateInputs = document.querySelectorAll('#input-depart, #input-return, .multicity-row input[placeholder="dd/mm/yyyy"], .multicity-row input[type="date"]');
    dateInputs.forEach(bindCalendarInput);

    // Hide Popups on click outside
    document.addEventListener('click', (e) => {
        const path = e.composedPath();
        if (locationPopup && !path.includes(locationPopup) && (!activeLocationInput || !path.includes(activeLocationInput))) {
            locationPopup.classList.remove('location-popup--show');
        }
        
        if (calendarPopup && !path.includes(calendarPopup) && (!activeInputForCalendar || !path.includes(activeInputForCalendar))) {
            calendarPopup.classList.remove('custom-calendar-popup--show');
        }
    });
}

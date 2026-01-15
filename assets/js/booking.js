// booking.js - Complete updated version with better time picker

function showAlert(message, type = "warning") {
    const box = document.getElementById("formAlert");
    if (!box) {
        alert(message);
        return;
    }

    box.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  `;
}

const ROUND_TRIP_MULTIPLIER = 1.8;
const YES_INSURANCE_EXTRA = 2000;

// Predefined time slots for better UX
const TIME_SLOTS = [
    "09:00", "09:30", "10:00", "10:30", 
    "11:00", "11:30", "12:00", "12:30",
    "13:00", "13:30", "14:00", "14:30",
    "15:00", "15:30", "16:00", "16:30",
    "17:00", "17:30", "18:00"
];

const els = {
    aircraftSelect: document.getElementById('aircraftSelect'),
    previewContainer: document.getElementById('aircraftPreview'),
    previewImg: document.getElementById('previewImage'),
    previewName: document.getElementById('previewName'),
    previewCapacity: document.getElementById('previewCapacity'),
    basePriceDisplay: document.getElementById('basePriceDisplay'),
    passengersInput: document.getElementById('passengers'),
    maxPassengersSpan: document.getElementById('maxPassengers'),
    totalPriceEl: document.getElementById('totalPrice'),
    dynamicPassengers: document.getElementById('dynamicPassengers')
};

let currentAircraft = null;
let datePicker = null;
let returnPicker = null;
let timePicker = null;
let returnTimePicker = null;

// -------------------- Enhanced Time Picker --------------------
function initializeTimePicker(inputId, isReturn = false) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    // Create time suggestions container
    const suggestionsId = isReturn ? 'returnTimeSuggestions' : 'timeSuggestions';
    let suggestionsContainer = document.getElementById(suggestionsId);
    if (!suggestionsContainer) {
        suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'time-suggestions';
        suggestionsContainer.id = suggestionsId;
        input.parentNode.appendChild(suggestionsContainer);
    }
    
    // Populate time suggestions
    suggestionsContainer.innerHTML = TIME_SLOTS.map(time => {
        const displayTime = formatTimeForDisplay(time);
        return `<div class="time-suggestion" data-time="${time}">${displayTime}</div>`;
    }).join('');
    
    // Add click handlers for time suggestions
    suggestionsContainer.querySelectorAll('.time-suggestion').forEach(suggestion => {
        suggestion.addEventListener('click', function() {
            const selectedTime = this.getAttribute('data-time');
            input.value = formatTimeForDisplay(selectedTime);
            suggestionsContainer.style.display = 'none';
            
            // Highlight selected
            suggestionsContainer.querySelectorAll('.time-suggestion').forEach(s => {
                s.classList.remove('active');
            });
            this.classList.add('active');
            
            updateTotalPrice();
        });
    });
    
    // Show/hide suggestions on focus/blur
    input.addEventListener('focus', function() {
        suggestionsContainer.style.display = 'block';
    });
    
    input.addEventListener('blur', function(e) {
        // Delay hiding to allow click on suggestions
        setTimeout(() => {
            if (!suggestionsContainer.contains(document.activeElement)) {
                suggestionsContainer.style.display = 'none';
            }
        }, 200);
    });
    
    // Also use flatpickr for mobile compatibility
    return flatpickr(`#${inputId}`, {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: false,
        minuteIncrement: 30,
        minTime: "09:00",
        maxTime: "18:00",
        onChange: function(selectedDates, dateStr, instance) {
            updateTotalPrice();
            // Also update suggestions highlight
            const time24 = dateStr;
            suggestionsContainer.querySelectorAll('.time-suggestion').forEach(s => {
                s.classList.remove('active');
                if (s.getAttribute('data-time') === time24) {
                    s.classList.add('active');
                }
            });
        },
        onOpen: function(selectedDates, dateStr, instance) {
            // Hide custom suggestions when flatpickr opens
            suggestionsContainer.style.display = 'none';
        }
    });
}

function formatTimeForDisplay(time24) {
    const [hours, minutes] = time24.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

// -------------------- Date Picker with Past Dates --------------------
function initializeDatePicker(selectedAircraftId = null) {
    const dateInput = document.getElementById("datePicker");
    
    if (!dateInput) return;
    
    // Get today's date
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    
    // Get booked dates for the selected aircraft
    let disabledDates = [];
    if (selectedAircraftId && bookedDates[selectedAircraftId]) {
        disabledDates = bookedDates[selectedAircraftId];
    }
    
    // If datePicker already exists, destroy it first
    if (datePicker) {
        datePicker.destroy();
    }
    
    datePicker = flatpickr("#datePicker", {
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            updateTotalPrice();
            
            // If return date picker exists and is visible, update its min date
            if (document.getElementById('returnDateContainer').style.display !== 'none') {
                const returnPickerEl = document.getElementById('returnDatePicker');
                if (returnPickerEl && returnPicker) {
                    returnPicker.set('minDate', dateStr);
                    
                    // If return date is before new min date, clear it
                    if (returnPickerEl.value && new Date(returnPickerEl.value) < new Date(dateStr)) {
                        returnPickerEl.value = '';
                    }
                }
            }
        },
        disable: disabledDates,
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            // Color coding for dates
            const date = dayElem.dateObj;
            const dateStr = date.toISOString().split('T')[0];
            
            // Mark past dates
            if (date < today) {
                dayElem.classList.add('past');
                dayElem.title = "Past date - Not available";
                return;
            }
            
            if (selectedAircraftId && bookedDates[selectedAircraftId]) {
                if (bookedDates[selectedAircraftId].includes(dateStr)) {
                    // Mark as unavailable (red)
                    dayElem.classList.add('booked');
                    dayElem.title = "Aircraft already booked on this date";
                } else {
                    // Mark as available (green)
                    dayElem.classList.add('available');
                    dayElem.title = "Available";
                }
            } else if (date >= today) {
                // Mark as available if no aircraft selected yet
                dayElem.classList.add('available');
                dayElem.title = "Available";
            }
        }
    });
}

function initializeReturnDatePicker(selectedAircraftId = null) {
    const returnDateInput = document.getElementById("returnDatePicker");
    
    if (!returnDateInput) return;
    
    // Get today's date
    const today = new Date();
    
    let disabledDates = [];
    if (selectedAircraftId && bookedDates[selectedAircraftId]) {
        disabledDates = bookedDates[selectedAircraftId];
    }
    
    if (returnPicker) {
        returnPicker.destroy();
    }
    
    returnPicker = flatpickr("#returnDatePicker", {
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: updateTotalPrice,
        disable: disabledDates,
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const date = dayElem.dateObj;
            const dateStr = date.toISOString().split('T')[0];
            
            // Mark past dates
            if (date < today) {
                dayElem.classList.add('past');
                dayElem.title = "Past date - Not available";
                return;
            }
            
            if (selectedAircraftId && bookedDates[selectedAircraftId]) {
                if (bookedDates[selectedAircraftId].includes(dateStr)) {
                    dayElem.classList.add('booked');
                    dayElem.title = "Aircraft already booked on this date";
                } else {
                    dayElem.classList.add('available');
                    dayElem.title = "Available";
                }
            } else if (date >= today) {
                dayElem.classList.add('available');
                dayElem.title = "Available";
            }
        }
    });
}

// -------------------- Trip Type Toggle --------------------
document.querySelectorAll('input[name="trip_type"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const isRound = document.getElementById('roundtrip').checked;
        document.getElementById('returnDateContainer').style.display = isRound ? 'block' : 'none';
        document.getElementById('returnTimeContainer').style.display = isRound ? 'block' : 'none';

        document.getElementById('returnDatePicker').required = isRound;
        document.getElementById('returnTimePicker').required = isRound;

        if (!isRound) {
            document.getElementById('returnDatePicker').value = '';
            document.getElementById('returnTimePicker').value = '';
        } else {
            // Get selected aircraft ID
            const selectedId = els.aircraftSelect.value;
            initializeReturnDatePicker(selectedId);
            
            // Initialize return time picker if not already initialized
            if (!returnTimePicker) {
                returnTimePicker = initializeTimePicker('returnTimePicker', true);
            }
            
            // Set min date to departure date
            const depDate = document.getElementById('datePicker').value;
            if (depDate && returnPicker) {
                returnPicker.set('minDate', depDate);
            }
        }

        updateTotalPrice();
    });
});

// -------------------- Aircraft Selection --------------------
els.aircraftSelect.addEventListener('change', function () {
    const selectedId = this.value;
    currentAircraft = aircraftData[selectedId] || null;

    if (!currentAircraft) {
        els.previewContainer.style.display = 'none';
        // Reset date picker to show all dates as available
        initializeDatePicker(null);
        return;
    }

    els.previewImg.src = currentAircraft.image_path;
    els.previewImg.alt = currentAircraft.name;
    els.previewName.textContent = currentAircraft.name;
    els.previewCapacity.textContent = currentAircraft.capacity;
    els.basePriceDisplay.textContent = '₱' + currentAircraft.price.toLocaleString('en-PH');

    els.maxPassengersSpan.textContent = currentAircraft.capacity;
    els.passengersInput.max = currentAircraft.capacity;

    if (parseInt(els.passengersInput.value) > currentAircraft.capacity) {
        els.passengersInput.value = currentAircraft.capacity;
    }

    els.previewContainer.style.display = 'block';

    // Reinitialize date picker with this aircraft's booked dates
    initializeDatePicker(selectedId);
    
    // Also update return date picker if it's visible
    if (document.getElementById('roundtrip').checked) {
        initializeReturnDatePicker(selectedId);
    }

    generatePassengerFields();
    updateTotalPrice();
});

// -------------------- Passengers Input --------------------
els.passengersInput.addEventListener('input', function () {
    if (!currentAircraft) return;
    const max = parseInt(currentAircraft.capacity);
    let value = parseInt(this.value) || 1;

    if (value > max) value = max;
    if (value < 1) value = 1;

    this.value = value;
    generatePassengerFields();
});

// -------------------- Validate Passengers --------------------
function validatePassengers() {
    const fNames = document.querySelectorAll('[name="passenger_f_name[]"]');
    const lNames = document.querySelectorAll('[name="passenger_l_name[]"]');
    const emails = document.querySelectorAll('[name="passenger_email[]"]');
    const phones = document.querySelectorAll('[name="passenger_phone[]"]');

    // Check if passenger fields exist
    if (fNames.length === 0) {
        showAlert("Please select an aircraft and number of passengers first", "warning");
        return false;
    }

    // Clear previous validation
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });

    for (let i = 0; i < fNames.length; i++) {
        const fn = fNames[i].value.trim();
        const ln = lNames[i].value.trim();
        const em = emails[i] ? emails[i].value.trim() : '';
        const ph = phones[i] ? phones[i].value.trim() : '';

        // Validate firstname
        if (!fn) {
            showAlert(`Passenger ${i + 1}: First name is required`, 'warning');
            fNames[i].classList.add('is-invalid');
            fNames[i].focus();
            return false;
        }

        if (fn.length < 2) {
            showAlert(`Passenger ${i + 1}: First name must be at least 2 characters`, 'warning');
            fNames[i].classList.add('is-invalid');
            fNames[i].focus();
            return false;
        }

        if (!/^[A-Za-z\s]+$/.test(fn)) {
            showAlert(`Passenger ${i + 1}: First name can only contain letters and spaces`, 'warning');
            fNames[i].classList.add('is-invalid');
            fNames[i].focus();
            return false;
        }

        // Validate lastname
        if (!ln) {
            showAlert(`Passenger ${i + 1}: Last name is required`, 'warning');
            lNames[i].classList.add('is-invalid');
            lNames[i].focus();
            return false;
        }

        if (ln.length < 2) {
            showAlert(`Passenger ${i + 1}: Last name must be at least 2 characters`, 'warning');
            lNames[i].classList.add('is-invalid');
            lNames[i].focus();
            return false;
        }

        if (!/^[A-Za-z\s]+$/.test(ln)) {
            showAlert(`Passenger ${i + 1}: Last name can only contain letters and spaces`, 'warning');
            lNames[i].classList.add('is-invalid');
            lNames[i].focus();
            return false;
        }

        // For primary passenger (index 0)
        if (i === 0) {
            // Validate email
            if (!em) {
                showAlert('Primary passenger: Email is required', 'warning');
                emails[i].classList.add('is-invalid');
                emails[i].focus();
                return false;
            }

            // Validate email format
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(em)) {
                showAlert('Primary passenger: Please enter a valid email address format', 'warning');
                emails[i].classList.add('is-invalid');
                emails[i].focus();
                return false;
            }

            // Validate Gmail
            const emailLower = em.toLowerCase();
            if (!emailLower.endsWith('@gmail.com')) {
                showAlert('Primary passenger: Please enter a valid Gmail address (must end with @gmail.com)', 'warning');
                emails[i].classList.add('is-invalid');
                emails[i].focus();
                return false;
            }

            // Validate phone
            if (!ph) {
                showAlert('Primary passenger: Phone number is required', 'warning');
                phones[i].classList.add('is-invalid');
                phones[i].focus();
                return false;
            }

            // Validate phone format (clean it first)
            const phoneClean = ph.replace(/\D/g, '');
            if (!/^09[0-9]{9}$/.test(phoneClean)) {
                showAlert('Primary passenger: Please enter a valid 11-digit Philippine phone number (09XXXXXXXXX)', 'warning');
                phones[i].classList.add('is-invalid');
                phones[i].focus();
                return false;
            }
        }
    }

    return true;
}

// -------------------- Check Aircraft Availability --------------------
function validateBookingDate() {
    const selectedAircraft = els.aircraftSelect.value;
    const selectedDate = document.getElementById('datePicker').value;
    const selectedTime = document.getElementById('timePicker').value;
    
    if (!selectedAircraft) {
        showAlert("Please select an aircraft first", "warning");
        els.aircraftSelect.focus();
        return false;
    }
    
    if (!selectedDate) {
        showAlert("Please select a departure date", "warning");
        document.getElementById('datePicker').focus();
        return false;
    }
    
    if (!selectedTime) {
        showAlert("Please select a departure time", "warning");
        document.getElementById('timePicker').focus();
        return false;
    }
    
    // Check if date is in the past
    const today = new Date();
    const selectedDateTime = new Date(selectedDate + ' ' + selectedTime);
    if (selectedDateTime < today) {
        showAlert("Cannot book in the past. Please select a future date and time.", 'warning');
        return false;
    }
    
    // Check if date is in bookedDates for this aircraft
    if (bookedDates[selectedAircraft] && bookedDates[selectedAircraft].includes(selectedDate)) {
        showAlert(`The selected aircraft is already booked on ${selectedDate}. Please choose a different date.`, 'warning');
        document.getElementById('datePicker').focus();
        return false;
    }
    
    // For round trip, also check return date
    if (document.getElementById('roundtrip').checked) {
        const returnDate = document.getElementById('returnDatePicker').value;
        const returnTime = document.getElementById('returnTimePicker').value;
        
        if (!returnDate) {
            showAlert("Please select a return date for round trip", "warning");
            document.getElementById('returnDatePicker').focus();
            return false;
        }
        
        if (!returnTime) {
            showAlert("Please select a return time for round trip", "warning");
            document.getElementById('returnTimePicker').focus();
            return false;
        }
        
        // Check if return date is in the past
        const returnDateTime = new Date(returnDate + ' ' + returnTime);
        if (returnDateTime < today) {
            showAlert("Return cannot be in the past. Please select a future date and time.", 'warning');
            return false;
        }
        
        if (bookedDates[selectedAircraft] && bookedDates[selectedAircraft].includes(returnDate)) {
            showAlert(`The selected aircraft is already booked on return date ${returnDate}. Please choose a different date.`, 'warning');
            document.getElementById('returnDatePicker').focus();
            return false;
        }
        
        // Check if return date is after departure date
        if (returnDateTime <= selectedDateTime) {
            showAlert("Return must be after departure", "warning");
            document.getElementById('returnDatePicker').focus();
            return false;
        }
    }
    
    return true;
}

// -------------------- Update Total Price --------------------
function updateTotalPrice() {
    if (!currentAircraft) {
        els.totalPriceEl.textContent = '₱0';
        return;
    }

    const pax = Math.max(1, parseInt(els.passengersInput.value) || 1);
    const isRound = document.getElementById('roundtrip').checked;

    let total = currentAircraft.price;
    if (isRound) total *= ROUND_TRIP_MULTIPLIER;

    let yesCount = 0;
    for (let i = 1; i <= pax; i++) {
        if (document.querySelector(`input[name="insurance${i}"][value="yes"]:checked`)) {
            yesCount++;
        }
    }
    total += yesCount * YES_INSURANCE_EXTRA;
    els.totalPriceEl.textContent = '₱' + Math.round(total).toLocaleString('en-PH');
}

function bindInsuranceListeners() {
    document.querySelectorAll('input[type="radio"][name^="insurance"]').forEach(radio => {
        radio.addEventListener('change', updateTotalPrice);
    });
}

// -------------------- Generate Passenger Fields --------------------
function generatePassengerFields() {
    const count = Math.max(1, parseInt(els.passengersInput.value) || 1);
    els.dynamicPassengers.innerHTML = '';

    const title = document.createElement('h5');
    title.className = 'mt-5 mb-4 fw-bold';
    title.textContent = `Passenger Details (${count})`;
    els.dynamicPassengers.appendChild(title);

    for (let i = 1; i <= count; i++) {
        const isPrimary = i === 1;
        const card = document.createElement('div');
        card.className = 'card mb-4 passenger-card shadow-sm';

        const firstName = isPrimary && userData?.first_name ? userData.first_name : '';
        const lastName = isPrimary && userData?.last_name ? userData.last_name : '';
        const email = isPrimary && userData?.email ? userData.email : '';
        const phone = isPrimary && userData?.phone ? userData.phone : '';

        card.innerHTML = `
        <div class="card-body">
          <h6 class="${isPrimary ? 'text-primary' : ''}">
            Passenger ${i}${isPrimary ? ' <span class="badge bg-primary ms-2">Primary Contact</span>' : ''}
          </h6>

          <div class="row g-3">

            <!-- First Name -->
            <div class="col-md-6">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text"
                    class="form-control"
                    name="passenger_f_name[]"
                    required
                    pattern="^[A-Za-z\s]+$"
                    value="${firstName}">
              <div class="invalid-feedback">
                Please enter a valid first name. Name field should contain alphabets [a-z], [A-Z] only
              </div>
            </div>

            <!-- Last Name -->
            <div class="col-md-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text"
                    class="form-control"
                    name="passenger_l_name[]"
                    required
                    pattern="^[A-Za-z\s]+$"
                    value="${lastName}">
              <div class="invalid-feedback">
                Please enter a valid last name. Name field should contain alphabets [a-z], [A-Z] only
              </div>
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label class="form-label">
                Email ${isPrimary ? '<span class="text-danger">*</span>' : '(optional)'}
              </label>
              <input type="email"
                    class="form-control"
                    name="passenger_email[]"
                    ${isPrimary ? 'required' : ''}
                    value="${email}">
              <div class="invalid-feedback">
                Enter an email address in this format: name@example.com
              </div>
            </div>

            <!-- Phone -->
            <div class="col-md-6">
              <label class="form-label">
                Phone ${isPrimary ? '<span class="text-danger">*</span>' : '(optional)'}
              </label>
              <input type="tel"
                    class="form-control"
                    name="passenger_phone[]"
                    pattern="^[0-9]{1,11}$"
                    maxlength="11"
                    ${isPrimary ? 'required' : ''}
                    value="${phone}">
              <div class="invalid-feedback">
                Invalid phone number. Use digits only, up to 11
              </div>
            </div>

            <!-- Insurance -->
            <div class="col-md-6">
              <label class="form-label">Travel Insurance?</label>
              <div class="btn-group w-100">
                <input type="radio" class="btn-check" name="insurance${i}" id="yes_${i}" value="yes">
                <label class="btn btn-outline-success" for="yes_${i}">Yes (+₱${YES_INSURANCE_EXTRA})</label>

                <input type="radio" class="btn-check" name="insurance${i}" id="no_${i}" value="no" checked>
                <label class="btn btn-outline-danger" for="no_${i}">No</label>
              </div>
            </div>

          </div>
        </div>`;
        els.dynamicPassengers.appendChild(card);
    }

    bindInsuranceListeners();
    updateTotalPrice();
}

// -------------------- Get Selected Destination --------------------
function getSelectedDestination() {
    const selectedPlaceName = document.getElementById('selectedPlaceName')?.value;
    return selectedPlaceName || "—";
}

// -------------------- Validate Complete Booking --------------------
function validateCompleteBooking() {
    // Check aircraft selection
    if (!currentAircraft) {
        showAlert("Please select an aircraft", "warning");
        els.aircraftSelect.focus();
        return false;
    }
    
    // Check date availability
    if (!validateBookingDate()) {
        return false;
    }
    
    // Check basic form validity
    const form = document.getElementById("bookingForm");
    const requiredFields = form.querySelectorAll('[required]');
    let formValid = true;

    const excludedFields = ['passenger_f_name[]', 'passenger_l_name[]', 'passenger_email[]', 'passenger_phone[]'];

    for (let field of requiredFields) {
        if (excludedFields.some(name => field.name === name)) continue;

        if (!field.value.trim()) {
            formValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    }

    if (!formValid) {
        showAlert("Please fill in all required fields", "warning");
        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) firstInvalid.focus();
        return false;
    }

    // Validate passengers
    if (!validatePassengers()) {
        return false;
    }
    
    return true;
}

// -------------------- Booking Summary Modal --------------------
document.getElementById("reviewBookingBtn").addEventListener("click", function (e) {
    e.preventDefault();

    // Clear previous alerts
    const alertBox = document.getElementById("formAlert");
    if (alertBox) {
        alertBox.innerHTML = '';
    }

    // Validate complete booking
    if (!validateCompleteBooking()) {
        return;
    }

    // If all validations pass, show summary
    showBookingSummary();
});

function showBookingSummary() {
    const form = document.getElementById("bookingForm");
    const formData = new FormData(form);
    const tripType = formData.get("trip_type") === "roundtrip" ? "Round Trip" : "One Way";
    const aircraftName = els.aircraftSelect.options[els.aircraftSelect.selectedIndex]?.text || "—";
    const departure = document.getElementById("departureLocation")?.selectedOptions[0]?.text || "—";
    const destination = getSelectedDestination();
    const depDate = formData.get("date") || "—";
    const depTime = formData.get("departure_time") || "—";
    const retDate = tripType === "Round Trip" ? (formData.get("return_date") || "—") : "—";
    const retTime = tripType === "Round Trip" ? (formData.get("return_time") || "—") : "—";
    const paxCount = els.passengersInput.value || "1";

    let total = 0;
    if (currentAircraft) {
        total = parseFloat(currentAircraft.price);
        if (tripType === "Round Trip") total *= ROUND_TRIP_MULTIPLIER;

        let yesCount = 0;
        for (let i = 1; i <= parseInt(paxCount); i++) {
            if (document.querySelector(`input[name="insurance${i}"][value="yes"]:checked`)) yesCount++;
        }
        total += yesCount * YES_INSURANCE_EXTRA;
        total = Math.round(total);
    }

    const summaryHTML = `
    <div class="p-3">
      <h4 class="text-center mb-4 text-primary">${aircraftName}</h4>
      <div class="row mb-2"><div class="col-5 fw-bold">Trip Type:</div><div class="col-7">${tripType}</div></div>
      <div class="row mb-2"><div class="col-5 fw-bold">Departure:</div><div class="col-7">${departure}</div></div>
      <div class="row mb-2"><div class="col-5 fw-bold">Destination:</div><div class="col-7">${destination}</div></div>
      <div class="row mb-2"><div class="col-5 fw-bold">Departure Date/Time:</div><div class="col-7">${depDate} • ${depTime}</div></div>
      ${tripType === "Round Trip" ? `<div class="row mb-2"><div class="col-5 fw-bold">Return Date/Time:</div><div class="col-7">${retDate} • ${retTime}</div></div>` : ''}
      <div class="row mb-3"><div class="col-5 fw-bold">Passengers:</div><div class="col-7">${paxCount}</div></div>
      <hr>
      <div class="row align-items-center"><div class="col-5 fw-bold fs-5">Total:</div><div class="col-7 fs-4 fw-bold text-success">₱${total.toLocaleString('en-PH')}</div></div>
      <small class="text-muted d-block text-center mt-4">Please review all details before proceeding to payment</small>
    </div>
    `;

    const summaryContent = document.getElementById("summaryContent");
    if (summaryContent) {
        summaryContent.innerHTML = summaryHTML;
    }

    // Show the modal
    const summaryModal = new bootstrap.Modal(document.getElementById("summaryModal"));
    summaryModal.show();
}

// -------------------- PayPal Payment --------------------
document.getElementById("confirmFinalBtn").addEventListener("click", async function () {
    // Clear previous alerts
    const alertBox = document.getElementById("formAlert");
    if (alertBox) {
        alertBox.innerHTML = '';
    }

    // Validate complete booking
    if (!validateCompleteBooking()) {
        return;
    }

    const container = document.getElementById('paypal-button-container');
    if (!container) {
        alert("Payment container not found. Refresh page.");
        return;
    }

    // Calculate total
    let total = 0;
    if (currentAircraft) {
        const pax = Math.max(1, parseInt(els.passengersInput.value) || 1);
        const isRound = document.getElementById('roundtrip').checked;

        total = parseFloat(currentAircraft.price);
        if (isRound) total *= ROUND_TRIP_MULTIPLIER;

        let yesCount = 0;
        for (let i = 1; i <= pax; i++) {
            if (document.querySelector(`input[name="insurance${i}"][value="yes"]:checked`)) yesCount++;
        }
        total += yesCount * YES_INSURANCE_EXTRA;
        total = Math.round(total);
    }

    if (total <= 0) {
        container.innerHTML = '<p class="text-danger text-center">Invalid total amount.</p>';
        return;
    }

    try {
        paypal.Buttons({
            createOrder: function (data, actions) {
                // Create order without booking_id first
                return fetch('../integrations/paypal/create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        amount: total
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success || !data.order_id) {
                            throw new Error(data.error || 'No order ID');
                        }
                        return data.order_id;
                    });
            },

            onApprove: async function (data, actions) {
                try {
                    // Collect all form data
                    const form = document.getElementById("bookingForm");
                    const formData = new FormData(form);

                    // Get passenger data
                    const passengerFNames = formData.getAll("passenger_f_name[]");
                    const passengerLNames = formData.getAll("passenger_l_name[]");
                    const passengerEmails = formData.getAll("passenger_email[]");
                    const passengerPhones = formData.getAll("passenger_phone[]");

                    // Create booking first
                    const paxCount = parseInt(els.passengersInput.value) || 1;
                    const passengerInsurances = [];
                    for (let i = 1; i <= paxCount; i++) {
                        const insurance = document.querySelector(`input[name="insurance${i}"][value="yes"]:checked`) ? 'yes' : 'no';
                        passengerInsurances.push(insurance);
                    }

                    const bookingData = {
                        lift_id: currentAircraft.lift_id,
                        place_id: selectedPlaceId,
                        departure_date: formData.get("date"),
                        departure_time: formData.get("departure_time"),
                        airport: formData.get("departure_location"),
                        passengers: paxCount,
                        passenger_f_names: passengerFNames,
                        passenger_l_names: passengerLNames,
                        passenger_emails: passengerEmails,
                        passenger_phones: passengerPhones,
                        passenger_insurances: passengerInsurances,
                        total_amount: total,
                        trip_type: document.querySelector('input[name="trip_type"]:checked')?.value || 'oneway',
                        return_date: formData.get("return_date") || null,
                        return_time: formData.get("return_time") || null
                    };

                    // Save booking to database
                    const bookingRes = await fetch('../booking/bookingProcess.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(bookingData)
                    });

                    const bookingResult = await bookingRes.json();

                    if (!bookingResult.success) {
                        throw new Error(bookingResult.error || 'Booking failed');
                    }

                    // Now capture PayPal payment
                    const captureData = {
                        order_id: data.orderID,
                        booking_id: bookingResult.booking_id
                    };

                    const captureRes = await fetch('../integrations/paypal/capture_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(captureData)
                    });

                    const captureResult = await captureRes.json();

                    if (captureResult.success) {
                        // Show success message
                        container.innerHTML = '<div class="alert alert-success">Payment successful! Redirecting...</div>';
                        // Redirect to success page
                        setTimeout(() => {
                            window.location.href = captureResult.redirect;
                        }, 1500);
                    } else {
                        throw new Error(captureResult.error || 'Payment failed');
                    }

                } catch (err) {
                    console.error('Payment error:', err);
                    const container = document.getElementById('paypal-button-container');
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Payment Error!</strong><br>
                            ${err.message || 'An error occurred during payment processing'}
                        </div>
                        <button class="btn btn-primary mt-3" onclick="location.reload()">Try Again</button>
                    `;
                }
            },

            onCancel: function () {
                container.innerHTML = '<p class="text-muted text-center">Payment cancelled. Try again.</p>';
            },

            onError: function (err) {
                container.innerHTML = '<p class="text-danger text-center">PayPal error. Try again.</p>';
                console.error(err);
            }
        }).render('#paypal-button-container');
    } catch (err) {
        container.innerHTML = `<p class="text-danger text-center">Error: ${err.message}</p>`;
        console.error(err);
    }
});

// Clear PayPal container when modal closes
const summaryModalEl = document.getElementById('summaryModal');
if (summaryModalEl) {
    summaryModalEl.addEventListener('hidden.bs.modal', () => {
        const container = document.getElementById('paypal-button-container');
        if (container) container.innerHTML = '';
    });
}

// -------------------- Initialize --------------------
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date picker with no aircraft selected
    initializeDatePicker();
    
    // Initialize time pickers
    timePicker = initializeTimePicker('timePicker');
    
    // Update total price
    updateTotalPrice();
    
    // Trigger aircraft selection if pre-selected
    if (els.aircraftSelect.value) {
        const event = new Event('change');
        els.aircraftSelect.dispatchEvent(event);
    }
    
    // Close time suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.time-picker-container')) {
            document.querySelectorAll('.time-suggestions').forEach(el => {
                el.style.display = 'none';
            });
        }
    });
});
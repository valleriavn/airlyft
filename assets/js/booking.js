const aircraftData = {
  "Cessna 206": { 
    base: 20000, 
    perPassenger: 2500, 
    capacity: 5, 
    name: "Cessna 206", 
    img: "https://images.unsplash.com/photo-1506947411487-4a9d6a0a2a8e?w=600" 
  },
  "Cessna G-Caravan EX": { 
    base: 35000, 
    perPassenger: 3000, 
    capacity: 10, 
    name: "Cessna G-Caravan EX", 
    img: "https://images.unsplash.com/photo-1564592030693-3c91e9e5e3e6?w=600" 
  },
  "Airbus H160": { 
    base: 40000, 
    perPassenger: 5000, 
    capacity: 8, 
    name: "Airbus H160", 
    img: "https://images.unsplash.com/photo-1558980663-3681b2b7f5a2?w=600" 
  },
  "Sikorsky S-76D": { 
    base: 45000, 
    perPassenger: 8500, 
    capacity: 6, 
    name: "Sikorsky S-76D", 
    img: "https://images.unsplash.com/photo-1569870499705-504209102861?w=600" 
  }
};

const ROUND_TRIP_MULTIPLIER = 1.8;
const YES_INSURANCE_EXTRA = 2000;

const els = {
  aircraftSelect: document.getElementById('aircraftSelect'),
  previewContainer: document.getElementById('aircraftPreview'),
  previewImg: document.getElementById('previewImage'),
  previewName: document.getElementById('previewName'),
  previewCapacity: document.getElementById('previewCapacity'),
  basePriceDisplay: document.getElementById('basePriceDisplay'),
  perPassengerDisplay: document.getElementById('perPassengerDisplay'),
  passengersInput: document.getElementById('passengers'),
  maxPassengersSpan: document.getElementById('maxPassengers'),
  totalPriceEl: document.getElementById('totalPrice'),
  dynamicPassengers: document.getElementById('dynamicPassengers')
};

let currentAircraft = null;

// Flatpickr
flatpickr("#datePicker", { dateFormat: "Y-m-d", minDate: "today", onChange: updateTotalPrice });
flatpickr("#timePicker", { enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, minuteIncrement: 30, minTime: "09:00", maxTime: "18:00", onChange: updateTotalPrice });

let returnPicker = null;
let returnTimePicker = null;

function initReturnPickers() {
  if (!returnPicker) returnPicker = flatpickr("#returnDatePicker", { dateFormat: "Y-m-d", minDate: "today", onChange: updateTotalPrice });
  if (!returnTimePicker) returnTimePicker = flatpickr("#returnTimePicker", { enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, minuteIncrement: 30, minTime: "09:00", maxTime: "18:00", onChange: updateTotalPrice });
}

// Trip type change
document.querySelectorAll('input[name="trip_type"]').forEach(radio => {
  radio.addEventListener('change', () => {
    const isRound = document.getElementById('roundtrip').checked;
    document.getElementById('returnDateContainer').style.display = isRound ? 'block' : 'none';
    document.getElementById('returnTimeContainer').style.display = isRound ? 'block' : 'none';

    if (isRound) {
      document.getElementById('returnDatePicker').required = true;
      document.getElementById('returnTimePicker').required = true;
      initReturnPickers();
      const depDate = document.getElementById('datePicker').value;
      if (depDate) returnPicker.set('minDate', depDate);
    } else {
      document.getElementById('returnDatePicker').required = false;
      document.getElementById('returnTimePicker').required = false;
      document.getElementById('returnDatePicker').value = '';
      document.getElementById('returnTimePicker').value = '';
    }

    updateDisplayedPrices();
    updateTotalPrice();
  });
});

// Aircraft selection - FIXED
els.aircraftSelect.addEventListener('change', function() {
  currentAircraft = aircraftData[this.value] ? { ...aircraftData[this.value] } : null;

  if (!currentAircraft) {
    els.previewContainer.style.display = 'none';
    els.passengersInput.max = 1;
    els.passengersInput.value = 1;
    els.maxPassengersSpan.textContent = '—';
    updateDisplayedPrices();
    updateTotalPrice();
    generatePassengerFields();
    return;
  }

  els.previewImg.src = currentAircraft.img;
  els.previewName.textContent = currentAircraft.name;
  els.previewCapacity.textContent = currentAircraft.capacity + " passengers";
  els.previewContainer.style.display = 'flex';

  els.passengersInput.max = currentAircraft.capacity;
  els.passengersInput.value = Math.min(parseInt(els.passengersInput.value) || 1, currentAircraft.capacity);
  els.maxPassengersSpan.textContent = currentAircraft.capacity;

  updateDisplayedPrices();
  generatePassengerFields();
  updateTotalPrice();
});

// Passenger count change
els.passengersInput.addEventListener('input', () => {
  if (currentAircraft) {
    const max = currentAircraft.capacity;
    if (els.passengersInput.value > max) els.passengersInput.value = max;
  }
  generatePassengerFields();
  updateTotalPrice();
});

// Insurance change
els.dynamicPassengers.addEventListener('change', e => {
  if (e.target.name?.startsWith('passenger_insurance_')) updateTotalPrice();
});

// Price display
function updateDisplayedPrices() {
  if (!currentAircraft) {
    els.basePriceDisplay.innerHTML = els.perPassengerDisplay.innerHTML = '₱0';
    return;
  }
  const isRound = document.getElementById('roundtrip').checked;
  const b = currentAircraft.base;
  const p = currentAircraft.perPassenger;

  els.basePriceDisplay.innerHTML = `₱${b.toLocaleString('en-PH')}` +
    (isRound ? ` <small>(${Math.round(b * ROUND_TRIP_MULTIPLIER).toLocaleString('en-PH')} round)</small>` : '');

  els.perPassengerDisplay.innerHTML = `₱${p.toLocaleString('en-PH')}` +
    (isRound ? ` <small>(${Math.round(p * ROUND_TRIP_MULTIPLIER).toLocaleString('en-PH')} round)</small>` : '');
}

function updateTotalPrice() {
  if (!currentAircraft) {
    els.totalPriceEl.textContent = '₱0';
    return;
  }

  const pax = Math.max(1, parseInt(els.passengersInput.value) || 1);
  const isRound = document.getElementById('roundtrip').checked;

  let total = currentAircraft.base + (currentAircraft.perPassenger * pax);
  if (isRound) total *= ROUND_TRIP_MULTIPLIER;

  let yesCount = 0;
  for (let i = 1; i <= pax; i++) {
    if (document.querySelector(`input[name="passenger_insurance_${i}"][value="yes"]:checked`)) yesCount++;
  }

  total += yesCount * YES_INSURANCE_EXTRA;
  els.totalPriceEl.textContent = '₱' + Math.round(total).toLocaleString('en-PH');
}

// Generate passenger fields
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
    card.innerHTML = `
      <div class="card-body">
        <h6 class="${isPrimary ? 'text-primary' : ''}">
          Passenger ${i}${isPrimary ? ' <span class="badge bg-primary ms-2">Primary Contact</span>' : ''}
        </h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="passenger_name[]" required minlength="2">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email ${isPrimary ? '<span class="text-danger">*</span>' : '<small class="text-muted">(optional)</small>'}</label>
            <input type="email" class="form-control" name="passenger_email[]" ${isPrimary ? 'required' : ''}>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone <small class="text-muted">(optional)</small></label>
            <input type="tel" class="form-control" name="passenger_phone[]">
          </div>
          <div class="col-md-6">
            <label class="form-label">Travel Insurance?</label>
            <div class="btn-group w-100">
              <input type="radio" class="btn-check" name="passenger_insurance_${i}" id="yes_${i}" value="yes">
              <label class="btn btn-outline-success" for="yes_${i}">Yes (+₱${YES_INSURANCE_EXTRA})</label>
              <input type="radio" class="btn-check" name="passenger_insurance_${i}" id="no_${i}" value="no" checked>
              <label class="btn btn-outline-danger" for="no_${i}">No</label>
            </div>
          </div>
        </div>
      </div>
    `;
    els.dynamicPassengers.appendChild(card);
  }

  updateTotalPrice();
}

// Summary modal with destination (static)
document.getElementById("reviewBookingBtn").addEventListener("click", function() {
  const form = document.getElementById("bookingForm");
  if (!form.checkValidity()) {
    form.classList.add("was-validated");
    return;
  }

  const formData = new FormData(form);
  const tripType = formData.get("trip_type") === "roundtrip" ? "Round Trip" : "One Way";
  const aircraftName = els.aircraftSelect.options[els.aircraftSelect.selectedIndex]?.text || "—";
  const departure = document.getElementById("departureLocation")?.selectedOptions[0]?.text || "—";

  const destination = "Cebu (CEB) - Mactan-Cebu"; // ← Destination added here

  const depDate = formData.get("date") || "—";
  const depTime = formData.get("departure_time") || "—";
  const retDate = tripType === "Round Trip" ? (formData.get("return_date") || "—") : "—";
  const retTime = tripType === "Round Trip" ? (formData.get("return_time") || "—") : "—";
  const paxCount = els.passengersInput.value || "1";
  const notes = formData.get("notes")?.trim() || "None";

  let total = 0;
  if (currentAircraft) {
    total = currentAircraft.base + (currentAircraft.perPassenger * paxCount);
    if (tripType === "Round Trip") total *= ROUND_TRIP_MULTIPLIER;

    let yesCount = 0;
    for (let i = 1; i <= paxCount; i++) {
      if (document.querySelector(`input[name="passenger_insurance_${i}"][value="yes"]:checked`)) yesCount++;
    }
    total += yesCount * YES_INSURANCE_EXTRA;
    total = Math.round(total);
  }

  const summaryHTML = `
    <div class="p-3">
      <h4 class="text-center mb-4 text-primary">${aircraftName}</h4>
      <div class="row mb-2">
        <div class="col-5 fw-bold">Trip Type:</div>
        <div class="col-7">${tripType}</div>
      </div>
      <div class="row mb-2">
        <div class="col-5 fw-bold">Departure:</div>
        <div class="col-7">${departure}</div>
      </div>
      <div class="row mb-2">
        <div class="col-5 fw-bold">Destination:</div>
        <div class="col-7">${destination}</div>
      </div>
      <div class="row mb-2">
        <div class="col-5 fw-bold">Departure Date/Time:</div>
        <div class="col-7">${depDate} • ${depTime}</div>
      </div>
      ${tripType === "Round Trip" ? `
      <div class="row mb-2">
        <div class="col-5 fw-bold">Return Date/Time:</div>
        <div class="col-7">${retDate} • ${retTime}</div>
      </div>` : ''}
      <div class="row mb-3">
        <div class="col-5 fw-bold">Passengers:</div>
        <div class="col-7">${paxCount}</div>
      </div>
      <hr>
      <div class="row align-items-center">
        <div class="col-5 fw-bold fs-5">Total:</div>
        <div class="col-7 fs-4 fw-bold text-success">₱${total.toLocaleString('en-PH')}</div>
      </div>
      ${notes !== "None" ? `
      <div class="row mt-3">
        <div class="col-5 fw-bold">Notes:</div>
        <div class="col-7">${notes}</div>
      </div>` : ''}
      <small class="text-muted d-block text-center mt-4">
        Please review all details before proceeding to payment
      </small>
    </div>
  `;

  document.getElementById("summaryContent").innerHTML = summaryHTML;

  const modal = new bootstrap.Modal(document.getElementById("summaryModal"));
  modal.show();
});

// Confirm → POST to paypal.php
document.getElementById("confirmFinalBtn").addEventListener("click", function() {
  const form = document.getElementById("bookingForm");
  bootstrap.Modal.getInstance(document.getElementById("summaryModal"))?.hide();

  const redirectForm = document.createElement('form');
  redirectForm.method = 'POST';
  redirectForm.action = 'paypal.php';

  new FormData(form).forEach((value, key) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = key;
    input.value = value;
    redirectForm.appendChild(input);
  });

  document.body.appendChild(redirectForm);
  redirectForm.submit();
});

// Initial load
updateDisplayedPrices();
updateTotalPrice();
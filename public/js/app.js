// Gestion des toasts Bootstrap
document.addEventListener('DOMContentLoaded', function () {
  // Initialisation des toasts
  const toastElList = [].slice.call(document.querySelectorAll('.toast'));
  toastElList.map(function (toastEl) { 
    return new bootstrap.Toast(toastEl).show(); 
  });


  // Amélioration de l'accessibilité mobile
  if (window.innerWidth <= 576) {
    // Augmenter la taille des zones cliquables sur mobile
    document.querySelectorAll('.btn').forEach(btn => {
      btn.style.minHeight = '44px';
      btn.style.padding = '12px 16px';
    });
    
    // Améliorer la navigation tactile
    document.querySelectorAll('.nav-link').forEach(link => {
      link.style.padding = '12px 16px';
      link.style.minHeight = '44px';
    });
  }
});


// Fonction utilitaire pour afficher des messages
function showMessage(message, type = 'info') {
  const toastContainer = document.querySelector('.toast-container');
  if (toastContainer) {
    const toneMap = { error: 'danger', message: 'info' };
    const tone = toneMap[type] || type;
    const delayMap = { success: 3000, warning: 4500, danger: 7000, info: 4000 };
    const iconMap = {
      success: 'fa-circle-check',
      warning: 'fa-triangle-exclamation',
      danger: 'fa-circle-xmark',
      info: 'fa-circle-info'
    };
    const delay = delayMap[tone] || 4000;
    const icon = iconMap[tone] || iconMap.info;

    const toast = document.createElement('div');
    toast.className = `toast align-items-start border-0 app-toast app-toast-tone-${tone}`;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.setAttribute('aria-atomic', 'true');
    toast.setAttribute('data-bs-delay', String(delay));
    
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">
          <i class="fa-solid ${icon} me-2" aria-hidden="true"></i>${message}
        </div>
        <button type="button" class="btn-close me-2 m-2" data-bs-dismiss="toast" aria-label="Fermer"></button>
      </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const reservationModalEl = document.getElementById('reservationModal');
  if (!reservationModalEl) {
    return;
  }

  let countdown = 60;
  let timerInterval;
  const csrfToken = reservationModalEl.dataset.csrfToken;
  const reservationModal = new bootstrap.Modal(reservationModalEl);
  const slotTimeEl = document.getElementById('slotTime');
  const slotDayEl = document.getElementById('slotDay');
  const remainingPlacesEl = document.getElementById('remainingPlaces');
  const countdownEl = document.getElementById('countdown');
  const confirmReservationBtn = document.getElementById('confirmReservation');
  const cancelledReservationBtn = document.getElementById('cancelledReservation');
  const closeModalBtn = document.getElementById('btn-close-modal');

  const reserveSlot = (slotId, status, onSuccess) => {
    fetch('/slots/' + slotId + '/reserve/' + status, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(response => response.json())
      .then(data => {
        if (data.success && typeof onSuccess === 'function') {
          onSuccess();
        }
      });
  };

  reservationModalEl.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const slotId = button.dataset.slotId;
    slotTimeEl.innerText = button.dataset.slotTime;
    slotDayEl.innerText = button.dataset.slotDay;
    remainingPlacesEl.innerText = button.dataset.remaining;
    cancelledReservationBtn.dataset.slotId = slotId;
    confirmReservationBtn.dataset.slotId = slotId;

    countdown = 60;
    countdownEl.innerText = countdown;
    reserveSlot(slotId, 'pending');

    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      countdown--;
      countdownEl.innerText = countdown;

      if (countdown <= 0) {
        clearInterval(timerInterval);
        reservationModal.hide();
        reserveSlot(cancelledReservationBtn.dataset.slotId, 'cancelled', () => window.location.reload());
      }
    }, 1000);
  });

  confirmReservationBtn.addEventListener('click', () => {
    clearInterval(timerInterval);
    reservationModal.hide();
    reserveSlot(confirmReservationBtn.dataset.slotId, 'confirmed', () => window.location.reload());
  });

  cancelledReservationBtn.addEventListener('click', () => {
    clearInterval(timerInterval);
    reservationModal.hide();
    reserveSlot(cancelledReservationBtn.dataset.slotId, 'cancelled', () => window.location.reload());
  });

  document.querySelectorAll('.cancelledReservation').forEach(button => {
    button.addEventListener('click', function () {
      clearInterval(timerInterval);
      reservationModal.hide();
      reserveSlot(this.dataset.slotId, 'cancelled', () => window.location.reload());
    });
  });

  document.querySelectorAll('.confirmReservationSincePending').forEach(button => {
    button.addEventListener('click', function () {
      clearInterval(timerInterval);
      reserveSlot(this.dataset.slotId, 'confirmed', () => window.location.reload());
    });
  });

  closeModalBtn.addEventListener('click', () => {
    window.location.reload();
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const filterButtons = document.querySelectorAll('.js-reservation-filter');
  if (!filterButtons.length) {
    return;
  }

  const applyFilter = (group, status) => {
    const items = document.querySelectorAll('.js-reservation-item[data-filter-group="' + group + '"]');
    items.forEach(item => {
      const itemStatus = item.dataset.reservationStatus || '';
      const shouldShow = status === 'all' || itemStatus === status;
      item.style.display = shouldShow ? '' : 'none';
    });
  };

  filterButtons.forEach(button => {
    button.addEventListener('click', function () {
      const group = this.dataset.filterGroup;
      const status = this.dataset.filterStatus;

      document
        .querySelectorAll('.js-reservation-filter[data-filter-group="' + group + '"]')
        .forEach(groupButton => {
          groupButton.classList.remove('is-active');
        });

      this.classList.add('is-active');
      applyFilter(group, status);
    });
  });
});

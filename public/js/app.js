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

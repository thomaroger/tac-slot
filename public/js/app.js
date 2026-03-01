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
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.setAttribute('aria-atomic', 'true');
    toast.setAttribute('data-bs-delay', '4000');
    
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
  }
}

/**
 * À l'ouverture, attache la modale directement à <body>, au même niveau que le backdrop Bootstrap.
 * Sinon le backdrop (souvent après .main-wrapper dans le DOM) peut recouvrir le dialogue
 * et bloquer saisie / clics.
 */
(function () {
  document.addEventListener(
    'show.bs.modal',
    function (event) {
      var el = event.target;
      if (!el || !el.classList || !el.classList.contains('modal')) {
        return;
      }
      if (el.parentElement === document.body) {
        return;
      }
      document.body.appendChild(el);
    },
    true
  );
})();

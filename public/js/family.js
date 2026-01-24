(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    // Handle multiple email/no-email pairs (Add form + inline edit forms)
    var forms = document.querySelectorAll('[data-family-form]');

    function wireEmailToggle(formEl) {
      var emailInput = formEl.querySelector('[data-family-email]');
      var noEmail = formEl.querySelector('[data-family-no-email]');
      if (!emailInput || !noEmail) return;

      function sync() {
        var checked = !!noEmail.checked;
        emailInput.disabled = checked;
        emailInput.required = !checked;
        if (checked) {
          emailInput.value = '';
        }
      }

      noEmail.addEventListener('change', sync);
      sync();
    }

    for (var i = 0; i < forms.length; i++) {
      wireEmailToggle(forms[i]);
    }

    // Inline edit row toggles
    var toggles = document.querySelectorAll('[data-family-edit-toggle]');
    function setRowVisible(memberId, visible) {
      var row = document.querySelector('[data-family-edit-row="' + memberId + '"]');
      if (!row) return;
      row.style.display = visible ? '' : 'none';
    }

    for (var j = 0; j < toggles.length; j++) {
      (function (btn) {
        btn.addEventListener('click', function () {
          var memberId = btn.getAttribute('data-family-edit-toggle');
          var row = document.querySelector('[data-family-edit-row="' + memberId + '"]');
          if (!row) return;
          var isHidden = row.style.display === 'none' || row.style.display === '' && row.classList.contains('is-hidden');

          // Normalize: we use style.display for deterministic state.
          var currentlyVisible = row.style.display !== 'none';
          setRowVisible(memberId, !currentlyVisible);
        });
      })(toggles[j]);
    }

    var cancels = document.querySelectorAll('[data-family-edit-cancel]');
    for (var k = 0; k < cancels.length; k++) {
      (function (btn) {
        btn.addEventListener('click', function () {
          var memberId = btn.getAttribute('data-family-edit-cancel');
          setRowVisible(memberId, false);
        });
      })(cancels[k]);
    }
  });
})();

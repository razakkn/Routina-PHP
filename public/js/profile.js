(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var status = document.getElementById('RelationshipStatus');
    var partnerWrap = document.getElementById('PartnerFields');
    var partnerTypeWrap = document.getElementById('PartnerTypeWrap');
    var partnerType = document.getElementById('PartnerType');

    var partnerName = document.getElementById('PartnerName');
    var partnerPhone = document.getElementById('PartnerPhone');
    var partnerEmail = document.getElementById('PartnerEmail');
    var partnerNoEmail = document.getElementById('PartnerNoEmail');

    if (!status || !partnerWrap) return;

    function syncNoEmail() {
      if (!partnerEmail || !partnerNoEmail) return;
      var checked = !!partnerNoEmail.checked;
      partnerEmail.disabled = checked;
      partnerEmail.required = !checked;
      if (checked) {
        partnerEmail.value = '';
      }
    }

    function syncStatus() {
      var v = (status.value || 'single');
      var needsPartner = (v === 'married' || v === 'in_relationship');

      partnerWrap.style.display = needsPartner ? '' : 'none';

      if (partnerName) partnerName.required = needsPartner;
      if (partnerPhone) partnerPhone.required = needsPartner;

      if (partnerTypeWrap) {
        // Only show girlfriend/boyfriend selector when in_relationship
        partnerTypeWrap.style.display = (v === 'in_relationship') ? '' : 'none';
      }

      if (partnerType) {
        partnerType.required = (v === 'in_relationship');
      }

      // Email requirement depends on no-email checkbox
      if (needsPartner) {
        syncNoEmail();
      } else {
        if (partnerEmail) {
          partnerEmail.disabled = false;
          partnerEmail.required = false;
        }
      }
    }

    if (partnerNoEmail) {
      partnerNoEmail.addEventListener('change', function () {
        syncNoEmail();
      });
    }

    status.addEventListener('change', syncStatus);
    syncStatus();
  });
})();

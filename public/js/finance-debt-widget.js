document.addEventListener('DOMContentLoaded', function () {
  var widget = document.querySelector('.finance-page .finance-debt-widget');
  var summaryCol = document.querySelector('.finance-page .finance-summary-col');
  var hideBtn = document.querySelector('.finance-debt-hide');
  var showBtn = document.querySelector('.finance-debt-show');
  var key = 'routina.financeDebtWidgetHidden';

  function setHidden(hidden) {
    if (!widget || !showBtn) return;
    widget.classList.toggle('finance-debt-hidden', hidden);
    if (summaryCol) {
      summaryCol.classList.toggle('finance-summary-expand', hidden);
    }
    showBtn.classList.toggle('d-none', !hidden);
  }

  setHidden(localStorage.getItem(key) === '1');

  if (hideBtn) {
    hideBtn.addEventListener('click', function () {
      localStorage.setItem(key, '1');
      setHidden(true);
    });
  }

  if (showBtn) {
    showBtn.addEventListener('click', function () {
      localStorage.removeItem(key);
      setHidden(false);
    });
  }
});

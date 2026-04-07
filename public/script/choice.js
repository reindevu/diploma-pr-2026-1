document.addEventListener('DOMContentLoaded', function () {
  const buttons = document.querySelectorAll('[data-size-choice]');

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      buttons.forEach(function (item) {
        item.classList.remove('active');
      });

      button.classList.add('active');
    });
  });
});

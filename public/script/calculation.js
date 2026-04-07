document.addEventListener('DOMContentLoaded', function () {
  const minusBtns = document.querySelectorAll('.minus-btn');
  const plusBtns = document.querySelectorAll('.plus-btn');
  const quantityInputs = document.querySelectorAll('.quantity-input');

  const count = Math.min(minusBtns.length, plusBtns.length, quantityInputs.length);

  for (let index = 0; index < count; index += 1) {
    const minusBtn = minusBtns[index];
    const plusBtn = plusBtns[index];
    const quantityInput = quantityInputs[index];

    minusBtn.addEventListener('click', function () {
      const value = parseInt(quantityInput.value, 10);
      quantityInput.value = !value || value <= 1 ? '1' : String(value - 1);
    });

    plusBtn.addEventListener('click', function () {
      const value = parseInt(quantityInput.value, 10);
      quantityInput.value = !value || value < 1 ? '1' : String(value + 1);
    });

    quantityInput.addEventListener('input', function () {
      const value = parseInt(quantityInput.value, 10);
      quantityInput.value = !value || value < 1 ? '1' : String(value);
    });
  }
});

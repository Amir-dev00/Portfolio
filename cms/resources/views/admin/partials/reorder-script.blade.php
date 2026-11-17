<script>
  (() => {
    const form = document.querySelector('[data-admin-order]');
    const list = form.querySelector('ol');

    form.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-move]');
      if (!button) return;

      const row = button.closest('li');
      const moveUp = button.dataset.move === 'up';
      const neighbor = moveUp ? row.previousElementSibling : row.nextElementSibling;
      if (!neighbor) return;

      if (moveUp) list.insertBefore(row, neighbor);
      else list.insertBefore(neighbor, row);

      for (const item of list.children) {
        item.querySelector('[data-move="up"]').disabled = item === list.firstElementChild;
        item.querySelector('[data-move="down"]').disabled = item === list.lastElementChild;
      }

      (button.disabled ? row.querySelector('button:not(:disabled)') : button).focus();
    });
  })();
</script>

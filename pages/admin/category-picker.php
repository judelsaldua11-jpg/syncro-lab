<?php
$categories = $categories ?? [];
$selectedCategoryIds = array_map('intval', (array)($selectedCategoryIds ?? []));
$categoryPickerId = 'category-picker-' . substr(md5(uniqid('', true)), 0, 8);
?>
<div class="category-picker" id="<?= $categoryPickerId ?>">
    <label for="<?= $categoryPickerId ?>-search" style="font-weight: 700; display: block; margin-bottom: 8px;">Categories</label>
    <div style="display: flex; gap: 8px; margin-bottom: 10px;">
        <input type="search" id="<?= $categoryPickerId ?>-search" placeholder="Search categories..." autocomplete="off"
               style="flex: 1; padding: 10px 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 15px;">
        <input type="text" class="category-new-input" placeholder="New category" autocomplete="off"
               style="flex: 1; padding: 10px 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 15px;">
        <button type="button" class="btn btn--outline category-add-button" style="padding: 0 14px;">ADD</button>
    </div>
    <div class="category-selected" style="display: flex; flex-wrap: wrap; gap: 6px; min-height: 8px; margin-bottom: 10px;"></div>
    <div class="category-new-values"></div>
    <div class="category-options" style="display: flex; flex-wrap: wrap; gap: 8px; max-height: 180px; overflow-y: auto; padding: 8px; background: var(--light); border: 1px solid var(--gray); border-radius: var(--radius);">
        <?php foreach ($categories as $category): ?>
            <label class="category-option" data-category-name="<?= htmlspecialchars(strtolower($category['name']), ENT_QUOTES) ?>"
                   style="display: flex; align-items: center; gap: 6px; font-size: 14px; padding: 6px 10px; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); cursor: pointer;">
                <input type="checkbox" name="categories[]" value="<?= (int)$category['id'] ?>"
                    <?= in_array((int)$category['id'], $selectedCategoryIds, true) ? 'checked' : '' ?>>
                <span><?= htmlspecialchars($category['name']) ?></span>
            </label>
        <?php endforeach; ?>
    </div>
</div>
<script>
(function () {
    const picker = document.getElementById('<?= $categoryPickerId ?>');
    const search = picker.querySelector('input[type="search"]');
    const selected = picker.querySelector('.category-selected');
    const options = [...picker.querySelectorAll('.category-option')];
    const newInput = picker.querySelector('.category-new-input');

    function refresh() {
        const query = search.value.trim().toLowerCase();
        options.forEach(option => {
            option.style.display = option.dataset.categoryName.includes(query) ? 'flex' : 'none';
        });
        selected.innerHTML = '';
        picker.querySelectorAll('input[name="categories[]"]:checked').forEach(input => {
            const label = input.closest('.category-option').querySelector('span').textContent;
            const chip = document.createElement('span');
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:5px 9px;background:var(--green);border-radius:var(--radius);font-size:13px;';
            chip.textContent = label;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'x';
            remove.setAttribute('aria-label', 'Remove ' + label);
            remove.style.cssText = 'border:0;background:none;cursor:pointer;font-weight:700;padding:0 2px;';
            remove.addEventListener('click', () => { input.checked = false; refresh(); });
            chip.appendChild(remove);
            selected.appendChild(chip);
        });
        picker.querySelectorAll('.category-new-value').forEach(input => {
            const chip = document.createElement('span');
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:5px 9px;background:var(--green);border-radius:var(--radius);font-size:13px;';
            chip.textContent = input.value;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'x';
            remove.setAttribute('aria-label', 'Remove ' + input.value);
            remove.style.cssText = 'border:0;background:none;cursor:pointer;font-weight:700;padding:0 2px;';
            remove.addEventListener('click', () => { input.remove(); refresh(); });
            chip.appendChild(remove);
            selected.appendChild(chip);
        });
    }

    search.addEventListener('input', refresh);
    picker.querySelectorAll('input[name="categories[]"]').forEach(input => input.addEventListener('change', refresh));
    picker.querySelector('.category-add-button').addEventListener('click', () => {
        const value = newInput.value.trim();
        if (!value) return;
        const match = options.find(option => option.dataset.categoryName === value.toLowerCase());
        if (match) {
            match.querySelector('input').checked = true;
        } else {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'new_categories[]';
            hidden.value = value;
            hidden.className = 'category-new-value';
            picker.querySelector('.category-new-values').appendChild(hidden);
        }
        newInput.value = '';
        refresh();
    });
    newInput.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            picker.querySelector('.category-add-button').click();
        }
    });
    refresh();
})();
</script>

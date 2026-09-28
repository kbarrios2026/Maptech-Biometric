<script>
    (() => {
        const departmentSelect = document.getElementById('department_id');
        const positionSelect = document.getElementById('position_id');

        if (!departmentSelect || !positionSelect) {
            return;
        }

        const positionOptions = Array.from(positionSelect.options)
            .slice(1)
            .map((option) => option.cloneNode(true));
        const placeholder = positionSelect.options[0].cloneNode(true);

        function filterPositions(resetSelection = false) {
            const departmentId = departmentSelect.value;
            const selectedPositionId = resetSelection ? '' : positionSelect.value;

            positionSelect.replaceChildren(placeholder.cloneNode(true));
            positionSelect.options[0].textContent = departmentId
                ? 'Select Position'
                : 'Select Department first';
            positionSelect.disabled = !departmentId;

            positionOptions
                .filter((option) => option.dataset.departmentId === departmentId)
                .forEach((option) => {
                    const filteredOption = option.cloneNode(true);
                    filteredOption.selected = filteredOption.value === selectedPositionId;
                    positionSelect.add(filteredOption);
                });

            if (selectedPositionId && positionSelect.value !== selectedPositionId) {
                positionSelect.value = '';
            }
        }

        departmentSelect.addEventListener('change', () => filterPositions(true));
        filterPositions();
    })();
</script>

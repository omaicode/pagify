<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema builder</title>
    <style>
        .builder-card { border: 1px solid #d1d5db; padding: 12px; margin-bottom: 10px; background: #fff; }
        .builder-grid { display: grid; gap: 8px; }
        .builder-actions { display: flex; gap: 8px; margin-top: 8px; }
    </style>
</head>
<body>
    <main>
        <h1>Schema builder for {{ $contentType->name }}</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div>
                <strong>Validation failed:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p>This builder stores schema metadata and queues migration planning jobs. It does not execute direct DDL in web requests.</p>

        <form method="POST" action="{{ route('content.admin.types.builder.update', $contentType) }}" id="builder-form">
            @csrf
            @method('PUT')

            <div id="fields-container"></div>

            <button type="button" id="add-field">Add field</button>
            <button type="submit">Save schema and queue plan</button>
        </form>

        <p><a href="{{ route('content.admin.types.builder.status', $contentType) }}">View migration plans</a></p>
        <p><a href="{{ route('content.admin.types.edit', $contentType) }}">Back to content type edit</a></p>
    </main>

    <script>
        const initialFields = @json(old('fields', $initialFields));
        const fieldTypes = @json($fieldTypes);

        const container = document.getElementById('fields-container');
        const addButton = document.getElementById('add-field');
        let draggingIndex = null;

        const ensureFields = () => {
            if (!Array.isArray(initialFields) || initialFields.length === 0) {
                initialFields.push({
                    key: 'title',
                    label: 'Title',
                    field_type: 'text',
                    config: {},
                    validation: {},
                    conditional: {},
                    sort_order: 0,
                    is_required: true,
                    is_localized: false,
                });
            }
        };

        const asJsonText = (value) => {
            try {
                return JSON.stringify(value ?? {}, null, 0);
            } catch (error) {
                return '{}';
            }
        };

        const render = () => {
            container.innerHTML = '';

            initialFields.forEach((field, index) => {
                const card = document.createElement('section');
                card.className = 'builder-card';
                card.draggable = true;
                card.dataset.index = String(index);

                card.addEventListener('dragstart', () => {
                    draggingIndex = index;
                });

                card.addEventListener('dragover', (event) => {
                    event.preventDefault();
                });

                card.addEventListener('drop', () => {
                    if (draggingIndex === null || draggingIndex === index) {
                        return;
                    }

                    const [moved] = initialFields.splice(draggingIndex, 1);
                    initialFields.splice(index, 0, moved);
                    draggingIndex = null;
                    render();
                });

                const options = fieldTypes
                    .map((fieldType) => `<option value="${fieldType}" ${String(field.field_type) === fieldType ? 'selected' : ''}>${fieldType}</option>`)
                    .join('');

                card.innerHTML = `
                    <div class="builder-grid">
                        <strong>Field #${index + 1}</strong>
                        <label>Key <input type="text" name="fields[${index}][key]" value="${String(field.key ?? '')}" required></label>
                        <label>Label <input type="text" name="fields[${index}][label]" value="${String(field.label ?? '')}" required></label>
                        <label>Type <select name="fields[${index}][field_type]">${options}</select></label>
                        <label>Config JSON <textarea name="fields[${index}][config]">${asJsonText(field.config)}</textarea></label>
                        <label>Validation JSON <textarea name="fields[${index}][validation]">${asJsonText(field.validation)}</textarea></label>
                        <label>Conditional JSON <textarea name="fields[${index}][conditional]">${asJsonText(field.conditional)}</textarea></label>
                        <input type="hidden" name="fields[${index}][sort_order]" value="${index}">
                        <label>Required <input type="checkbox" name="fields[${index}][is_required]" value="1" ${(field.is_required ?? false) ? 'checked' : ''}></label>
                        <label>Localized <input type="checkbox" name="fields[${index}][is_localized]" value="1" ${(field.is_localized ?? false) ? 'checked' : ''}></label>
                    </div>
                `;

                const actions = document.createElement('div');
                actions.className = 'builder-actions';

                const upButton = document.createElement('button');
                upButton.type = 'button';
                upButton.textContent = 'Move up';
                upButton.disabled = index === 0;
                upButton.addEventListener('click', () => {
                    if (index === 0) {
                        return;
                    }

                    const [moved] = initialFields.splice(index, 1);
                    initialFields.splice(index - 1, 0, moved);
                    render();
                });

                const downButton = document.createElement('button');
                downButton.type = 'button';
                downButton.textContent = 'Move down';
                downButton.disabled = index === initialFields.length - 1;
                downButton.addEventListener('click', () => {
                    if (index === initialFields.length - 1) {
                        return;
                    }

                    const [moved] = initialFields.splice(index, 1);
                    initialFields.splice(index + 1, 0, moved);
                    render();
                });

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.textContent = 'Remove';
                removeButton.addEventListener('click', () => {
                    initialFields.splice(index, 1);
                    ensureFields();
                    render();
                });

                actions.appendChild(upButton);
                actions.appendChild(downButton);
                actions.appendChild(removeButton);
                card.appendChild(actions);
                container.appendChild(card);
            });
        };

        addButton.addEventListener('click', () => {
            initialFields.push({
                key: `field_${initialFields.length + 1}`,
                label: `Field ${initialFields.length + 1}`,
                field_type: 'text',
                config: {},
                validation: {},
                conditional: {},
                sort_order: initialFields.length,
                is_required: false,
                is_localized: false,
            });

            render();
        });

        ensureFields();
        render();
    </script>
</body>
</html>

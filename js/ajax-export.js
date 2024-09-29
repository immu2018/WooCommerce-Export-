document.addEventListener('DOMContentLoaded', function () {
    const exportForm = document.querySelector('form'); // Adjust to select your form
    const selectedFieldsInput = document.getElementById('selected_fields_input'); // Reference to the hidden input

    exportForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent the default form submission

        // Get selected fields using the correct class
        const selectedFields = Array.from(document.querySelectorAll('#selected-fields .field-item')).map(item => item.getAttribute('data-field'));
        if (selectedFields.length === 0) {
            alert('Please select at least one field to export.');
            return;
        }

        // Update hidden input value
        selectedFieldsInput.value = selectedFields.join(',');

        // Show loader
        document.getElementById('loader').style.display = 'block';

        // AJAX request
        jQuery.ajax({
            type: 'POST',
            url: ajax_object.ajax_url, // This is provided by wp_localize_script
            data: {
                action: 'export_products', // Your action hook
                fields: selectedFields,
            },
            success: function (response) {
                document.getElementById('loader').style.display = 'none';
                if (response.success) {
                    // Redirect to the generated CSV file URL
                    window.location.href = response.data.file_url;
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function (error) {
                console.error(error);
                alert('An error occurred while exporting the file.');
                document.getElementById('loader').style.display = 'none';
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    // Get the list of fields and the selected fields container
    const coreFields = document.getElementById('core-fields');
    const customFields = document.getElementById('custom-fields');
    const selectedFields = document.getElementById('selected-fields');

    // Function to add selected field to the right side
    function addFieldToSelection(fieldValue) {
        // Create a new list item for the selected field
        const newFieldItem = document.createElement('li');
        newFieldItem.className = 'field-item'; // Use the same class as the core fields
        newFieldItem.setAttribute('data-field', fieldValue);
        newFieldItem.textContent = fieldValue;

        // Append the new field item to the selected fields list
        selectedFields.appendChild(newFieldItem);

        // Update the hidden input value
        const currentValues = selectedFieldsInput.value ? selectedFieldsInput.value.split(',') : [];
        currentValues.push(fieldValue);
        selectedFieldsInput.value = currentValues.join(',');
    }

    // Add click event listener to each field in the left list
    [coreFields, customFields].forEach(fieldList => {
        fieldList.addEventListener('click', function (event) {
            if (event.target.classList.contains('field-item')) {
                const fieldValue = event.target.getAttribute('data-field');
                addFieldToSelection(fieldValue);
            }
        });
    });
});



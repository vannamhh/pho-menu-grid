/**
 * Dietary guide repeater for the Menu Item meta box.
 *
 * Extracted from an inline <script> so it can be cached and does not need an
 * inline-script CSP exemption.
 */
(function () {
	'use strict';

	var i18n = (window.phoMenuAdmin && window.phoMenuAdmin.i18n) || {};

	document.addEventListener('DOMContentLoaded', function () {
		var wrap = document.getElementById('pho_dietary_repeater_wrap');

		if (!wrap) {
			return;
		}

		var input = document.getElementById('pho_dietary_guide_json');
		var rowsContainer = document.getElementById('pho_dietary_rows');
		var addButton = document.getElementById('pho_add_dietary_row');
		var rows = [];

		try {
			var parsed = JSON.parse(input.value || '[]');
			rows = Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			rows = [];
		}

		/**
		 * Write the current rows back into the hidden field.
		 */
		function sync() {
			input.value = JSON.stringify(rows);
		}

		/**
		 * Build one repeater row.
		 *
		 * @param {Object} row   Row data.
		 * @param {number} index Row index.
		 * @return {HTMLElement}
		 */
		function buildRow(row, index) {
			var wrapper = document.createElement('div');
			wrapper.className = 'pho-dietary-row';

			var label = document.createElement('input');
			label.type = 'text';
			label.className = 'pho-dietary-label';
			label.placeholder = i18n.labelPlaceholder || 'Label';
			label.value = row.label || '';
			label.addEventListener('input', function () {
				rows[index].label = label.value;
				sync();
			});

			var text = document.createElement('textarea');
			text.className = 'pho-dietary-text';
			text.rows = 2;
			text.placeholder = i18n.textPlaceholder || 'Description text...';
			text.value = row.text || '';
			text.addEventListener('input', function () {
				rows[index].text = text.value;
				sync();
			});

			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'button button-link pho-dietary-remove';
			remove.textContent = i18n.remove || 'Remove';
			remove.addEventListener('click', function () {
				rows.splice(index, 1);
				sync();
				render();
			});

			wrapper.appendChild(label);
			wrapper.appendChild(text);
			wrapper.appendChild(remove);

			return wrapper;
		}

		/**
		 * Redraw every row.
		 */
		function render() {
			rowsContainer.textContent = '';
			rows.forEach(function (row, index) {
				rowsContainer.appendChild(buildRow(row, index));
			});
		}

		addButton.addEventListener('click', function () {
			rows.push({ label: '', text: '' });
			sync();
			render();
		});

		render();
	});
})();

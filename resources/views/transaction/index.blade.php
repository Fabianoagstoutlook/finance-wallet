<x-app-layout>
	<div class="py-12">
		<div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white shadow sm:rounded-lg p-6">
				<form id="transaction-form" method="POST" action="{{ route('transactions.deposit') }}" class="space-y-4">
					@csrf

					<div>
						<label for="transaction_type" class="block text-sm font-medium text-gray-700">
							{{ __('Transação') }}
						</label>
						<select id="transaction_type" name="transaction_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
							<option value="deposit">{{ __('Depositar ') }}</option>
							<option value="withdraw">{{ __('Receber') }}</option>
							<option value="transfer">{{ __('Enviar') }}</option>
						</select>
					</div>

					<div id="email_field" class="hidden">
						<label for="to_email" class="block text-sm font-medium text-gray-700">
							{{ __('Email do destinatário') }}
						</label>
						<input id="to_email" name="to_email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="email@exemplo.com" />
					</div>

					<div>
						<label for="amount" class="block text-sm font-medium text-gray-700">
							{{ __('Valor') }}
						</label>
						<input id="amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="R$ 0,00" />
					</div>

					<div class="flex items-center justify-end">
						<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700">
							{{ __('Confirmar') }}
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div id="modal-overlay" class="fixed inset-0 bg-gray-900/60 hidden"></div>
	<div id="modal" class="fixed inset-0 flex items-center justify-center hidden px-4">
		<div class="bg-white rounded-lg shadow-xl w-3/4 max-w-sm min-h-[220px]">
			<div class="p-6">
				<h3 id="modal-title" class="text-lg font-semibold text-gray-900"></h3>
				<p id="modal-message" class="mt-3 text-sm text-gray-600"></p>
				<div class="mt-6 flex justify-end">
					<button id="modal-close" type="button" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700">
						{{ __('Ok') }}
					</button>
				</div>
			</div>
		</div>
	</div>

	<script>
		(function () {
			var typeSelect = document.getElementById('transaction_type');
			var emailField = document.getElementById('email_field');
			var form = document.getElementById('transaction-form');
			var amountInput = document.getElementById('amount');
			var depositAction = "{{ route('transactions.deposit') }}";
			var transferAction = "{{ route('transactions.transfer') }}";
			var withdrawAction = "{{ route('transactions.withdraw') }}";

			var modal = document.getElementById('modal');
			var modalOverlay = document.getElementById('modal-overlay');
			var modalTitle = document.getElementById('modal-title');
			var modalMessage = document.getElementById('modal-message');
			var modalClose = document.getElementById('modal-close');

			function openModal(title, message) {
				modalTitle.textContent = title;
				modalMessage.textContent = message;
				modal.classList.remove('hidden');
				modalOverlay.classList.remove('hidden');
			}

			function closeModal() {
				modal.classList.add('hidden');
				modalOverlay.classList.add('hidden');
			}

			function updateForm() {
				var value = typeSelect.value;

				if (value === 'transfer') {
					emailField.classList.remove('hidden');
					form.setAttribute('action', transferAction);
					return;
				}

				emailField.classList.add('hidden');
				form.setAttribute('action', value === 'withdraw' ? withdrawAction : depositAction);
			}

			function formatCurrency(value) {
				var digits = value.replace(/\D/g, '');
				if (!digits) {
					return '';
				}
				while (digits.length < 3) {
					digits = '0' + digits;
				}
				var cents = digits.slice(-2);
				var integerPart = digits.slice(0, -2).replace(/^0+/, '');
				if (!integerPart) {
					integerPart = '0';
				}
				integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
				return 'R$ ' + integerPart + ',' + cents;
			}

			function normalizeAmount(value) {
				return value.replace(/\./g, '').replace(/R\$\s*/g, '').replace(',', '.').trim();
			}

			function resetForm() {
				form.reset();
				updateForm();
				amountInput.value = '';
			}

			amountInput.addEventListener('input', function (event) {
				var formatted = formatCurrency(event.target.value);
				event.target.value = formatted;
			});

			form.addEventListener('submit', function (event) {
				event.preventDefault();

				var formData = new FormData(form);
				var amount = normalizeAmount(formData.get('amount') || '');
				formData.set('amount', amount);

				fetch(form.getAttribute('action'), {
					method: 'POST',
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
					},
					body: formData,
				})
					.then(function (response) {
						if (response.ok) {
							return response.json();
						}

						return response.text().then(function (text) {
							var message = 'Ocorreu um erro na transação. Tente novamente mais tarde.';
							if (text) {
								try {
									var data = JSON.parse(text);
									if (data && data.message) {
										message = data.message;
									}
									if (data && data.errors) {
										var first = Object.values(data.errors).flat()[0];
										if (first) {
											message = first;
										}
									}
								} catch (error) {
									message = text;
								}
							}
							throw new Error(message);
						});
					})
					.then(function () {
						openModal('Transação realizada com sucesso', 'Sua transação foi concluída.');
						resetForm();
					})
					.catch(function (error) {
						openModal('Erro na transação', error.message || 'Ocorreu um erro na transação. Tente novamente mais tarde.');
						resetForm();
					});
			});

			modalClose.addEventListener('click', closeModal);
			modalOverlay.addEventListener('click', closeModal);
			typeSelect.addEventListener('change', updateForm);
			updateForm();
		})();
	</script>
</x-app-layout>

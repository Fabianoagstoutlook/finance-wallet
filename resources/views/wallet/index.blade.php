<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="text-sm text-gray-500">{{ __('Saldo') }}</div>
                <div class="mt-2 text-3xl font-semibold text-gray-900">
                    R$ {{ number_format((float) $balance, 2, ',', '.') }}
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Movimentações') }}</h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-600">
                            <thead class="text-xs uppercase text-gray-500 border-b">
                                <tr>
                                    <th scope="col" class="px-3 py-2">{{ __('Valor') }}</th>
                                    <th scope="col" class="px-3 py-2">{{ __('Transação') }}</th>
                                    <th scope="col" class="px-3 py-2">{{ __('Data') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($statement as $transaction)
                                    <tr>
                                        <td class="px-3 py-3 font-medium text-gray-900">
                                            R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-3">
                                            {{ $transaction->type->label() }}
                                        </td>
                                        <td class="px-3 py-3">
                                            {{ $transaction->created_at?->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-3 py-6 text-center text-gray-500" colspan="3">
                                            {{ __('Nenhuma movimentação encontrada.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $statement->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
